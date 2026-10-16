<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('lists life situations and builds the tree', function (): void {
    $situations = BayernPortal::lifeSituations()->list();
    $tree = BayernPortal::lifeSituations()->tree(onlyWithServices: true);

    expect($situations)->not->toBeEmpty()
        ->and($situations->first()?->id)->toMatch('/^\d+$/')
        ->and($tree)->not->toBeEmpty();
});

it('finds a life situation', function (): void {
    $id = BayernPortal::lifeSituations()->list()->first()?->id;

    expect(BayernPortal::lifeSituations()->find((string) $id)->id)->toBe($id);
});
