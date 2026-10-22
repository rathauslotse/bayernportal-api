<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Rathauslotse\BayernPortal\Tests\TestCase;

// Credentials for the live suite may live in an untracked .env.testing.
if (is_file(dirname(__DIR__).'/.env.testing')) {
    Dotenv::createUnsafeImmutable(dirname(__DIR__), '.env.testing')->safeLoad();
}

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| Live integration suite
|--------------------------------------------------------------------------
|
| Runs against the real BayernPortal API with the credentials from the
| BAYERNPORTAL_USERNAME / BAYERNPORTAL_PASSWORD environment variables and
| skips itself when they are missing. Run it with `composer test:integration`.
|
*/

pest()->extend(TestCase::class)
    ->group('integration')
    ->beforeEach(function (): void {
        $username = getenv('BAYERNPORTAL_USERNAME') ?: null;
        $password = getenv('BAYERNPORTAL_PASSWORD') ?: null;

        if ($username === null || $password === null) {
            $this->markTestSkipped('Set BAYERNPORTAL_USERNAME and BAYERNPORTAL_PASSWORD to run the live integration suite.');
        }

        config()->set('bayernportal.username', $username);
        config()->set('bayernportal.password', $password);
        config()->set('bayernportal.retry.sleep_ms', 500);

        if ($baseUrl = getenv('BAYERNPORTAL_BASE_URL')) {
            config()->set('bayernportal.base_url', $baseUrl);
        }

        if (getenv('BAYERNPORTAL_RECORD_FIXTURES')) {
            app()->extend(
                Rathauslotse\BayernPortal\Contracts\Connector::class,
                fn (Rathauslotse\BayernPortal\Contracts\Connector $connector) => new Rathauslotse\BayernPortal\Tests\Support\RecordingConnector($connector, __DIR__.'/recorded'),
            );
        }
    })
    ->in('Integration');

pest()->extend(TestCase::class)->group('contract')->in('Contract');

/*
|--------------------------------------------------------------------------
| Live scraper suite
|--------------------------------------------------------------------------
|
| Runs the scraper driver against the real www.bayernportal.de. Opt in with
| BAYERNPORTAL_SCRAPER_LIVE=1 (`composer test:scraper`); no credentials needed.
|
*/

pest()->extend(TestCase::class)
    ->group('scraper-live')
    ->beforeEach(function (): void {
        if (! getenv('BAYERNPORTAL_SCRAPER_LIVE')) {
            $this->markTestSkipped('Set BAYERNPORTAL_SCRAPER_LIVE=1 to run the live scraper suite.');
        }

        config()->set('bayernportal.driver', 'scraper');
        config()->set('bayernportal.cache.enabled', true);
        config()->set('cache.default', 'array');
    })
    ->in('ScraperLive');

/**
 * Skip a live test unless heavy (unpaginated, very large) requests are allowed.
 */
function requiresHeavyRequests(): void
{
    if (! getenv('BAYERNPORTAL_INTEGRATION_HEAVY')) {
        test()->markTestSkipped('Very large response. Set BAYERNPORTAL_INTEGRATION_HEAVY=1 to run it.');
    }
}

/**
 * Municipality used by the live suite (default: Munich).
 */
function liveMunicipalityKey(): string
{
    return getenv('BAYERNPORTAL_TEST_MUNICIPALITY_KEY') ?: '09162000';
}

/**
 * Decoded JSON fixture from tests/Fixtures.
 *
 * @return array<array-key, mixed>
 */
function jsonFixture(string $name): array
{
    return json_decode((string) file_get_contents(__DIR__.'/../resources/fixtures/'.$name.'.json'), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Fake every API request with the given fixture.
 *
 * @param  array<string, string>  $headers
 */
function fakeApi(string $fixture, array $headers = [], int $status = 200): void
{
    Illuminate\Support\Facades\Http::fake(['*' => Illuminate\Support\Facades\Http::response(jsonFixture($fixture), $status, $headers)]);
}

/**
 * Assert that exactly this path (relative to the base URL, including the
 * sorted query string) was requested.
 */
function assertRequested(string $pathAndQuery): void
{
    Illuminate\Support\Facades\Http::assertSent(
        fn (Illuminate\Http\Client\Request $request): bool => $request->url() === TestCase::BASE_URL.$pathAndQuery,
    );
}

/**
 * Switch the container to the scraper driver with the test index, no
 * throttling and no retries.
 */
function useScraper(bool $cache = false): void
{
    config()->set('cache.default', 'array');
    config()->set('bayernportal.driver', 'scraper');
    config()->set('bayernportal.cache.enabled', $cache);
    config()->set('bayernportal.scraper.index_path', __DIR__.'/Fixtures/scraper/municipalities.json');
    config()->set('bayernportal.scraper.throttle_ms', 0);
    config()->set('bayernportal.scraper.retry.times', 0);
    config()->set('bayernportal.scraper.retry.sleep_ms', 0);
    app()->forgetInstance(Rathauslotse\BayernPortal\Contracts\Connector::class);
    app()->forgetInstance(Rathauslotse\BayernPortal\BayernPortal::class);
    Rathauslotse\BayernPortal\Facades\BayernPortal::clearResolvedInstances();
}

/**
 * HTML fixture recorded from www.bayernportal.de (tests/Fixtures/scraper).
 */
function htmlFixture(string $name): string
{
    return (string) file_get_contents(__DIR__.'/Fixtures/scraper/'.$name.'.html');
}

/**
 * Serve the recorded Stadt Haar pages for www.bayernportal.de requests.
 * $overrides maps a path (without query) to a fixture name or a response.
 *
 * @param  array<string, string|GuzzleHttp\Promise\PromiseInterface>  $overrides
 */
function fakeWebsite(array $overrides = []): void
{
    $haar = 'dokumente/behoerde/66552873578';
    $routes = [
        $haar => 'authority',
        $haar.'/adressen' => 'buildings',
        $haar.'/mitarbeiter/alle' => 'contacts',
        $haar.'/leistungen' => 'authority-services',
        $haar.'/onlineverfahren' => 'online-procedures',
        'dokumente/leistung/8110751776' => 'service-description-full',
        'suche/leistung/alphabetisch' => 'services',
        'suche/lebenslage/hierarchisch/buerger' => 'life-situation-category',
        'dokumente/lebenslage/781142333177167' => 'life-situation',
        ...$overrides,
    ];

    Illuminate\Support\Facades\Http::fake(function (Illuminate\Http\Client\Request $request) use ($routes) {
        $path = ltrim((string) parse_url($request->url(), PHP_URL_PATH), '/');
        $localised = str_contains($request->url(), 'gemeinde=');
        $route = $routes[$path] ?? match (true) {
            str_starts_with($path, 'dokumente/leistung/') => $localised ? 'service-description-localised' : 'service-description',
            str_starts_with($path, 'dokumente/lebenslage/') => 'life-situation-leaf',
            str_starts_with($path, 'suche/lebenslage/hierarchisch/') => '<html><body><h1>Lebenslagen</h1></body></html>',
            default => null,
        };

        if ($route instanceof GuzzleHttp\Promise\PromiseInterface) {
            return $route;
        }

        if ($route === null) {
            return Illuminate\Support\Facades\Http::response(htmlFixture('not-found'), 400);
        }

        return Illuminate\Support\Facades\Http::response(str_starts_with($route, '<') ? $route : htmlFixture($route), 200, ['Content-Type' => 'text/html;charset=UTF-8']);
    });
}
