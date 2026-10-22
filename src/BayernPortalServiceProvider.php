<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Rathauslotse\BayernPortal\Cache\CachingConnector;
use Rathauslotse\BayernPortal\Console\BuildMunicipalityIndexCommand;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Http\ConnectorOptions;
use Rathauslotse\BayernPortal\Http\HttpConnector;
use Rathauslotse\BayernPortal\Scraper\MunicipalityIndex;
use Rathauslotse\BayernPortal\Scraper\PortalClient;
use Rathauslotse\BayernPortal\Scraper\ScraperOptions;
use Rathauslotse\BayernPortal\Scraper\ScraperState;
use Rathauslotse\BayernPortal\Scraper\ScrapingConnector;
use Rathauslotse\BayernPortal\Scraper\Throttle;

class BayernPortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bayernportal.php', 'bayernportal');

        $this->app->singleton(Connector::class, static function (Application $app): Connector {
            $config = self::config($app);
            $driver = $config['driver'] ?? 'api';

            return match ($driver) {
                'api' => self::cached($app, $config, new HttpConnector(
                    $app->make(Factory::class),
                    ConnectorOptions::fromConfig($config),
                )),
                'scraper' => self::scraper($app, $config),
                default => throw new InvalidArgumentException(sprintf(
                    'Unknown BayernPortal driver [%s]. Use "api" or "scraper".',
                    is_scalar($driver) ? (string) $driver : get_debug_type($driver),
                )),
            };
        });

        $this->app->singleton(BayernPortal::class, static function (Application $app): BayernPortal {
            $key = self::config($app)['municipality_key'] ?? null;

            return new BayernPortal(
                $app->make(Connector::class),
                is_string($key) && $key !== '' ? $key : null,
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bayernportal.php' => $this->app->configPath('bayernportal.php'),
            ], 'bayernportal-config');

            $this->commands([BuildMunicipalityIndexCommand::class]);
        }
    }

    /**
     * @param  array<array-key, mixed>  $config
     */
    private static function scraper(Application $app, array $config): Connector
    {
        $store = self::cacheStore($app, $config);
        $options = ScraperOptions::fromConfig($config);
        $scraper = is_array($config['scraper'] ?? null) ? $config['scraper'] : [];
        $indexPath = is_string($scraper['index_path'] ?? null) && $scraper['index_path'] !== '' ? $scraper['index_path'] : MunicipalityIndex::BUNDLED;

        $connector = new ScrapingConnector(
            new PortalClient($app->make(Factory::class), $options, new Throttle($options->throttleMilliseconds, $store)),
            new MunicipalityIndex($indexPath),
            // Learned facts (portal id spellings, localisation) are kept forever,
            // in memory when no cache is configured.
            new ScraperState($store ?? new CacheRepository(new ArrayStore)),
        );

        $cached = self::cached($app, $config, $connector);
        $connector->useForSubrequests($cached);

        return $cached;
    }

    /**
     * Wrap a connector in the response cache when it is enabled.
     *
     * @param  array<array-key, mixed>  $config
     */
    private static function cached(Application $app, array $config, Connector $connector): Connector
    {
        $store = self::cacheStore($app, $config);

        if ($store === null) {
            return $connector;
        }

        $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];
        $prefix = is_string($cache['prefix'] ?? null) && $cache['prefix'] !== '' ? $cache['prefix'] : 'bayernportal';

        return new CachingConnector(
            $connector,
            $store,
            is_numeric($cache['ttl'] ?? null) ? max(1, (int) $cache['ttl']) : 86400,
            $prefix,
        );
    }

    /**
     * The configured cache store, or null when caching is disabled.
     *
     * @param  array<array-key, mixed>  $config
     */
    private static function cacheStore(Application $app, array $config): ?Repository
    {
        $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];

        if (! filter_var($cache['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $store = is_string($cache['store'] ?? null) && $cache['store'] !== '' ? $cache['store'] : null;

        return $app->make(CacheFactory::class)->store($store);
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function config(Application $app): array
    {
        $config = $app->make(ConfigRepository::class)->get('bayernportal', []);

        return is_array($config) ? $config : [];
    }
}
