<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Pagination;

use Closure;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * One page of a paginated list endpoint.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class Page implements Countable, IteratorAggregate
{
    /**
     * @param  Collection<int, TItem>  $items
     * @param  int  $page  Zero-based page number.
     * @param  int  $size  Requested page size.
     * @param  int|null  $total  Total number of items (X-Total-Count), if the API sent it.
     * @param  array<string, string>  $links  Link header URLs per relation (first, prev, next, last).
     * @param  Closure(int): Page<TItem>  $fetch  Loads another page by number.
     */
    public function __construct(
        public Collection $items,
        public int $page,
        public int $size,
        public ?int $total,
        public array $links,
        private Closure $fetch,
    ) {}

    public function hasNextPage(): bool
    {
        if ($this->links !== []) {
            if (isset($this->links['next'])) {
                return true;
            }

            $last = LinkHeader::pageOf($this->links['last'] ?? null);

            if ($last !== null) {
                return $this->page < $last;
            }
        }

        if ($this->total !== null) {
            return ($this->page + 1) * $this->size < $this->total;
        }

        // Without any metadata: a full page suggests there may be more.
        return $this->items->count() >= $this->size;
    }

    public function isLast(): bool
    {
        return ! $this->hasNextPage();
    }

    /**
     * The following page, or null on the last page.
     *
     * @return Page<TItem>|null
     */
    public function next(): ?self
    {
        return $this->hasNextPage() ? ($this->fetch)($this->page + 1) : null;
    }

    /**
     * Number of pages, if the total is known.
     */
    public function lastPage(): ?int
    {
        $last = LinkHeader::pageOf($this->links['last'] ?? null);

        if ($last !== null) {
            return $last;
        }

        return $this->total === null ? null : max(0, (int) ceil($this->total / $this->size) - 1);
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * @return Traversable<int, TItem>
     */
    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }
}
