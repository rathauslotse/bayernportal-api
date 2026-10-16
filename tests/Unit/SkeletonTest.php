<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\BayernPortalServiceProvider;

it('registers the service provider', function (): void {
    expect(app()->getProviders(BayernPortalServiceProvider::class))->not->toBeEmpty();
});
