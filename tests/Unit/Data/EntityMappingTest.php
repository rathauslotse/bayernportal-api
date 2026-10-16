<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Data\Authority;
use Rathauslotse\BayernPortal\Data\Building;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Data\Form;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\OnlineProcedure;
use Rathauslotse\BayernPortal\Data\Service;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Data\ServiceReference;
use Rathauslotse\BayernPortal\Data\ServiceWithForms;
use Rathauslotse\BayernPortal\Data\ServiceWithOnlineProcedures;
use Rathauslotse\BayernPortal\Enums\LifeSituationCategory;
use Rathauslotse\BayernPortal\Enums\ServiceStatus;
use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Enums\WrittenFormRequirement;
use Rathauslotse\BayernPortal\Support\Payload;

it('maps an agency', function (): void {
    $agency = Agency::fromPayload(Payload::single(jsonFixture('agency'), 'dienststelle'));

    expect($agency)->toBeInstanceOf(Authority::class)
        ->and($agency->id)->toBe(4711)
        ->and($agency->name)->toBe('Landeshauptstadt München')
        ->and($agency->nameSuffix)->toBe('Kreisverwaltungsreferat')
        ->and($agency->fullName())->toBe('Landeshauptstadt München – Kreisverwaltungsreferat')
        ->and($agency->agencyKey)->toBe('0916200')
        ->and($agency->branchNumber)->toBe(0)
        ->and($agency->isBranchOffice())->toBeFalse()
        ->and($agency->type)->toBe('Gemeinde')
        ->and($agency->group)->toBe('Kommunalverwaltung')
        ->and($agency->sortOrder)->toBe(10)
        ->and($agency->summary)->toBe('Stadtverwaltung der Landeshauptstadt München')
        ->and($agency->description)->toContain('kommunalen Angelegenheiten')
        ->and($agency->email)->toBe('kvr@muenchen.example')
        ->and($agency->deMail)->toBe('kvr@muenchen.de-mail.example')
        ->and($agency->website)->toBe('https://stadt.muenchen.example')
        ->and($agency->logo?->mimeType)->toBe('image/png')
        ->and($agency->logo?->title)->toBe('Wappen München')
        ->and($agency->logoWithText)->toBeNull()
        ->and($agency->subordinateAuthorityIds)->toBe([4712, 4713])
        ->and($agency->buildingIds)->toBe([801, 802])
        ->and($agency->secureContactFormUrl)->toBe('https://formulare.example/kontakt/4711')
        ->and($agency->administrativeAssociation)->toBeNull()
        ->and($agency->raw['dienststellenschluessel'])->toBe('0916200');
});

it('maps a branch office', function (): void {
    $items = Payload::items(jsonFixture('agencies'), 'dienststellen', 'dienststelle');
    $branch = Agency::fromPayload($items[2]);

    expect($branch->isBranchOffice())->toBeTrue()
        ->and($branch->branchNumber)->toBe(1)
        ->and(Agency::fromPayload($items[1])->administrativeAssociation)->toBe('Verwaltungsgemeinschaft Beispiel')
        ->and(Agency::fromPayload($items[1])->fullName())->toBe('Gemeinde Hallbergmoos');
});

it('maps an authority with prefixed attributes', function (): void {
    $authority = Authority::fromArray(jsonFixture('authority')['behoerde']);

    expect($authority->id)->toBe(4711)
        ->and($authority->name)->toBe('Kreisverwaltungsreferat München')
        ->and($authority->buildingIds)->toBe([801])
        ->and($authority->subordinateAuthorityIds)->toBe([])
        ->and($authority->logo)->toBeNull()
        ->and($authority->logoWithText?->mimeType)->toBe('image/png');
});

it('tolerates sparse payloads', function (): void {
    $authority = Authority::fromArray([]);
    $agency = Agency::fromArray(['bezeichnung' => 'X']);

    expect($authority->id)->toBe(0)
        ->and($authority->name)->toBe('')
        ->and($authority->type)->toBe('')
        ->and($agency->agencyKey)->toBe('')
        ->and($agency->name)->toBe('X');
});

