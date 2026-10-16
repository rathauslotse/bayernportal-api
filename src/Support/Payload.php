<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Support;

use BackedEnum;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Tolerant read access to a decoded API payload.
 *
 * The REST API documents its XML schema but not the exact JSON rendering.
 * This class smooths over the usual XML-to-JSON differences:
 *
 * - attributes may appear as `id`, `@id`, `_id` or `-id`,
 * - text content of elements with attributes may be wrapped in
 *   `value`, `$`, `#text`, `_` or `content`,
 * - repeated elements may be a list, a single object, or wrapped in their
 *   container element (`{"synonyme": {"synonym": [...]}}`).
 *
 * @internal
 */
final readonly class Payload
{
    private const array ATTRIBUTE_PREFIXES = ['', '@', '_', '-'];

    private const array TEXT_KEYS = ['value', '$', '#text', '_', 'content', '_value'];

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(private array $data) {}

    public static function of(mixed $data): self
    {
        return new self(is_array($data) ? $data : []);
    }

    /**
     * Descend through the given wrapper keys and return the resulting items.
     *
     * The wrapper keys form an optional path: each one is descended into
     * when present and skipped otherwise, so a list, a single object and a
     * wrapped list/object are all accepted, e.g.
     * `items($body, 'dienststellen', 'dienststelle')`. A segment may list
     * alternatives separated by "|".
     *
     * @return list<self>
     */
    public static function items(mixed $data, string ...$wrappers): array
    {
        $data = self::descend($data, array_values($wrappers));

        if (is_scalar($data)) {
            // A single element with text content only, e.g. one synonym.
            return [new self(['value' => $data])];
        }

        if (! is_array($data) || $data === []) {
            return [];
        }

        if (! array_is_list($data)) {
            return [new self($data)];
        }

        $items = [];

        foreach ($data as $item) {
            if (is_array($item)) {
                $items[] = new self($item);
            } elseif (is_scalar($item)) {
                $items[] = new self(['value' => $item]);
            }
        }

        return $items;
    }

    /**
     * Descend through the given wrapper keys and return a single object.
     *
     * When the payload turns out to be a list, its first entry is used.
     */
    public static function single(mixed $data, string ...$wrappers): self
    {
        return self::items($data, ...$wrappers)[0] ?? new self([]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function raw(): array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function has(string $key): bool
    {
        return $this->lookup($key) !== null;
    }

    /**
     * The raw value for a key, trying attribute prefix variants.
     */
    public function get(string $key): mixed
    {
        return $this->lookup($key);
    }

    /**
     * The first key that is present.
     */
    public function first(string ...$keys): mixed
    {
        foreach ($keys as $key) {
            $value = $this->lookup($key);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    public function string(string ...$keys): ?string
    {
        $value = self::text($this->first(...$keys));

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function int(string ...$keys): ?int
    {
        $value = self::text($this->first(...$keys));

        if ($value === null || ! is_numeric(trim($value))) {
            return null;
        }

        return (int) trim($value);
    }

    public function bool(string ...$keys): ?bool
    {
        $value = $this->first(...$keys);

        if (is_bool($value)) {
            return $value;
        }

        $text = self::text($value);

        return match ($text === null ? null : strtolower(trim($text))) {
            'true', '1', 'yes', 'ja' => true,
            'false', '0', 'no', 'nein' => false,
            default => null,
        };
    }

    public function date(string ...$keys): ?CarbonImmutable
    {
        $value = $this->first(...$keys);

        if (is_int($value)) {
            // Epoch milliseconds, as rendered by some JSON serialisers.
            return CarbonImmutable::createFromTimestampMs($value);
        }

        $text = self::text($value);

        if ($text === null || trim($text) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($text));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    public function enum(string $enum, string ...$keys): ?BackedEnum
    {
        $value = $this->string(...$keys);

        if ($value === null) {
            return null;
        }

        return $enum::tryFrom($value)
            ?? $enum::tryFrom(strtolower($value))
            ?? $enum::tryFrom(strtoupper($value));
    }

    /**
     * A nested object.
     */
    public function payload(string ...$keys): ?self
    {
        $value = $this->first(...$keys);

        if (! is_array($value) || $value === []) {
            return null;
        }

        return self::single($value);
    }

    /**
     * Repeated child elements, e.g. `list('synonyme', 'synonym')`.
     *
     * The first key is the container element. Further keys are the item
     * element names that may wrap the list inside the container. When the
     * container is absent, the item element is looked up directly.
     *
     * @return list<self>
     */
    public function list(string $container, string ...$itemKeys): array
    {
        $value = $this->lookup($container);

        if ($value === null) {
            foreach ($itemKeys as $itemKey) {
                $value = $this->lookup($itemKey);

                if ($value !== null) {
                    return self::items($value);
                }
            }

            return [];
        }

        return self::items($value, ...$itemKeys);
    }

    /**
     * Repeated scalar child elements, e.g. synonyms.
     *
     * @return list<string>
     */
    public function strings(string $container, string ...$itemKeys): array
    {
        $strings = [];

        foreach ($this->list($container, ...$itemKeys) as $item) {
            $value = self::text($item->raw());

            if ($value !== null && trim($value) !== '') {
                $strings[] = trim($value);
            }
        }

        return $strings;
    }

    /**
     * Ids of repeated reference elements such as `<gebaeude id="…"/>`.
     *
     * @return list<int>
     */
    public function ids(string $container, string ...$itemKeys): array
    {
        $ids = [];

        foreach ($this->list($container, ...$itemKeys) as $item) {
            $id = $item->int('id', 'value');

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function lookup(string $key): mixed
    {
        foreach (self::ATTRIBUTE_PREFIXES as $prefix) {
            if (array_key_exists($prefix.$key, $this->data) && $this->data[$prefix.$key] !== null) {
                return $this->data[$prefix.$key];
            }
        }

        return null;
    }

    /**
     * Text content of a scalar or of an element object with attributes.
     */
    private static function text(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) && ! array_is_list($value)) {
            foreach (self::TEXT_KEYS as $key) {
                if (array_key_exists($key, $value)) {
                    return self::text($value[$key]);
                }
            }
        }

        return null;
    }

    /**
     * Follow the optional wrapper path. Each segment is skipped when absent;
     * a segment may list alternatives separated by "|".
     *
     * @param  list<string>  $wrappers
     */
    private static function descend(mixed $data, array $wrappers): mixed
    {
        foreach ($wrappers as $segment) {
            if (! is_array($data) || array_is_list($data)) {
                break;
            }

            foreach (explode('|', $segment) as $wrapper) {
                if (array_key_exists($wrapper, $data)) {
                    $data = $data[$wrapper];

                    break;
                }
            }
        }

        return $data;
    }
}
