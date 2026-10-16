<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Http\ConnectorOptions;
use Rathauslotse\BayernPortal\Http\HttpConnector;

class BayernPortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bayernportal.php', 'bayernportal');

        $this->app->singleton(Connector::class, static function (Application $app): Connector {
            return new HttpConnector(
                $app->make(Factory::class),
                ConnectorOptions::fromConfig(self::config($app)),
            );
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
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function config(Application $app): array
    {
        $config = $app->make(Repository::class)->get('bayernportal', []);

        return is_array($config) ? $config : [];
    }
}
