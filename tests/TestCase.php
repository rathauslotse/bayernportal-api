<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rathauslotse\BayernPortal\BayernPortalServiceProvider;

abstract class TestCase extends Orchestra
{
    public const string BASE_URL = 'https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/';

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [BayernPortalServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bayernportal.username', 'test-user');
        $app['config']->set('bayernportal.password', 'test-secret');
        $app['config']->set('bayernportal.retry.sleep_ms', 0);
    }
}
