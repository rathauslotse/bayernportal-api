# Testing

## Testing your application: `BayernPortal::fake()`

`BayernPortal::fake()` replaces the API with realistic responses. Every endpoint answers with a bundled fixture (Munich data, modelled on the official XML schema). No credentials are needed.

```php
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('shows the services of a municipality', function () {
    $fake = BayernPortal::fake();

    $this->get('/gemeinde/09162000/leistungen')
        ->assertOk()
        ->assertSee('Personalausweis beantragen');

    $fake->assertRequested('leistungen', ['gemeindekennziffer' => '09162000']);
});
```

### Custom responses

Keys are paths relative to the API base URL. Wildcards (`*`) are allowed, and the first matching key wins.

```php
use Rathauslotse\BayernPortal\Testing\BayernPortalFake;

$fake = BayernPortal::fake([
    // JSON body
    'leistungen/1010' => ['leistung' => ['id' => 1010, 'bezeichnung' => 'Personalausweis beantragen']],

    // error with the API's German message
    'dienststellen/*/lebenslagen' => BayernPortalFake::error(400, 'Es wurde keine Dienststelle gefunden.'),

    // JSON with pagination header
    'behoerden' => BayernPortalFake::json(BayernPortalFake::fixture('authorities'), totalCount: 2),

    // closure receiving the Illuminate\Http\Client\Request
    'lebenslagen/*' => fn ($request) => BayernPortalFake::json(['lebenslage' => ['id' => '1', 'bezeichnung' => 'Test']]),
]);

$fake->respondWith(['leistungen' => BayernPortalFake::error(500)]);   // add more later
```

Unmatched paths without a default fixture answer with HTTP 404.

### Assertions

```php
$fake->assertRequested('dienststellen/0916200');
$fake->assertRequested('leistungsbeschreibungen/*', ['gemeindekennziffer' => '09162000']);
$fake->assertNotRequested('behoerden/*');
$fake->assertRequestCount(3);
$fake->assertNothingRequested();
```

Query parameters use the API's (German) names and string values (`'true'`, `'false'`).

### Fixtures

```php
BayernPortalFake::fixtures();          // ['agencies', 'agency', 'authorities', …]
BayernPortalFake::fixture('agency');   // decoded array
```

The files live in `vendor/rathauslotse/bayernportal-api/resources/fixtures`.

## Testing the package

```bash
composer test              # unit + feature tests (no network)
composer test:coverage     # same, with a 90 % coverage minimum (needs pcov or xdebug)
composer analyse           # Larastan, level max
composer lint              # Pint (composer format to fix)
```

### Live integration suite

`tests/Integration` calls the real API. Without credentials, every test is **skipped**, not failed.

```bash
cp .env.testing.example .env.testing   # git-ignored
# fill in BAYERNPORTAL_USERNAME / BAYERNPORTAL_PASSWORD
composer test:integration
```

Environment variables, read from the shell or `.env.testing`:

| Variable | |
|---|---|
| `BAYERNPORTAL_USERNAME`, `BAYERNPORTAL_PASSWORD` | required |
| `BAYERNPORTAL_TEST_MUNICIPALITY_KEY` | default `09162000` (Munich) |
| `BAYERNPORTAL_TEST_AGENCY_KEY` | default: the first agency in the list |
| `BAYERNPORTAL_INTEGRATION_HEAVY=1` | also run the unpaginated, very large requests (`contacts()->list()`, `serviceDescriptions()->list()`) |
| `BAYERNPORTAL_RECORD_FIXTURES=1` | write every live response to `tests/recorded/` |
| `BAYERNPORTAL_BASE_URL` | point the suite at another host |

The suite discovers IDs dynamically (first agency, first authority with buildings, …), so it does not depend on specific records.

### Contract check against real data

The JSON shape of the REST API is not documented. The fixtures and mappers are derived from the SOAP XML schema. After recording live responses, check them offline:

```bash
BAYERNPORTAL_RECORD_FIXTURES=1 composer test:integration
composer test:contract
```

For each recorded response, the contract test verifies that:

1. the wrapper path the resource uses actually finds items, and
2. every top-level field is known to the mapper.

Failures name the endpoint and the unmapped fields. Without recordings, the test is skipped.