it('maps a building', function (): void {
    $building = Building::fromArray(jsonFixture('building')['BehoerdenGebaeude']);

    expect($building->id)->toBe(801)
        ->and($building->authorityId)->toBe(4711)
        ->and($building->name)->toBe('Bürgerbüro Ruppertstraße')
        ->and((string) $building->streetAddress)->toBe('Ruppertstraße 19, 80337 München')
        ->and((string) $building->postalAddress)->toBe('Postfach 20 13 13, 80313 München')
        ->and((string) $building->phone)->toBe('+49 89 233-96000')
        ->and((string) $building->fax)->toBe('+49 89 233-96001')
        ->and($building->sortOrder)->toBe(1)
        ->and($building->logo)->toBeNull()
        ->and($building->contactIds)->toBe([9001, 9002])
        ->and($building->openingHours?->byAppointment)->toBeFalse()
        ->and($building->openingHours?->specialHours)->toBe('An Feiertagen geschlossen.')
        ->and($building->openingHours?->timeSlots)->toHaveCount(3)
        ->and($building->openingHours?->forDay(Weekday::Tuesday)[0]->format())->toBe('08:30–12:00, 14:00–18:00');
});

it('maps a building contact', function (): void {
    $contact = Contact::fromArray(jsonFixture('building-contact')['ansprechpartner']);

    expect($contact->id)->toBe(9001)
        ->and($contact->fullName())->toBe('Dr. Maria Huber')
        ->and($contact->salutation)->toBe('Frau')
        ->and($contact->officialTitle)->toBe('Verwaltungsamtfrau')
        ->and($contact->role)->toBe('Sachgebietsleitung')
        ->and($contact->jobTitle)->toBe('Leitung Bürgerbüro')
        ->and($contact->room)->toBe('2.14')
        ->and($contact->authorityId)->toBe(4711)
        ->and($contact->buildingId)->toBe(801)
        ->and($contact->authorityName)->toBeNull()
        ->and((string) $contact->phone)->toBe('+49 89 233-96010')
        ->and((string) $contact->mobile)->toBe('+49 151 12345678')
        ->and($contact->fax)->toBeNull()
        ->and($contact->consultationHours?->byAppointment)->toBeTrue()
        ->and($contact->consultationHours?->specialHours)->toBeNull()
        ->and($contact->consultationHours?->forDay(Weekday::Wednesday))->toHaveCount(1)
        ->and($contact->services)->toBe([]);
});

it('maps a contact with services', function (): void {
    $contact = Contact::fromPayload(Payload::single(jsonFixture('contact'), 'ansprechpartner', 'ap'));

    expect($contact->authorityName)->toBe('Kreisverwaltungsreferat München')
        ->and($contact->buildingName)->toBe('Bürgerbüro Ruppertstraße')
        ->and($contact->services)->toHaveCount(2)
        ->and($contact->services[0])->toEqual(new ServiceReference(1010, 'Personalausweis beantragen'));
});

it('maps services', function (): void {
    $services = array_map(Service::fromPayload(...), Payload::items(jsonFixture('services'), 'leistungen', 'leistung'));

    expect($services)->toHaveCount(2)
        ->and($services[0]->id)->toBe(1010)
        ->and($services[0]->name)->toBe('Personalausweis beantragen')
        ->and($services[0]->url)->toBe('https://www.bayernportal.de/dokumente/leistung/1010')
        ->and($services[0]->synonyms)->toBe(['Perso', 'Ausweis'])
        ->and($services[0]->lifeSituations[0]->id)->toBe('10000100')
        ->and($services[0]->lifeSituations[0]->name)->toBe('Ausweise und Dokumente')
        ->and($services[0]->lastModifiedAt?->toIso8601String())->toBe('2026-08-12T10:15:00+02:00')
        ->and($services[1]->synonyms)->toBe(['Ummeldung'])
        ->and($services[1]->lifeSituations)->toHaveCount(1);
});

