<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Facades;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Rathauslotse\BayernPortal\BayernPortal as Client;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Http\ConnectorOptions;
use Rathauslotse\BayernPortal\Testing\BayernPortalFake;

/**
 * @method static \Rathauslotse\BayernPortal\Resources\AgencyResource agencies()
 * @method static \Rathauslotse\BayernPortal\Resources\AuthorityResource authorities()
 * @method static \Rathauslotse\BayernPortal\Resources\ContactResource contacts()
 * @method static \Rathauslotse\BayernPortal\Resources\ServiceResource services()
 * @method static \Rathauslotse\BayernPortal\Resources\ServiceDescriptionResource serviceDescriptions()
 * @method static \Rathauslotse\BayernPortal\Resources\LifeSituationResource lifeSituations()
 * @method static Client forMunicipality(?string $municipalityKey)
 * @method static Client withoutCache()
 * @method static void flushCache()
 * @method static string|null municipalityKey()
 * @method static bool usesScraper()
 * @method static \Rathauslotse\BayernPortal\Contracts\Connector connector()
 *
 * @see Client
 */
class BayernPortal extends Facade
{
    /**
     * Replace the API with realistic fake responses for tests.
     *
     * Every endpoint answers with a bundled fixture unless a custom response
     * is given for its path (relative to the API base URL, wildcards
     * allowed). Responses may be arrays (JSON bodies), strings,
     * `Http::response()` results or closures receiving the request.
     *
     * @param  array<string, mixed>  $responses
     */
    public static function fake(array $responses = []): BayernPortalFake
    {
        $baseUrl = config('bayernportal.base_url');

        // The fake answers REST requests, so apps on the scraper driver are
        // switched to the HTTP connector for the duration of the test.
        if (config('bayernportal.driver', 'api') !== 'api') {
            config()->set('bayernportal.driver', 'api');
            app()->forgetInstance(Connector::class);
            app()->forgetInstance(Client::class);
            static::clearResolvedInstance(Client::class);
        }

        $fake = new BayernPortalFake(
            app(Factory::class),
            $responses,
            is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : ConnectorOptions::DEFAULT_BASE_URL,
        );

        return $fake->register();
    }

    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
