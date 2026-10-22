<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

use JsonException;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;

/**
 * Maps official municipality keys (Amtlicher Gemeindeschlüssel, 8 digits)
 * to the website's authority ids.
 *
 * The website shows the key only inside statistics links on each
 * municipality page, so the mapping is crawled once by
 * `php artisan bayernportal:scrape-index` and shipped as JSON.
 *
 * @internal
 */
final class MunicipalityIndex
{
    public const string BUNDLED = __DIR__.'/../../resources/scraper/municipalities.json';

    /** @var array<string, array{id: string, name: string}>|null */
    private ?array $entries = null;

    public function __construct(private readonly string $path = self::BUNDLED) {}

    /**
     * @param  array<string, array{id: string, name: string}>  $entries
     */
    public static function fromArray(array $entries): self
    {
        $index = new self('');
        $index->entries = $entries;

        return $index;
    }

    /**
     * @return array{id: string, name: string}|null
     */
    public function find(string $municipalityKey): ?array
    {
        return $this->entries()[$municipalityKey] ?? null;
    }

    /**
     * Resolve a 7-digit agency key (Dienststellenschlüssel).
     *
     * The derivation from the municipality key is not documented. Two
     * forms are accepted: the key without its leading zero ("9184123")
     * and the key without its last digit ("0918412") when unambiguous.
     *
     * @return array{key: string, id: string, name: string}|null
     */
    public function findByAgencyKey(string $agencyKey): ?array
    {
        $entries = $this->entries();

        if (isset($entries['0'.$agencyKey])) {
            return ['key' => '0'.$agencyKey, ...$entries['0'.$agencyKey]];
        }

        $matches = array_filter($entries, static fn (string $key): bool => substr($key, 0, 7) === $agencyKey, ARRAY_FILTER_USE_KEY);

        if (count($matches) !== 1) {
            return null;
        }

        $key = (string) array_key_first($matches);

        return ['key' => $key, ...$matches[$key]];
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    public function all(): array
    {
        return $this->entries();
    }

    /**
     * @return array<string, array{id: string, name: string}>
     */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        if (! is_file($this->path)) {
            return $this->entries = [];
        }

        try {
            $data = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BayernPortalException(sprintf('The municipality index [%s] is not valid JSON.', $this->path), 0, $exception);
        }

        $entries = [];

        foreach (is_array($data) ? $data : [] as $key => $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $entries[(string) $key] = ['id' => $entry['id'], 'name' => is_string($entry['name'] ?? null) ? $entry['name'] : ''];
            }
        }

        return $this->entries = $entries;
    }
}
