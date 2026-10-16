# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
