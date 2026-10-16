<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\Data\Authority;
use Rathauslotse\BayernPortal\Data\Building;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Data\ServiceReference;
use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('lists authorities', function (): void {
    fakeApi('authorities', ['X-Total-Count' => '2']);

    $page = BayernPortal::authorities()->list(page: 0, size: 10);

    expect($page->items)->toHaveCount(2)
        ->and($page->items->first())->toBeInstanceOf(Authority::class)
        ->and($page->total)->toBe(2);

    assertRequested('behoerden?full=false&page=0&size=10');
});

it('iterates all authorities lazily', function (): void {
    fakeApi('authorities', ['X-Total-Count' => '2']);

    expect(BayernPortal::authorities()->all(full: true)->count())->toBe(2);

    assertRequested('behoerden?full=true&page=0&size=100');
});

it('finds an authority', function (): void {
    fakeApi('authority');

    $authority = BayernPortal::authorities()->find(4711, withSecureContactForm: true);

    expect($authority->id)->toBe(4711)
        ->and($authority->secureContactFormUrl)->toBe('https://formulare.example/kontakt/4711');

    assertRequested('behoerden/4711?sicheresKontaktformular=true');
});

it('loads a building', function (): void {
    fakeApi('building');

    $building = BayernPortal::authorities()->building(4711, 801);

    expect($building)->toBeInstanceOf(Building::class)
        ->and($building->name)->toBe('Bürgerbüro Ruppertstraße');

    assertRequested('behoerden/4711/gebaeude/801');
});

it('loads a building contact', function (): void {
    fakeApi('building-contact');

    $contact = BayernPortal::authorities()->buildingContact(4711, 801, 9001);

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->fullName())->toBe('Dr. Maria Huber');

    assertRequested('behoerden/4711/gebaeude/801/ansprechpartner/9001');
});

it('lists contacts of an authority', function (): void {
    fakeApi('contacts');

    expect(BayernPortal::authorities()->contacts(4711))->toHaveCount(2);

    assertRequested('behoerden/4711/ansprechpartner');
});

it('lists services of an authority', function (?bool $direct, string $query): void {
    fakeApi('authority-services');

    $services = BayernPortal::authorities()->services(4711, onlyDirectAssignments: $direct);

    expect($services)->toHaveCount(3)
        ->and($services->first())->toEqual(new ServiceReference(1010, 'Personalausweis beantragen'));

    assertRequested('behoerden/4711/leistungen'.$query);
})->with([
    [null, ''],
    [true, '?nurDirekteLeistungszuordnungen=true'],
    [false, '?nurDirekteLeistungszuordnungen=false'],
]);

it('lists delegated services of a district government', function (): void {
    fakeApi('authority-services');

    expect(BayernPortal::authorities()->delegatedServices(100))->toHaveCount(3);

    assertRequested('behoerden/100/abgegebene-leistungen');
});

it('reports missing permission for delegated services', function (): void {
    Http::fake(['*' => Http::response('Sie haben keine Berechtigung, diese Daten abzurufen.', 400)]);

    BayernPortal::authorities()->delegatedServices(4711);
})->throws(RequestException::class, 'keine Berechtigung');

it('validates ids before sending', function (Closure $call, string $message): void {
    Http::fake();

    expect($call)->toThrow(InvalidParameterException::class, $message);

    Http::assertNothingSent();
})->with([
    [fn () => BayernPortal::authorities()->find(0), 'authority id'],
    [fn () => BayernPortal::authorities()->building(1, -1), 'building id'],
    [fn () => BayernPortal::authorities()->buildingContact(1, 1, 0), 'contact id'],
]);
