# Configuration

All options live in `config/bayernportal.php` and can be set through environment variables.

| Key | Env variable | Default | Description |
|---|---|---|---|
| `driver` | `BAYERNPORTAL_DRIVER` | `api` | `api` uses the REST API (credentials required). `scraper` reads www.bayernportal.de instead. See [Scraper driver](scraper.md). |
| `base_url` | `BAYERNPORTAL_BASE_URL` | `https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/` | API root. Only change it for proxies or test doubles. |
| `username` | `BAYERNPORTAL_USERNAME` | `null` | HTTP Basic Auth user. |
| `password` | `BAYERNPORTAL_PASSWORD` | `null` | HTTP Basic Auth password. |
| `municipality_key` | `BAYERNPORTAL_MUNICIPALITY_KEY` | `null` | Default 8-digit municipality key sent with every localisable request. See [Municipality scope](municipality-scope.md). |
| `timeout` | `BAYERNPORTAL_TIMEOUT` | `30` | Request timeout in seconds. |
| `connect_timeout` | `BAYERNPORTAL_CONNECT_TIMEOUT` | `10` | Connection timeout in seconds. |
| `retry.times` | `BAYERNPORTAL_RETRY_TIMES` | `2` | Additional attempts after a connection error or a 5xx response. `0` disables retries. 4xx responses are never retried. |
| `retry.sleep_ms` | `BAYERNPORTAL_RETRY_SLEEP_MS` | `250` | Pause between attempts in milliseconds. |
| `cache.enabled` | `BAYERNPORTAL_CACHE_ENABLED` | `false` (`true` for the scraper driver) | Cache successful responses. See [Caching](caching.md). |
| `cache.store` | `BAYERNPORTAL_CACHE_STORE` | `null` | Cache store from `config/cache.php`. `null` uses the default store. |
| `cache.ttl` | `BAYERNPORTAL_CACHE_TTL` | `86400` | Cache lifetime in seconds. |
| `cache.prefix` | `BAYERNPORTAL_CACHE_PREFIX` | `bayernportal` | Prefix of all cache keys. |
| `scraper.base_url` | `BAYERNPORTAL_SCRAPER_BASE_URL` | `https://www.bayernportal.de/` | Website root for the scraper driver. |
| `scraper.index_path` | `BAYERNPORTAL_SCRAPER_INDEX_PATH` | `null` | Your own municipality index from `php artisan bayernportal:scrape-index`. `null` uses the bundled index. |
| `scraper.throttle_ms` | `BAYERNPORTAL_SCRAPER_THROTTLE_MS` | `1000` | Minimum delay between two page requests. |
| `scraper.timeout` | `BAYERNPORTAL_SCRAPER_TIMEOUT` | `30` | Page request timeout in seconds. |
| `scraper.retry.times` | `BAYERNPORTAL_SCRAPER_RETRY_TIMES` | `2` | Additional attempts after a connection error, a 5xx response or HTTP 429. |
| `scraper.retry.sleep_ms` | `BAYERNPORTAL_SCRAPER_RETRY_SLEEP_MS` | `2000` | Pause between attempts in milliseconds. |

## Request details

With the `api` driver, every request:

- is a `GET` to `base_url` + path,
- sends `Accept: application/json` (the API also supports XML, but this package only uses JSON),
- sends a `User-Agent: rathauslotse/bayernportal-api/<version>` header,
- uses HTTP Basic Auth when a username is configured,
- serialises boolean parameters as `true`/`false`, drops `null` parameters, and sorts parameters alphabetically.

For the `scraper` driver, see [Scraper driver](scraper.md#how-it-works).

## Using a custom connector

The HTTP layer is behind the `Rathauslotse\BayernPortal\Contracts\Connector` interface. To add logging, metrics or a proxy, decorate it in a service provider:

```php
use Rathauslotse\BayernPortal\Contracts\Connector;

$this->app->extend(Connector::class, fn (Connector $connector) => new LoggingConnector($connector));
```
