<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\BayernPortal as Client;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Data\ServiceReference;

/*
 * Compares the scraper driver with the REST API for one municipality. Needs
 * credentials and BAYERNPORTAL_SCRAPER_LIVE=1. It also answers whether the
 * website's ids equal the REST ids and which agency key form the API uses
 * (see docs/scraper.md).
 */

beforeEach(function (): void {
    if (! getenv('BAYERNPORTAL_SCRAPER_LIVE')) {
        $this->markTestSkipped('Set BAYERNPORTAL_SCRAPER_LIVE=1 to compare the scraper with the API.');
    }
});

function parityClient(string $driver): Client
{
    config()->set('bayernportal.driver', $driver);
    config()->set('bayernportal.cache.enabled', $driver === 'scraper');
    config()->set('cache.default', 'array');
    app()->forgetInstance(Connector::class);
    app()->forgetInstance(Client::class);

    return app(Client::class);
}

it('returns the same agency and services as the API', function (): void {
    $agencyKey = getenv('BAYERNPORTAL_PARITY_AGENCY_KEY') ?: '9184123'; // Stadt Haar

    $api = parityClient('api')->agencies()->find($agencyKey);
    $scraped = parityClient('scraper')->agencies()->find($agencyKey);

    expect($scraped->id)->toBe($api->id)
        ->and($scraped->name)->toBe($api->name)
        ->and($scraped->agencyKey)->toBe($api->agencyKey);

    $ids = fn (iterable $services): array => collect($services)
        ->map(fn (ServiceReference $service): int => $service->id)
        ->sort()
        ->values()
        ->all();

    $apiServices = $ids(parityClient('api')->authorities()->services($api->id));
    $scrapedServices = $ids(parityClient('scraper')->authorities()->services($scraped->id));

    expect(array_intersect($scrapedServices, $apiServices))->toHaveCount(count($apiServices));
});
