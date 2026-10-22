<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | "api" (default) talks to the REST API and needs credentials. "scraper"
    | reads the public website www.bayernportal.de instead: no credentials,
    | but much slower and limited to the calls listed in docs/scraper.md.
    |
    */

    'driver' => env('BAYERNPORTAL_DRIVER', 'api'),

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
    | by default for the API driver and enabled by default for the scraper
    | driver. "store" is a cache store from config/cache.php (null uses the
    | default store), "ttl" is in seconds.
    |
    */

    'cache' => [
        'enabled' => (bool) env('BAYERNPORTAL_CACHE_ENABLED', env('BAYERNPORTAL_DRIVER', 'api') === 'scraper'),
        'store' => env('BAYERNPORTAL_CACHE_STORE'),
        'ttl' => (int) env('BAYERNPORTAL_CACHE_TTL', 86400),
        'prefix' => env('BAYERNPORTAL_CACHE_PREFIX', 'bayernportal'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scraper
    |--------------------------------------------------------------------------
    |
    | Only used by the "scraper" driver. "throttle_ms" is the minimum delay
    | between two page requests; keep it at one second or more to stay
    | polite towards the public website. "index_path" points to your own
    | municipality index (php artisan bayernportal:scrape-index); null uses
    | the index bundled with the package.
    |
    */

    'scraper' => [
        'base_url' => env('BAYERNPORTAL_SCRAPER_BASE_URL', 'https://www.bayernportal.de/'),
        'index_path' => env('BAYERNPORTAL_SCRAPER_INDEX_PATH'),
        'throttle_ms' => (int) env('BAYERNPORTAL_SCRAPER_THROTTLE_MS', 1000),
        'timeout' => (int) env('BAYERNPORTAL_SCRAPER_TIMEOUT', 30),
        'retry' => [
            'times' => (int) env('BAYERNPORTAL_SCRAPER_RETRY_TIMES', 2),
            'sleep_ms' => (int) env('BAYERNPORTAL_SCRAPER_RETRY_SLEEP_MS', 2000),
        ],
    ],

];
