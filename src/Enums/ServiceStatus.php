<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Enums;

/**
 * Editorial status of a service description.
 */
enum ServiceStatus: string
{
    case Live = 'live';
    case Working = 'working';
    case Inactive = 'inactive';
}
