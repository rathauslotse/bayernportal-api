<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Facades;

use Illuminate\Support\Facades\Facade;
use Rathauslotse\BayernPortal\BayernPortal as Client;

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
 * @method static \Rathauslotse\BayernPortal\Contracts\Connector connector()
 *
 * @see Client
 */
class BayernPortal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
