<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Pagination;

use Closure;
use Illuminate\Support\LazyCollection;

/**
 * Iterates all items of a paginated endpoint lazily.
 *
 * @internal
 */
final class Paginator
{
    /**
     * Pages are only requested while the collection is being consumed, so
     * `->take(10)` on a large list sends a single request.
     *
     * @template TItem
     *
     * @param  Closure(int): Page<TItem>  $fetch
     * @return LazyCollection<int, TItem>
     */
    public static function lazy(Closure $fetch, int $startPage = 0): LazyCollection
    {
        return LazyCollection::make(function () use ($fetch, $startPage) {
            $page = $fetch($startPage);

            while (true) {
                // Plain yields keep keys continuous across pages.
                foreach ($page->items as $item) {
                    yield $item;
                }

                if ($page->isEmpty()) {
                    return;
                }

                $page = $page->next();

                if ($page === null) {
                    return;
                }
            }
        });
    }
}
