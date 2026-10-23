<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Exceptions\UnsupportedByScraperException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

/*
 * Smoke tests against the real www.bayernportal.de. They double as an early
 * warning when the website's markup changes. About 17 throttled requests.
 */

const LIVE_MUNICIPALITY = '09184123'; // Stadt Haar

it('reads a municipality agency', function (): void {
    $agency = BayernPortal::agencies()->find('9184123');

    expect($agency->name)->toBe('Stadt Haar')
        ->and($agency->email)->not->toBeNull()
        ->and($agency->buildingIds)->not->toBeEmpty();

    $building = BayernPortal::authorities()->building($agency->id, $agency->buildingIds[0]);

    expect($building->streetAddress?->postalCode)->toBe('85540');
});

it('lists the services of a municipality', function (): void {
    $services = BayernPortal::services()->list(LIVE_MUNICIPALITY);

    expect($services->count())->toBeGreaterThan(100)
        ->and($services->first()?->id)->toBeGreaterThan(0);
});

it('includes the services of the administrative association', function (): void {
    $services = BayernPortal::services()->list('09178115'); // Attenkirchen, member of VG Zolling

    expect($services->count())->toBeGreaterThan(300)
        ->and($services->map(fn ($service): int => $service->id)->all())->toContain(92442164491);
});

it('reads a localised service description', function (): void {
    $service = BayernPortal::services()->list(LIVE_MUNICIPALITY)
        ->first(fn ($service): bool => str_starts_with($service->name, 'Meldebescheinigung'));

    expect($service)->not->toBeNull();

    $description = BayernPortal::serviceDescriptions()->find($service->id, LIVE_MUNICIPALITY);

    expect($description->description)->not->toBeNull()
        ->and($description->responsibilities)->not->toBeEmpty()
        ->and($description->responsibilities[0]->name)->toContain('Haar')
        ->and($description->updatedAt)->not->toBeNull();
});

it('reads the contacts of an authority', function (): void {
    $contacts = BayernPortal::authorities()->contacts(66552873578);

    expect($contacts)->not->toBeEmpty()
        ->and($contacts->first()?->lastName)->not->toBe('');
});

it('reads a life situation', function (): void {
    $situation = BayernPortal::lifeSituations()->find('781175888731167');

    expect($situation->parentId)->toBe('781142333177167')
        ->and($situation->services)->not->toBeEmpty();
});

it('refuses unsupported calls without a request', function (): void {
    BayernPortal::agencies()->list();
})->throws(UnsupportedByScraperException::class);
