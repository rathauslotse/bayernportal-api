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
