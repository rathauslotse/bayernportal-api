# Caching

BayernPortal data changes rarely, and some responses (all service descriptions, all contacts) are large. The package can cache successful responses in any Laravel cache store.

## Enable

```dotenv
BAYERNPORTAL_CACHE_ENABLED=true
BAYERNPORTAL_CACHE_STORE=redis     # optional, default store when empty
BAYERNPORTAL_CACHE_TTL=86400       # seconds, default 24 hours
BAYERNPORTAL_CACHE_PREFIX=bayernportal
```

## Behaviour

- The cache key is derived from the path and the normalised (sorted) query, so `forMunicipality('09162000')->services()->list()` and `services()->list('09162000')` share an entry.
- Only **successful** responses are cached. Errors are always fetched again.
- Body and pagination headers (`X-Total-Count`, `Link`) are cached together, so cached pages keep their metadata.
- Entries are stored as plain arrays, so no PHP objects have to be unserialised. This works with Laravel's `cache.serializable_classes` restriction.

## Bypass

```php
BayernPortal::withoutCache()->serviceDescriptions()->find(1010);
```

`withoutCache()` returns a client that talks to the API directly. It does nothing when caching is disabled.

## Flush

```php
BayernPortal::flushCache();
```

Flushing bumps a generation counter (`<prefix>:generation`) that is part of every key. Old entries are not deleted; they become unreachable and expire with their TTL. This works the same on every store, with or without tag support. If you want the memory back immediately, use a dedicated store and clear it with `php artisan cache:clear <store>`.

A typical setup is a nightly scheduled flush after the editorial office has published changes:

```php
Schedule::call(fn () => BayernPortal::flushCache())->dailyAt('03:00');
```
