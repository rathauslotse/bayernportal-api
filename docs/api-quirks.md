# Known API quirks

Things about the BayernPortal REST API (spec version 9.29.1, 09.09.2026) that are not obvious, and how the package handles them.

## The JSON shape is not documented

The REST page documents endpoints and parameters, but not the response structure. The functionally identical SOAP API publishes XML schemas (WSDL v3), and the package's DTOs and fixtures are derived from those.

The XML-to-JSON rendering is not specified either, so the mapper (`Support\Payload`) is deliberately tolerant:

- attributes may appear as `id`, `@id`, `_id` or `-id`,
- text of elements with attributes may be wrapped in `value`, `$`, `#text`, `_` or `content`,
- repeated elements may be a list, a single object, or wrapped in their container (`{"synonyme": {"synonym": […]}}`),
- the response may or may not be wrapped in its root element (`{"dienststellen": {"dienststelle": […]}}`).

To verify this against real data, run `BAYERNPORTAL_RECORD_FIXTURES=1 composer test:integration` followed by `composer test:contract`. See [Testing](testing.md#contract-check-against-real-data).

## Errors are German plain text, mostly HTTP 400

Only HTTP 400 is documented, and it is used for business cases ("no agency found", "no permission"). Messages are German plain text, not JSON. The package keeps them in `ResponseException::apiMessage()`. See [Error handling](error-handling.md).

## Unresolved placeholders in the spec

Two endpoint URLs in the spec contain an unresolved `${serverUrl}` placeholder (agency life situations, authority contacts). The package uses the regular base URL for them.

## Link headers point to another host

The example `Link` headers in the spec use `baybw-services.bayern.de` instead of the API host. The package only reads the `page` parameter and the relation types from `Link`, and always sends requests to the configured `base_url`.

## Pagination differs per endpoint

- `dienststellen` and `behoerden` are paginated in both short and full form.
- `ansprechpartner` is only paginated with `full=true`. That is why `contacts()` has both `list()` (short, unpaginated) and `paginate()` / `all()` (full).
- Everything else is unpaginated, and some responses are very large (all service descriptions). Use [caching](caching.md).

## Grouped vs. ungrouped forms

`dienststellen/{key}/formulare` and `…/onlineverfahren` return two completely different structures depending on `gruppiertNachLeistungen`, and the API default is `true`. The package exposes them as separate methods with precise return types (`forms()` / `formsByService()`).

## Schema typos

The responsibility (`Zustaendigkeit`) type names its authority ID `behoerdId`. The package also accepts `behoerdeId`.

## Default values that are not documented

The defaults of `nurDirekteLeistungszuordnungen` and `leistungenZugeordnet` are not documented. The package only sends them when you pass a value (`null` = not sent).

## Life situation IDs

Life situation IDs are numeric **strings** (sorted from `100001000` to `35001305` according to the spec). Top-level categories have an empty or zero `vorgaengerId`; the package maps both to `parentId = null`.
