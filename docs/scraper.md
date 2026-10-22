# Scraper driver (no API credentials)

The REST API needs credentials from the Zentrale Redaktion (see [Authentication](authentication.md)). Without them, the **scraper driver** reads the public website [www.bayernportal.de](https://www.bayernportal.de) instead. It serves the same client API and returns the same DTOs, but it is **much slower** and supports **fewer calls**.

```dotenv
BAYERNPORTAL_DRIVER=scraper
```

The REST API remains the default (`BAYERNPORTAL_DRIVER=api`). The package never switches drivers on its own.

## How it works

The scraper is a [connector](configuration.md#using-a-custom-connector). It maps each REST path to one or more website pages, parses the HTML and answers with the JSON the REST API would return. Resources, DTOs, the municipality scope and the cache therefore work unchanged.

Every page request:

- is a `GET` to `https://www.bayernportal.de/…` without credentials,
- sends `User-Agent: rathauslotse/bayernportal-api/<version> (+scraper)`,
- waits at least `scraper.throttle_ms` (default 1000 ms) after the previous request. With a cache store configured, this delay is shared between processes,
- is retried on connection errors, 5xx responses and HTTP 429.

## Speed

One page takes 0.3–2 s, plus the throttle. Rough numbers at the default throttle:

| Call | Requests | Duration |
|---|---|---|
| `authorities()->find()`, `agencies()->find()` | 2 (the page and its buildings) | ~3 s |
| `serviceDescriptions()->find($id, $key)` | 1 (+1 the first time for a municipality) | ~1–2 s |
| `services()->list($key)` | 1 | ~2 s |
| `agencies()->serviceDescriptions($key)`, `forms()`, `serviceDescriptions()->list($key)` | 1 + one per service (~300 for a municipality) | ~6–10 min |
| `lifeSituations()->list()` | one per life situation (several hundred) | ~5–10 min |

**Keep the cache on.** It is enabled by default for the scraper driver (`BAYERNPORTAL_CACHE_ENABLED` defaults to `true` when the driver is `scraper`). Use a persistent store such as Redis or a database rather than `array`. Aggregating calls fetch every item through the cache one by one. An interrupted run therefore resumes where it stopped, and the other aggregates of the same municipality reuse the items.

Heavy calls belong in a queued job or a scheduled command, not in a web request.

## Supported calls

| Call | Scraper | Website source |
|---|---|---|
| `agencies()->find($agencyKey)` | ✅ | authority page + buildings |
| `agencies()->services()` | ✅ | `/dokumente/behoerde/{id}/leistungen` |
| `agencies()->serviceDescriptions()` | ✅ slow | one service description page per service |
| `agencies()->forms()`, `formsByService()` | ✅ slow | from the service descriptions |
| `agencies()->onlineProcedures()`, `onlineProceduresByService()` | ✅ | `/dokumente/behoerde/{id}/onlineverfahren` |
| `agencies()->list()`, `all()` | ❌ | – |
| `agencies()->lifeSituations()` | ❌ | – |
| `authorities()->find()` | ✅ | `/dokumente/behoerde/{id}` |
| `authorities()->building()`, `buildingContact()` | ✅ | `/dokumente/behoerde/{id}/adressen` |
| `authorities()->contacts()` | ✅ | `/dokumente/behoerde/{id}/mitarbeiter/alle` |
| `authorities()->services()` | ✅ | `/dokumente/behoerde/{id}/leistungen` |
| `authorities()->list()`, `all()`, `delegatedServices()` | ❌ | – |
| `contacts()->*` | ❌ | – |
| `services()->list()` (with or without a municipality) | ✅ | municipality list or `/suche/leistung/alphabetisch` |
| `services()->find()` | ✅ | `/dokumente/leistung/{id}` |
| `serviceDescriptions()->find()` | ✅ | `/dokumente/leistung/{id}` |
| `serviceDescriptions()->list($municipalityKey)` | ✅ slow | one page per service |
| `serviceDescriptions()->list()` without a municipality | ❌ | it would read ~3,400 pages |
| `lifeSituations()->find()` | ✅ | `/dokumente/lebenslage/{id}` |
| `lifeSituations()->list()`, `tree()` | ✅ slow | crawls the three category trees |

Unsupported calls throw `UnsupportedByScraperException` before any request is sent.

## Agencies and the municipality index

The website identifies municipalities by its own authority ids. The 8-digit municipality key appears only inside statistics links on each municipality page. The package therefore ships an index (`resources/scraper/municipalities.json`) that maps every Bavarian municipality key to its authority id.

`agencies()->find()` and the other agency calls take a 7-digit agency key. The scraper accepts two forms of it and resolves both through the index:

- the municipality key without its leading zero (`09184123` → `9184123`),
- the municipality key without its last digit (`0916200` for Munich), but only when exactly one municipality matches. In practice that holds for independent cities only (58 of 2,056 keys).

How the REST API derives agency keys is not documented, so check the result for your municipality. The municipality key is always available as `$agency->raw['gemeindekennziffer']`.

Rebuild the index when municipalities change (about 2,050 requests, ~35 minutes):

```bash
php artisan bayernportal:scrape-index                    # writes storage/app/bayernportal/municipalities.json
BAYERNPORTAL_SCRAPER_INDEX_PATH=storage/app/bayernportal/municipalities.json
```

`--limit=N` crawls only N municipalities. An existing file is resumed unless you pass `--fresh`.

## Differences from the API driver

- **Ids.** Authority, service and procedure ids are the website's ids, which match the ids in the REST API's `url` and `bwwlink` fields. The website does not show ids for **buildings and contacts**. Their ids are synthetic: stable hashes of the authority and the visible name and address. They work with `building()` and `buildingContact()`, but they are not the REST API's ids.
- **Leading zeros.** Some website ids start with `0` (e.g. `047980064430`). An integer id loses that zero. The scraper remembers every id it sees on a page and otherwise retries with a leading zero, which can cost one extra request.
- **Missing fields.** The website does not show sort orders, logo image data, subordinate authorities, written-form requirements of forms, internal ids, last-modified timestamps (only "Stand") or the relation from life situations to agencies. These fields stay empty.
- **Authority type.** `type` and `group` come from the breadcrumb (e.g. "Gemeinden", "Behörden"), not from the REST categories.
- **Regional supplements.** With a municipality, the website shows the municipality's supplements inline in the sections and lists the responsible authorities. The section HTML (`description`, `fees` …) therefore includes the supplements. Each supplement is also available in `$description->raw['regionaleErgaenzungen']` (section, authority name and HTML). `regionalSupplementIds` stays empty because the website shows no ids.
- **Extra facts.** The authority pages show more than the REST `Authority` DTO holds: address, phone, fax, the parent district office, memberships in special-purpose associations and the municipality key. These are kept in `$authority->raw`.
- **Errors.** The website answers unknown pages with HTTP 400 and a "Seite nicht gefunden" page. The scraper maps this to `NotFoundException`. When the website rate-limits or shows a bot check (captcha), the scraper throws `ScraperBlockedException`. Raise `scraper.throttle_ms` and try again later.

## Testing

`BayernPortal::fake()` works with both drivers. It switches the container to the API connector, so tests do not need to care about the driver.

`composer test:scraper` runs a small live suite against the real website (about 15 requests). It also detects markup changes early.

## Being a good citizen

The website is a public service of the Bavarian state. Keep the throttle at one second or more, cache aggressively and run heavy crawls outside office hours. Whenever possible, ask the Zentrale Redaktion for API access.
