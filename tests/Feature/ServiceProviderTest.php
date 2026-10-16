<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\BayernPortal;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Facades\BayernPortal as BayernPortalFacade;
use Rathauslotse\BayernPortal\Http\HttpConnector;

it('merges the package config', function (): void {
    expect(config('bayernportal.base_url'))->toBe('https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/')
        ->and(config('bayernportal.cache.enabled'))->toBeFalse()
        ->and(config('bayernportal.cache.ttl'))->toBe(86400);
});

it('binds the client and connector as singletons', function (): void {
    expect(app(BayernPortal::class))->toBe(app(BayernPortal::class))
        ->and(app(Connector::class))->toBeInstanceOf(HttpConnector::class);
});

it('resolves the facade', function (): void {
    expect(BayernPortalFacade::getFacadeRoot())->toBeInstanceOf(BayernPortal::class)
        ->and(BayernPortalFacade::municipalityKey())->toBeNull();
});

it('applies the default municipality key from config', function (): void {
    config()->set('bayernportal.municipality_key', '09162000');
    app()->forgetInstance(BayernPortal::class);

    expect(app(BayernPortal::class)->municipalityKey())->toBe('09162000');
});

it('scopes the client to a municipality immutably', function (): void {
    $client = app(BayernPortal::class);
    $scoped = $client->forMunicipality('09178124');

    expect($scoped)->not->toBe($client)
        ->and($scoped->municipalityKey())->toBe('09178124')
        ->and($client->municipalityKey())->toBeNull()
        ->and($scoped->connector())->toBe($client->connector());
});

it('rejects an invalid municipality scope', function (): void {
    app(BayernPortal::class)->forMunicipality('123');
})->throws(InvalidParameterException::class);

it('publishes the config file', function (): void {
    $this->artisan('vendor:publish', ['--tag' => 'bayernportal-config', '--force' => true])->assertSuccessful();

    expect(file_exists(config_path('bayernportal.php')))->toBeTrue();

    unlink(config_path('bayernportal.php'));
});