it('maps a full service description', function (): void {
    $description = ServiceDescription::fromPayload(Payload::single(jsonFixture('service-description'), 'leistungsbeschreibung'));

    expect($description)->toBeInstanceOf(Service::class)
        ->and($description->id)->toBe(1010)
        ->and($description->name)->toBe('Personalausweis beantragen')
        ->and($description->status)->toBe(ServiceStatus::Live)
        ->and($description->isLive())->toBeTrue()
        ->and($description->summary)->toContain('amtliches Ausweisdokument')
        ->and($description->prerequisites)->toContain('Staatsangehörigkeit')
        ->and($description->procedure)->toContain('Termin')
        ->and($description->notes)->toContain('drei Wochen')
        ->and($description->deadlines)->toBe('<p>Keine</p>')
        ->and($description->processingTime)->toBe('ca. 3 Wochen')
        ->and($description->fees)->toBe('37,00 Euro (ab 24 Jahren)')
        ->and($description->updatedAt?->toDateString())->toBe('2026-07-01')
        ->and($description->responsibleAuthority)->toStartWith('Bayerisches Staatsministerium')
        ->and($description->regionalSupplementIds)->toBe([81]);

    expect($description->requiredDocuments)->toHaveCount(2)
        ->and($description->requiredDocuments[0]->title)->toBe('Biometrisches Lichtbild')
        ->and($description->requiredDocuments[0]->service)->toBeNull()
        ->and($description->requiredDocuments[0]->id)->toBe(31)
        ->and($description->requiredDocuments[1]->service?->id)->toBe(1020)
        ->and($description->requiredDocuments[1]->description)->toBe('Falls vorhanden');

    expect($description->forms)->toHaveCount(1)
        ->and($description->forms[0]->writtenFormRequirement)->toBe(WrittenFormRequirement::SignatureAndPersonalAppearanceRequired)
        ->and($description->forms[0]->prefillable)->toBeTrue()
        ->and($description->onlineProcedures)->toHaveCount(1)
        ->and($description->onlineProcedures[0]->title)->toBe('Online-Terminvereinbarung')
        ->and($description->legalBases[0]->title)->toBe('§ 9 Personalausweisgesetz (PAuswG)')
        ->and($description->legalBases[0]->url)->toContain('gesetze-im-internet')
        ->and($description->legalRemedy?->summary)->toBe('Widerspruch oder Klage')
        ->and($description->legalRemedy?->service?->name)->toBe('Widerspruch einlegen')
        ->and($description->links[0]->title)->toBe('Informationen des BMI')
        ->and($description->relatedServices)->toHaveCount(2)
        ->and($description->relatedServices[0]->direct)->toBeTrue()
        ->and($description->relatedServices[1]->direct)->toBeFalse();

    $responsibility = $description->responsibilities[0];

    expect($description->responsibilities)->toHaveCount(1)
        ->and($responsibility->authorityId)->toBe(4711)
        ->and($responsibility->name)->toBe('Kreisverwaltungsreferat München')
        ->and($responsibility->portalUrl)->toBe('https://www.bayernportal.de/dokumente/behoerde/4711')
        ->and($responsibility->secureContactFormUrl)->toBe('https://formulare.example/kontakt/4711')
        ->and($responsibility->email)->toBe('kvr@muenchen.example')
        ->and($responsibility->buildings[0]->id)->toBe(801)
        ->and((string) $responsibility->buildings[0]->streetAddress)->toBe('Ruppertstraße 19, 80337 München')
        ->and((string) $responsibility->buildings[0]->postalAddress)->toBe('Postfach 20 13 13, 80313 München')
        ->and($responsibility->buildings[0]->phone)->toBe('+49 89 233-96000')
        ->and($responsibility->buildings[0]->contactIds)->toBe([9001]);
});

it('maps a minimal service description', function (): void {
    $description = ServiceDescription::fromArray(['id' => 1, 'bezeichnung' => 'X', 'status' => 'unknown']);

    expect($description->status)->toBeNull()
        ->and($description->isLive())->toBeFalse()
        ->and($description->legalRemedy)->toBeNull()
        ->and($description->forms)->toBe([])
        ->and($description->responsibilities)->toBe([]);
});

