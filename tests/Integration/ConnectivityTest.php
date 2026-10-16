<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Contracts\Connector;

it('authenticates against the live API', function (): void {
    $response = app(Connector::class)->get('lebenslagen');

    expect($response->successful())->toBeTrue()
        ->and($response->json())->not->toBeEmpty();
});
