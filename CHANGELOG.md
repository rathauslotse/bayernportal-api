# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-22

### Added

- Scraper driver (`BAYERNPORTAL_DRIVER=scraper`) that reads www.bayernportal.de when no API credentials are available: municipality-centric calls, throttling, bot detection, a bundled municipality index and the `bayernportal:scrape-index` command. See `docs/scraper.md`.
- `BayernPortal::usesScraper()` to tell which driver a client uses.
- Opt-in parity test that compares the scraper with the REST API for one municipality.

### Changed

- Requires `ext-dom`, `ext-libxml` and `illuminate/console`.

## [1.0.0] - 2026-10-16

### Added

- Client for all 23 endpoints of the BayernPortal REST API v3 ("allgemein"): agencies, authorities, buildings, contacts, services, service descriptions, forms, online procedures and life situations.
- English-named, immutable DTOs and value objects (`Address`, `PhoneNumber`, `OpeningHours`, `TimeSlot`, `Image`) mapped from the WSDL v3 schemas, with the raw payload kept in `raw`.
- Tolerant payload mapping for the undocumented JSON rendering.
- Pagination via `Page` (`next()`, `total`, `links`) and lazy `all()` iteration.
- Municipality scope via `forMunicipality()` and the `municipality_key` config option.
- Optional response caching with `withoutCache()` and `flushCache()`.
- Typed exception hierarchy that keeps the original German API messages; parameter validation before requests are sent.
- Retries on connection errors and 5xx responses.
- `BayernPortal::fake()` with bundled fixtures and request assertions.
- Live integration test suite with response recording and an offline contract check.

[Unreleased]: https://github.com/rathauslotse/bayernportal-api/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/rathauslotse/bayernportal-api/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/rathauslotse/bayernportal-api/releases/tag/v1.0.0
