<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Tests\TestCase;

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
    })
    ->in('Integration');

/**
 * Decoded JSON fixture from tests/Fixtures.
 *
 * @return array<array-key, mixed>
 */
function jsonFixture(string $name): array
{
    return json_decode((string) file_get_contents(__DIR__.'/Fixtures/'.$name.'.json'), true, 512, JSON_THROW_ON_ERROR);
}
