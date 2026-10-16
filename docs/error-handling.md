# Error handling

All exceptions extend `Rathauslotse\BayernPortal\Exceptions\BayernPortalException` (a `RuntimeException`).

```
BayernPortalException
├── InvalidParameterException       invalid argument, nothing was sent
├── ConnectionException             DNS, TLS, timeout … (after retries)
└── ResponseException               unsuccessful HTTP response
    ├── RequestException            4xx, e.g. 400
    │   ├── AuthenticationException 401, 403
    │   └── NotFoundException       404, or an empty body for a single resource
    └── ServerException             5xx (after retries)
```

## Response exceptions

```php
use Rathauslotse\BayernPortal\Exceptions\RequestException;

try {
    BayernPortal::agencies()->lifeSituations('0000000');
} catch (RequestException $e) {
    $e->status();        // 400
    $e->apiMessage();    // "Es wurde keine Dienststelle gefunden …" (original German text)
    $e->path;            // "dienststellen/0000000/lebenslagen"
    $e->response;        // Rathauslotse\BayernPortal\Http\ApiResponse (status, body, headers)
    $e->getMessage();    // "BayernPortal API request [GET …] failed with HTTP 400: …"
}
```

The API documents only HTTP 400 and uses it for several business cases:

| Endpoint | Meaning of HTTP 400 |
|---|---|
| `agencies()->lifeSituations()` | Agency not found, or it has no services |
| `authorities()->delegatedServices()` | No permission (only district governments may call it) |

The texts are German plain text, not structured JSON. The package keeps them unchanged in `apiMessage()`.

## Parameter validation

These checks run before a request is sent:

| Value | Rule |
|---|---|
| municipality key | exactly 8 digits |
| agency key | exactly 7 digits |
| authority, building, contact and service IDs | positive integers |
| life situation ID | numeric string |
| page | ≥ 0 |
| page size | 1–100 |

## Retries

Connection errors and 5xx responses are retried `retry.times` times (default 2) with `retry.sleep_ms` between attempts. 4xx responses are never retried. See [Configuration](configuration.md).

## Invalid JSON

If the API returns something that is not JSON, e.g. an HTML maintenance page with status 200, `BayernPortalException` is thrown with "BayernPortal API returned invalid JSON".
