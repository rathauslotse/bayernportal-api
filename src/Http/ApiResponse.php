<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Http;

use JsonException;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;

/**
 * Immutable, serialisable snapshot of an API response.
 *
 * Only the parts the package needs are kept, so instances can be stored in
 * any cache store.
 */
final readonly class ApiResponse
{
    /**
     * @param  array<string, string>  $headers  Header names are lower-cased.
     */
    public function __construct(
        public int $status,
        public string $body,
        public array $headers = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $headers  Header values may be strings or lists of strings.
     */
    public static function make(int $status, string $body, array $headers = []): self
    {
        $normalised = [];

        foreach ($headers as $name => $value) {
            $values = array_filter(is_array($value) ? $value : [$value], is_scalar(...));
            $normalised[strtolower((string) $name)] = implode(', ', array_map(strval(...), $values));
        }

        return new self($status, $body, $normalised);
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Decode the JSON body.
     *
     * An empty body decodes to an empty array.
     *
     * @return array<array-key, mixed>
     *
     * @throws BayernPortalException when the body is not valid JSON.
     */
    public function json(): array
    {
        if (trim($this->body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BayernPortalException('BayernPortal API returned invalid JSON: '.$exception->getMessage(), 0, $exception);
        }

        return is_array($decoded) ? $decoded : ['value' => $decoded];
    }
}
