<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Enums\ContactAssignmentMode;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('lists services for a municipality and finds one', function (): void {
    $services = BayernPortal::forMunicipality(liveMunicipalityKey())->services()->list();

    expect($services)->not->toBeEmpty();

    $service = BayernPortal::services()->find($services->first()->id, liveMunicipalityKey());

    expect($service->id)->toBe($services->first()->id)
        ->and($service->name)->not->toBeEmpty();
});

it('finds a service description with responsibilities for a municipality', function (): void {
    $service = BayernPortal::services()->list(liveMunicipalityKey())->first();

    $description = BayernPortal::serviceDescriptions()->find(
        $service->id,
        liveMunicipalityKey(),
        withSecureContactForm: true,
        contactAssignments: ContactAssignmentMode::PreferDirect,
    );

    expect($description->id)->toBe($service->id)
        ->and($description->name)->not->toBeEmpty();
});

it('lists all service descriptions', function (): void {
    requiresHeavyRequests();

    expect(BayernPortal::serviceDescriptions()->list(liveMunicipalityKey()))->not->toBeEmpty();
});
