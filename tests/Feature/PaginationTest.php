<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Pagination\Page;
use Rathauslotse\BayernPortal\Resources\Resource;
use Rathauslotse\BayernPortal\Support\Payload;

final class PaginationTestResource extends Resource
{
    /**
     * @return Page<int>
     */
    public function list(int $page = 0, int $size = 2): Page
    {
        return $this->page('behoerden', ['full' => false], $page, $size, ['behoerden', 'behoerde'], fn (Payload $p): int => $p->int('id') ?? 0);
    }

    /**
     * @return LazyCollection<int, int>
     */
    public function all(int $size = 2): LazyCollection
    {
        return $this->lazy('behoerden', ['full' => false], $size, ['behoerden', 'behoerde'], fn (Payload $p): int => $p->int('id') ?? 0);
    }
}

function paginationResource(): PaginationTestResource
{
    return new PaginationTestResource(app(Connector::class));
}

/**
 * @return array<string, string>
 */
function pageHeaders(int $page, int $last, ?int $total = 5): array
{
    $url = fn (int $p): string => '<https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/behoerden?page='.$p.'&size=2>';
    $links = [$url(0).'; rel="first"', $url($last).'; rel="last"'];

    if ($page < $last) {
        $links[] = $url($page + 1).'; rel="next"';
    }

    return array_filter(['X-Total-Count' => $total === null ? null : (string) $total, 'Link' => implode(', ', $links)]);
}

/**
 * @param  list<int>  $ids
 * @return array<string, mixed>
 */
function pageBody(array $ids): array
{
    return ['behoerden' => ['behoerde' => array_map(fn (int $id): array => ['id' => $id], $ids)]];
}

function fakeThreePages(): void
{
    Http::fake([
        '*page=0*' => Http::response(pageBody([1, 2]), 200, pageHeaders(0, 2)),
        '*page=1*' => Http::response(pageBody([3, 4]), 200, pageHeaders(1, 2)),
        '*page=2*' => Http::response(pageBody([5]), 200, pageHeaders(2, 2)),
    ]);
}

it('returns a page with metadata', function (): void {
    fakeThreePages();

    $page = paginationResource()->list();

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->items->all())->toBe([1, 2])
        ->and($page->page)->toBe(0)
        ->and($page->size)->toBe(2)
        ->and($page->total)->toBe(5)
        ->and($page->lastPage())->toBe(2)
        ->and($page->hasNextPage())->toBeTrue()
        ->and($page->isLast())->toBeFalse()
        ->and(count($page))->toBe(2)
        ->and(iterator_to_array($page))->toBe([1, 2])
        ->and(array_keys($page->links))->toBe(['first', 'last', 'next']);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'behoerden?full=false&page=0&size=2'));
});

it('walks to the next page until the last one', function (): void {
    fakeThreePages();

    $last = paginationResource()->list()->next()?->next();

    expect($last?->items->all())->toBe([5])
        ->and($last?->isLast())->toBeTrue()
        ->and($last?->next())->toBeNull();

    Http::assertSentCount(3);
});

it('iterates all pages lazily', function (): void {
    fakeThreePages();

    $all = paginationResource()->all();

    Http::assertNothingSent();

    expect($all->all())->toBe([1, 2, 3, 4, 5]);

    Http::assertSentCount(3);
});

it('only loads the pages it needs', function (): void {
    fakeThreePages();

    expect(paginationResource()->all()->take(3)->all())->toBe([1, 2, 3]);

    Http::assertSentCount(2);
});

it('falls back to the total count without link headers', function (): void {
    Http::fake([
        '*page=0*' => Http::response(pageBody([1, 2]), 200, ['X-Total-Count' => '3']),
        '*page=1*' => Http::response(pageBody([3]), 200, ['X-Total-Count' => '3']),
    ]);

    expect(paginationResource()->all()->all())->toBe([1, 2, 3])
        ->and(paginationResource()->list()->lastPage())->toBe(1);
});

it('falls back to the page fill level without any metadata', function (): void {
    Http::fake([
        '*page=0*' => Http::response(pageBody([1, 2])),
        '*page=1*' => Http::response(pageBody([3, 4])),
        '*page=2*' => Http::response(pageBody([])),
    ]);

    $first = paginationResource()->list();

    expect($first->total)->toBeNull()
        ->and($first->lastPage())->toBeNull()
        ->and($first->hasNextPage())->toBeTrue()
        ->and(paginationResource()->all()->all())->toBe([1, 2, 3, 4]);
});

it('handles an empty result', function (): void {
    Http::fake(['*' => Http::response(['behoerden' => []], 200, ['X-Total-Count' => '0'])]);

    $page = paginationResource()->list();

    expect($page->isEmpty())->toBeTrue()
        ->and($page->hasNextPage())->toBeFalse()
        ->and($page->lastPage())->toBe(0)
        ->and(paginationResource()->all()->all())->toBe([]);

    Http::assertSentCount(2);
});

it('uses the last link when there is no next link', function (): void {
    Http::fake(['*' => Http::response(pageBody([1, 2]), 200, ['Link' => '<x?page=0&size=2>; rel="last"'])]);

    expect(paginationResource()->list()->hasNextPage())->toBeFalse();
});

it('validates page and size before sending', function (int $page, int $size): void {
    Http::fake();

    expect(fn () => paginationResource()->list($page, $size))->toThrow(InvalidParameterException::class);
    expect(fn () => paginationResource()->all($size === 2 ? 0 : $size)->all())->toThrow(InvalidParameterException::class);

    Http::assertNothingSent();
})->with([[-1, 2], [0, 0], [0, 101]]);
