# Configuration

All options live in `config/bayernportal.php` and can be set through environment variables.

| Key | Env variable | Default | Description |
|---|---|---|---|
| `base_url` | `BAYERNPORTAL_BASE_URL` | `https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/` | API root. Only change it for proxies or test doubles. |
| `username` | `BAYERNPORTAL_USERNAME` | `null` | HTTP Basic Auth user. |
| `password` | `BAYERNPORTAL_PASSWORD` | `null` | HTTP Basic Auth password. |
| `municipality_key` | `BAYERNPORTAL_MUNICIPALITY_KEY` | `null` | Default 8-digit municipality key sent with every localisable request. See [Municipality scope](municipality-scope.md). |
| `timeout` | `BAYERNPORTAL_TIMEOUT` | `30` | Request timeout in seconds. |
| `connect_timeout` | `BAYERNPORTAL_CONNECT_TIMEOUT` | `10` | Connection timeout in seconds. |
| `retry.times` | `BAYERNPORTAL_RETRY_TIMES` | `2` | Additional attempts after a connection error or a 5xx response. `0` disables retries. 4xx responses are never retried. |
| `retry.sleep_ms` | `BAYERNPORTAL_RETRY_SLEEP_MS` | `250` | Pause between attempts in milliseconds. |
| `cache.enabled` | `BAYERNPORTAL_CACHE_ENABLED` | `false` | Cache successful responses. See [Caching](caching.md). |
| `cache.store` | `BAYERNPORTAL_CACHE_STORE` | `null` | Cache store from `config/cache.php`. `null` uses the default store. |
| `cache.ttl` | `BAYERNPORTAL_CACHE_TTL` | `86400` | Cache lifetime in seconds. |
| `cache.prefix` | `BAYERNPORTAL_CACHE_PREFIX` | `bayernportal` | Prefix of all cache keys. |

## Request details

Every request:

- is a `GET` to `base_url` + path,
- sends `Accept: application/json` (the API also supports XML, but this package only uses JSON),
- sends a `User-Agent: rathauslotse/bayernportal-api/<version>` header,
- uses HTTP Basic Auth when a username is configured,
- serialises boolean parameters as `true`/`false`, drops `null` parameters, and sorts parameters alphabetically.

## Using a custom connector

The HTTP layer is behind the `Rathauslotse\BayernPortal\Contracts\Connector` interface. To add logging, metrics or a proxy, decorate it in a service provider:

```php
use Rathauslotse\BayernPortal\Contracts\Connector;

$this->app->extend(Connector::class, fn (Connector $connector) => new LoggingConnector($connector));
```
