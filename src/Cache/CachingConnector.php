<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Cache;

use BackedEnum;
use Illuminate\Contracts\Cache\Repository;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Http\ApiResponse;
use Rathauslotse\BayernPortal\Http\Query;

/**
 * Caches successful API responses in a Laravel cache store.
 *
 * Only successful responses reach the cache: the wrapped connector throws
 * on errors. Entries are stored as plain arrays, so no classes need to be
 * unserialised. {@see self::flush()} bumps a generation counter instead of
 * deleting keys, which works on every store (tagged or not); old entries
 * expire with their TTL.
 */
final class CachingConnector implements Connector
{
    public function __construct(
        private readonly Connector $connector,
        private readonly Repository $cache,
        private readonly int $ttl = 86400,
        private readonly string $prefix = 'bayernportal',
    ) {}

    public function get(string $path, array $query = []): ApiResponse
    {
        $key = $this->key($path, $query);
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_int($cached['status'] ?? null) && is_string($cached['body'] ?? null)) {
            $headers = is_array($cached['headers'] ?? null) ? $cached['headers'] : [];

            return ApiResponse::make($cached['status'], $cached['body'], $headers);
        }

        $response = $this->connector->get($path, $query);

        $this->cache->put($key, [
            'status' => $response->status,
            'body' => $response->body,
            'headers' => array_intersect_key($response->headers, array_flip(['x-total-count', 'link', 'content-type'])),
        ], $this->ttl);

        return $response;
    }

    /**
     * Invalidate every cached response.
     */
    public function flush(): void
    {
        $this->cache->forever($this->generationKey(), $this->generation() + 1);
    }

    /**
     * The connector that performs the actual requests.
     */
    public function uncached(): Connector
    {
        return $this->connector;
    }

    /**
     * @param  array<string, scalar|BackedEnum|null>  $query
     */
    public function key(string $path, array $query = []): string
    {
        $normalised = ltrim($path, '/').'?'.http_build_query(Query::normalise($query));

        return sprintf('%s:%d:%s', $this->prefix, $this->generation(), sha1($normalised));
    }

    private function generation(): int
    {
        $generation = $this->cache->get($this->generationKey(), 0);

        return is_numeric($generation) ? (int) $generation : 0;
    }

    private function generationKey(): string
    {
        return $this->prefix.':generation';
    }
}
