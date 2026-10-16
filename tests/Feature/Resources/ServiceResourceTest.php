<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Data\Service;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Enums\ContactAssignmentMode;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('lists services', function (): void {
    fakeApi('services');

    $services = BayernPortal::services()->list();

    expect($services)->toHaveCount(2)
        ->and($services->first())->toBeInstanceOf(Service::class);

    assertRequested('leistungen');
});

it('finds a service localised for a municipality', function (): void {
    fakeApi('service');

    $service = BayernPortal::services()->find(1010, '09162000');

    expect($service->name)->toBe('Personalausweis beantragen');

    assertRequested('leistungen/1010?gemeindekennziffer=09162000');
});

it('lists service descriptions', function (): void {
    fakeApi('service-descriptions');

    $descriptions = BayernPortal::serviceDescriptions()->list('09162000', withInternalIds: true);

    expect($descriptions)->toHaveCount(2)
        ->and($descriptions->last()?->name)->toBe('Wohnsitz anmelden');

    assertRequested('leistungsbeschreibungen?gemeindekennziffer=09162000&mitIDallgemeineDaten=true&mitRegionalenErgaenzungen=false');
});

it('finds a service description with responsibilities', function (): void {
    fakeApi('service-description');

    $description = BayernPortal::serviceDescriptions()->find(
        1010,
        municipalityKey: '09162000',
        withSecureContactForm: true,
        contactAssignments: ContactAssignmentMode::PreferDirect,
    );

    expect($description)->toBeInstanceOf(ServiceDescription::class)
        ->and($description->responsibilities[0]->name)->toBe('Kreisverwaltungsreferat München');

    assertRequested('leistungsbeschreibungen/1010?behoerdenAnsprechpartnerZuordnungen=DIREKTBEVORZUGT&gemeindekennziffer=09162000&mitIDallgemeineDaten=false&mitRegionalenErgaenzungen=true&sicheresKontaktformular=true');
});
