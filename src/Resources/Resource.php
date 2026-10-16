<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Pagination\LinkHeader;
use Rathauslotse\BayernPortal\Pagination\Page;
use Rathauslotse\BayernPortal\Pagination\Paginator;
use Rathauslotse\BayernPortal\Support\Payload;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Shared plumbing for the resource classes.
 *
 * Paths and query parameter names use the API's German names; everything
 * a resource returns is mapped to English-named DTOs.
 *
 * @internal
 */
abstract class Resource
{
    final public function __construct(
        protected readonly Connector $connector,
        protected readonly ?string $municipalityKey = null,
    ) {}

    /**
     * The explicit key wins over the client's municipality scope.
     */
    protected function municipality(?string $municipalityKey): ?string
    {
        return Validate::municipalityKey($municipalityKey ?? $this->municipalityKey);
    }

    /**
     * @template TItem
     *
     * @param  array<string, scalar|BackedEnum|null>  $query
     * @param  list<string>  $wrappers
     * @param  callable(Payload): TItem  $map
     * @return TItem
     *
     * @throws NotFoundException when the API answers with an empty body.
     */
    protected function one(string $path, array $query, array $wrappers, callable $map): mixed
    {
        $response = $this->connector->get($path, $query);
        $payload = Payload::single($response->json(), ...$wrappers);

        if ($payload->isEmpty()) {
            throw NotFoundException::fromResponse($response, $path);
        }

        return $map($payload);
    }

    /**
     * @template TItem
     *
     * @param  array<string, scalar|BackedEnum|null>  $query
     * @param  list<string>  $wrappers
     * @param  callable(Payload): TItem  $map
     * @return Collection<int, TItem>
     */
    protected function many(string $path, array $query, array $wrappers, callable $map): Collection
    {
        return new Collection(array_map($map, Payload::items($this->connector->get($path, $query)->json(), ...$wrappers)));
    }

    /**
     * @template TItem
     *
     * @param  array<string, scalar|BackedEnum|null>  $query
     * @param  list<string>  $wrappers
     * @param  callable(Payload): TItem  $map
     * @return Page<TItem>
     */
    protected function page(string $path, array $query, int $page, int $size, array $wrappers, callable $map): Page
    {
        Validate::page($page);
        Validate::pageSize($size);

        $response = $this->connector->get($path, [...$query, 'page' => $page, 'size' => $size]);
        $total = $response->header('X-Total-Count');

        return new Page(
            items: new Collection(array_map($map, Payload::items($response->json(), ...$wrappers))),
            page: $page,
            size: $size,
            total: $total !== null && is_numeric(trim($total)) ? (int) trim($total) : null,
            links: LinkHeader::parse($response->header('Link')),
            fetch: fn (int $next): Page => $this->page($path, $query, $next, $size, $wrappers, $map),
        );
    }

    /**
     * @template TItem
     *
     * @param  array<string, scalar|BackedEnum|null>  $query
     * @param  list<string>  $wrappers
     * @param  callable(Payload): TItem  $map
     * @return LazyCollection<int, TItem>
     */
    protected function lazy(string $path, array $query, int $size, array $wrappers, callable $map): LazyCollection
    {
        Validate::pageSize($size);

        return Paginator::lazy(fn (int $page): Page => $this->page($path, $query, $page, $size, $wrappers, $map));
    }
}
