<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Data\Authority;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

/**
 * An authority that has at least one building.
 */
function liveAuthority(): Authority
{
    $authority = BayernPortal::authorities()->all(full: true, pageSize: 20)
        ->first(fn (Authority $authority): bool => $authority->buildingIds !== []);

    if (! $authority instanceof Authority) {
        test()->markTestSkipped('No authority with buildings found on the first pages.');
    }

    return $authority;
}

it('lists authorities', function (): void {
    $page = BayernPortal::authorities()->list(size: 3);

    expect($page->items)->not->toBeEmpty()
        ->and($page->total)->toBeGreaterThan(0)
        ->and($page->items->first()?->id)->toBeGreaterThan(0);
});

it('finds an authority with its secure contact form link', function (): void {
    $authority = liveAuthority();

    expect(BayernPortal::authorities()->find($authority->id, withSecureContactForm: true)->id)->toBe($authority->id);
});

it('loads a building and its contacts', function (): void {
    $authority = liveAuthority();
    $building = BayernPortal::authorities()->building($authority->id, $authority->buildingIds[0]);

    expect($building->id)->toBe($authority->buildingIds[0])
        ->and($building->name)->not->toBeEmpty();

    if ($building->contactIds !== []) {
        $contact = BayernPortal::authorities()->buildingContact($authority->id, $building->id, $building->contactIds[0]);

        expect($contact->lastName)->not->toBeEmpty();
    }
});

it('lists contacts and services of an authority', function (): void {
    $authority = liveAuthority();

    expect(BayernPortal::authorities()->contacts($authority->id))->toBeIterable()
        ->and(BayernPortal::authorities()->services($authority->id))->toBeIterable()
        ->and(BayernPortal::authorities()->services($authority->id, onlyDirectAssignments: true))->toBeIterable();
});

it('rejects delegated services for authorities that are not district governments', function (): void {
    $authority = liveAuthority();

    try {
        expect(BayernPortal::authorities()->delegatedServices($authority->id))->toBeIterable();
    } catch (RequestException $exception) {
        expect($exception->status())->toBe(400);
    }
});
