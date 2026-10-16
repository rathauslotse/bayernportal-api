# Pagination

Three list endpoints are paginated by the API:

| Method | REST |
|---|---|
| `agencies()->list()` / `all()` | `GET dienststellen` |
| `authorities()->list()` / `all()` | `GET behoerden` |
| `contacts()->paginate()` / `all()` | `GET ansprechpartner?full=true` |

Pages are **zero-based**. The page size is 1–100 (default 100). Other values throw `InvalidParameterException` before the request is sent.

## `Page`

```php
$page = BayernPortal::authorities()->list(page: 0, size: 50);

$page->items;          // Illuminate\Support\Collection of DTOs
$page->page;           // 0
$page->size;           // 50
$page->total;          // from X-Total-Count, or null
$page->links;          // ['first' => '…', 'next' => '…', 'last' => '…'] from the Link header
$page->lastPage();     // zero-based number of the last page, or null
$page->hasNextPage();
$page->isLast();
$page->isEmpty();
count($page);          // items on this page

$next = $page->next(); // Page or null
foreach ($page as $authority) { /* … */ }
```

`hasNextPage()` uses the `Link` header when present (`next`, or `last` compared with the current page). It falls back to `X-Total-Count`. Without either, it assumes there is another page whenever the current one is full.

## Lazy iteration with `all()`

```php
BayernPortal::agencies()->all(pageSize: 100)
    ->filter(fn ($agency) => str_starts_with($agency->agencyKey, '09178'))
    ->each(fn ($agency) => /* … */);
```

`all()` returns a `LazyCollection`. A page is only requested once the iteration reaches it:

```php
BayernPortal::agencies()->all()->take(10)->all();   // a single request
```
