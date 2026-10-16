<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Facades;

use Illuminate\Support\Facades\Facade;
use Rathauslotse\BayernPortal\BayernPortal as Client;

/**
 * @method static Client forMunicipality(?string $municipalityKey)
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
