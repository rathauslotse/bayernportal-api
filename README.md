# BayernPortal API for Laravel

[![ci](https://github.com/rathauslotse/bayernportal-api/actions/workflows/ci.yml/badge.svg)](https://github.com/rathauslotse/bayernportal-api/actions/workflows/ci.yml)

A Laravel client for the [BayernPortal REST API v3](https://www.baybw-services.bayern.de/restapi.htm), the official directory of Bavarian public administration. It covers agencies, authorities, buildings, contact persons, services, service descriptions, forms, online procedures and life situations.

- **English API.** Every class, method, property and parameter has an English name. The German REST names only appear on the wire. See the [glossary](docs/glossary.md).
- **Typed, immutable DTOs.** Responses map to `readonly` objects with value objects for addresses, phone numbers, opening hours and images. The raw payload is always available as `raw`.
- **Pagination built in.** Use `Page` objects with `next()`, or a lazy `all()` that fetches pages only while you iterate.
- **Municipality scope.** `BayernPortal::forMunicipality('09162000')` localises every request that supports it.
- **Optional response caching**, retries on server errors, and typed exceptions that keep the API's original (German) error message.
- **Testing helpers.** `BayernPortal::fake()` answers every endpoint with realistic fixtures.

```php
use Rathauslotse\BayernPortal\Facades\BayernPortal;

$munich = BayernPortal::forMunicipality('09162000');

foreach ($munich->services()->list() as $service) {
    echo $service->name, PHP_EOL;              // "Personalausweis beantragen"
}

$description = $munich->serviceDescriptions()->find(1010);

$description->fees;                            // "37,00 Euro (ab 24 Jahren)"
$description->responsibilities[0]->name;       // "Kreisverwaltungsreferat München"
$description->forms[0]->url;                   // "https://…/antrag.pdf"
```

## Requirements

- PHP 8.3 or newer
- Laravel 12 or 13
- BayernPortal web service credentials ([how to get them](docs/authentication.md))

## Installation

```bash
composer require rathauslotse/bayernportal-api
```

The service provider and the `BayernPortal` facade are registered automatically. Add your credentials to `.env`:

```dotenv
BAYERNPORTAL_USERNAME=your-user
BAYERNPORTAL_PASSWORD=your-password
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=bayernportal-config
```

## Quick start

```php
use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

// Agencies (Dienststellen), paginated
$page = BayernPortal::agencies()->list(size: 50);
$page->total;                     // X-Total-Count
$page->next();                    // next Page or null

// …or iterate everything lazily
BayernPortal::agencies()->all()->each(fn ($agency) => /* … */);

// One agency and what it offers
$agency = BayernPortal::agencies()->find('0916200');
BayernPortal::agencies()->formsByService('0916200', '09162000');

// Authorities, buildings, contacts
$building = BayernPortal::authorities()->building(4711, 801);
$building->streetAddress->format();          // "Ruppertstraße 19, 80337 München"
$building->phone?->format();                 // "+49 89 233-96000"
$building->openingHours?->forDay(Weekday::Tuesday);

// Life situations as a tree
BayernPortal::lifeSituations()->tree();
```

Without the facade, inject `Rathauslotse\BayernPortal\BayernPortal`.

## Documentation

| Topic | |
|---|---|
| Getting started | [Installation](docs/installation.md) · [Configuration](docs/configuration.md) · [Authentication](docs/authentication.md) |
| Endpoints | [Agencies](docs/agencies.md) · [Authorities & buildings](docs/authorities.md) · [Contacts](docs/contacts.md) · [Services](docs/services.md) · [Service descriptions](docs/service-descriptions.md) · [Life situations](docs/life-situations.md) |
| Concepts | [Municipality scope](docs/municipality-scope.md) · [Pagination](docs/pagination.md) · [Caching](docs/caching.md) · [Error handling](docs/error-handling.md) |
| Reference | [Data model](docs/data-model.md) · [Glossary (German → English)](docs/glossary.md) · [API quirks](docs/api-quirks.md) |
| Development | [Testing](docs/testing.md) · [Contributing](CONTRIBUTING.md) · [Changelog](CHANGELOG.md) |

## License

MIT. See [LICENSE](LICENSE).
