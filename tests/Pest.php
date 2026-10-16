<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature', 'Integration');

pest()->group('integration')->in('Integration');
