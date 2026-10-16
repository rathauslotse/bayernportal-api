<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('paginates detailed contacts', function (): void {
    $page = BayernPortal::contacts()->paginate(size: 3);

    expect($page->items)->not->toBeEmpty()
        ->and($page->total)->toBeGreaterThan(0);
});

it('finds a contact and its services', function (): void {
    $first = BayernPortal::contacts()->paginate(size: 1)->items->first();

    expect($first)->not->toBeNull();

    $contact = BayernPortal::contacts()->find($first->id);

    expect($contact->id)->toBe($first->id)
        ->and(BayernPortal::contacts()->services($contact->id))->toBeIterable();
});

it('lists all contacts in short form', function (): void {
    requiresHeavyRequests();

    expect(BayernPortal::contacts()->list())->not->toBeEmpty();
});
