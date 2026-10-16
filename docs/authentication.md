# Authentication

The BayernPortal web services use **HTTP Basic Auth over TLS**. There are no API keys or tokens.

## Getting credentials

User IDs are issued by the **central editorial office (Zentrale Redaktion)** at the Bavarian State Ministry for Digital Affairs (Bayerisches Staatsministerium für Digitales). To request access, contact the editorial office through the BayernPortal editorial system:

- https://www.bayvis-redaktionssystem.bayern.de

Mention that you need access to the **REST web service "allgemein" v3** and describe what you will use it for, e.g. municipal information for a specific municipality.

Some endpoints are restricted further. For example, `authorities()->delegatedServices()` only works for the seven Bavarian district governments and answers other callers with HTTP 400 "Sie haben keine Berechtigung …".

## Configuring credentials

```dotenv
BAYERNPORTAL_USERNAME=your-user
BAYERNPORTAL_PASSWORD=your-password
```

Without a username the package sends requests without an `Authorization` header. The API then answers with HTTP 401, which is raised as `AuthenticationException`. This is useful together with [`BayernPortal::fake()`](testing.md), which needs no credentials.

## Wrong credentials

HTTP 401 and 403 raise `Rathauslotse\BayernPortal\Exceptions\AuthenticationException`:

```php
use Rathauslotse\BayernPortal\Exceptions\AuthenticationException;

try {
    BayernPortal::lifeSituations()->list();
} catch (AuthenticationException $e) {
    report($e);           // check BAYERNPORTAL_USERNAME / BAYERNPORTAL_PASSWORD
}
```
