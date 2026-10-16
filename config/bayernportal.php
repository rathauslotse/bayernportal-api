<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Root of the BayernPortal REST API ("allgemein", version 3). Only change
    | this if you route requests through a proxy or a test double.
    |
    */

    'base_url' => env('BAYERNPORTAL_BASE_URL', 'https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | HTTP Basic Auth credentials issued by the central editorial office
    | ("Zentrale Redaktion") of the Bavarian State Ministry for Digital
    | Affairs. See docs/authentication.md.
    |
    */

    'username' => env('BAYERNPORTAL_USERNAME'),

    'password' => env('BAYERNPORTAL_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Default municipality key
    |--------------------------------------------------------------------------
    |
    | Optional 8-digit official municipality key (Amtlicher Gemeindeschlüssel,
    | e.g. "09162000" for Munich). When set, it is sent with every request
    | that supports localisation, unless a call passes its own key.
    |
    */

    'municipality_key' => env('BAYERNPORTAL_MUNICIPALITY_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HTTP options
    |--------------------------------------------------------------------------
    |
    | Timeouts are in seconds. Retries only happen on connection errors and
    | 5xx responses; client errors (4xx) are never retried.
    |
    */

    'timeout' => (int) env('BAYERNPORTAL_TIMEOUT', 30),

    'connect_timeout' => (int) env('BAYERNPORTAL_CONNECT_TIMEOUT', 10),

    'retry' => [
        'times' => (int) env('BAYERNPORTAL_RETRY_TIMES', 2),
        'sleep_ms' => (int) env('BAYERNPORTAL_RETRY_SLEEP_MS', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response cache
    |--------------------------------------------------------------------------
    |
    | BayernPortal data changes rarely, so responses can be cached. Disabled
    | by default. "store" is a cache store from config/cache.php (null uses
    | the default store), "ttl" is in seconds.
    |
    */

    'cache' => [
        'enabled' => (bool) env('BAYERNPORTAL_CACHE_ENABLED', false),
        'store' => env('BAYERNPORTAL_CACHE_STORE'),
        'ttl' => (int) env('BAYERNPORTAL_CACHE_TTL', 86400),
        'prefix' => env('BAYERNPORTAL_CACHE_PREFIX', 'bayernportal'),
    ],

];