it('maps forms grouped by service', function (): void {
    $groups = array_map(ServiceWithForms::fromPayload(...), Payload::items(jsonFixture('forms-grouped'), 'formulare', 'leistungMitFormularen'));

    expect($groups)->toHaveCount(2)
        ->and($groups[0]->service->id)->toBe(1010)
        ->and($groups[0]->service->url)->toBe('https://www.bayernportal.de/dokumente/leistung/1010')
        ->and($groups[0]->forms[0]->id)->toBe(41)
        ->and($groups[1]->forms)->toHaveCount(1)
        ->and($groups[1]->forms[0]->writtenFormRequirement)->toBe(WrittenFormRequirement::SignatureRequired)
        ->and($groups[1]->forms[0]->writtenFormRequirementDescription)->toBe('Unterschrift erforderlich')
        ->and($groups[1]->forms[0]->prefillable)->toBeFalse();
});

it('maps forms with their services', function (): void {
    $forms = array_map(Form::fromPayload(...), Payload::items(jsonFixture('forms'), 'formulare', 'formular'));

    expect($forms)->toHaveCount(2)
        ->and($forms[0]->prefillable)->toBeTrue()
        ->and($forms[0]->services[0]->name)->toBe('Personalausweis beantragen')
        ->and($forms[1]->writtenFormRequirement)->toBe(WrittenFormRequirement::NoSignatureRequired)
        ->and($forms[1]->services)->toHaveCount(2)
        ->and(Form::fromArray(['kurzbeschreibung' => 'X'])->writtenFormRequirement)->toBeNull();
});

it('maps online procedures', function (): void {
    $groups = array_map(ServiceWithOnlineProcedures::fromPayload(...), Payload::items(jsonFixture('online-procedures-grouped'), 'onlineverfahren', 'leistungMitOnlineverfahren'));
    $procedures = array_map(OnlineProcedure::fromPayload(...), Payload::items(jsonFixture('online-procedures'), 'onlineverfahren', 'onlineVerfahren'));

    expect($groups[0]->service->name)->toBe('Wohnsitz anmelden')
        ->and($groups[0]->onlineProcedures[0]->url)->toBe('https://online.example/anmeldung')
        ->and($procedures[0]->title)->toBe('Elektronische Wohnsitzanmeldung')
        ->and($procedures[0]->description)->toBe('Anmeldung mit Online-Ausweisfunktion')
        ->and($procedures[0]->services[0]->id)->toBe(1030)
        ->and(OnlineProcedure::fromArray(['kurzbeschreibung' => 'Y'])->services)->toBe([])
        ->and(ServiceWithOnlineProcedures::fromPayload(Payload::of(['onlineverfahren' => ['onlineverfahren' => [['kurzbeschreibung' => 'Z']]]]))->onlineProcedures[0]->title)->toBe('Z');
});

it('maps life situations', function (): void {
    $situations = array_map(LifeSituation::fromPayload(...), Payload::items(jsonFixture('life-situations'), 'lebenslagen', 'lebenslage'));
    $moving = LifeSituation::fromArray(jsonFixture('life-situation')['lebenslage']);

    expect($situations)->toHaveCount(4)
        ->and($situations[0]->isTopLevel())->toBeTrue()
        ->and($situations[0]->parentId)->toBeNull()
        ->and($situations[1]->parentId)->toBe('10000000')
        ->and($situations[1]->services)->toHaveCount(2)
        ->and($situations[2]->services[0]->name)->toBe('Wohnsitz anmelden')
        ->and($situations[3]->category)->toBe(LifeSituationCategory::Business)
        ->and($situations[3]->isTopLevel())->toBeTrue()
        ->and($moving->id)->toBe('10000300')
        ->and($moving->name)->toBe('Umzug')
        ->and($moving->category)->toBe(LifeSituationCategory::Citizen)
        ->and($moving->description)->toContain('Behördengänge')
        ->and($moving->synonyms)->toBe(['Wohnungswechsel', 'Ummeldung'])
        ->and($moving->updatedAt?->year)->toBe(2026);
});

it('maps service references', function (): void {
    expect(ServiceReference::fromArray(['leistungId' => '5', 'leistungBezeichnung' => 'A', 'leistungUrl' => 'u']))->toEqual(new ServiceReference(5, 'A', 'u'))
        ->and(ServiceReference::fromArray(['bezeichnung' => 'no id']))->toBeNull();
});
