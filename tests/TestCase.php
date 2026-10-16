<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rathauslotse\BayernPortal\BayernPortalServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [BayernPortalServiceProvider::class];
    }
}
