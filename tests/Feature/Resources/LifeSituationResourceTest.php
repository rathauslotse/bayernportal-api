<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\LifeSituationNode;
use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;
use Rathauslotse\BayernPortal\Resources\LifeSituationResource;

it('lists life situations', function (): void {
    fakeApi('life-situations');

    expect(BayernPortal::lifeSituations()->list(onlyWithServices: true))->toHaveCount(4);

    assertRequested('lebenslagen?leistungenZugeordnet=true');
});

it('builds the life situation tree', function (): void {
    fakeApi('life-situations');

    $tree = BayernPortal::lifeSituations()->tree();

    expect($tree)->toHaveCount(2)
        ->and($tree->first())->toBeInstanceOf(LifeSituationNode::class)
        ->and($tree->first()?->lifeSituation->name)->toBe('Persönliche Dokumente')
        ->and(array_map(fn (LifeSituation $child): string => $child->name, $tree->first()->children ?? []))->toBe(['Ausweise und Dokumente', 'Umzug'])
        ->and($tree->last()?->children)->toBe([]);

    assertRequested('lebenslagen');
});

it('keeps orphaned sub-categories at the top level', function (): void {
    $tree = LifeSituationResource::buildTree(new Collection([
        new LifeSituation('2', 'Orphan', parentId: '999'),
    ]));

    expect($tree)->toHaveCount(1)
        ->and($tree->first()?->lifeSituation->id)->toBe('2');
});

it('finds a life situation', function (): void {
    fakeApi('life-situation');

    $situation = BayernPortal::lifeSituations()->find('10000300');

    expect($situation->name)->toBe('Umzug')
        ->and($situation->services[0]->id)->toBe(1030);

    assertRequested('lebenslagen/10000300');
});

it('validates life situation ids', function (): void {
    BayernPortal::lifeSituations()->find('../x');
})->throws(InvalidParameterException::class);
