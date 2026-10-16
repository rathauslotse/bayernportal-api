<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Contracts;

use BackedEnum;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Http\ApiResponse;

/**
 * Sends GET requests to the BayernPortal API.
 */
interface Connector
{
    /**
     * @param  string  $path  Path relative to the API base URL, e.g. "dienststellen/0916200".
     * @param  array<string, scalar|BackedEnum|null>  $query  Query parameters using the API's (German) names. Null values are dropped.
     *
     * @throws BayernPortalException
     */
    public function get(string $path, array $query = []): ApiResponse;
}
