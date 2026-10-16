<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('lists contacts in short form', function (): void {
    fakeApi('contacts');

    $contacts = BayernPortal::contacts()->list();

    expect($contacts)->toHaveCount(2)
        ->and($contacts->first())->toBeInstanceOf(Contact::class)
        ->and($contacts->last()?->lastName)->toBe('Bauer');

    assertRequested('ansprechpartner?full=false');
});

it('paginates detailed contacts', function (): void {
    Http::fake([
        '*page=0*' => Http::response(jsonFixture('contacts'), 200, ['X-Total-Count' => '3']),
        '*page=1*' => Http::response(jsonFixture('contact'), 200, ['X-Total-Count' => '3']),
    ]);

    $page = BayernPortal::contacts()->paginate(size: 2);

    expect($page->items)->toHaveCount(2)
        ->and($page->hasNextPage())->toBeTrue()
        ->and($page->next()?->items->first()?->services)->toHaveCount(2)
        ->and(BayernPortal::contacts()->all(pageSize: 2)->map(fn (Contact $contact): int => $contact->id)->all())->toBe([9001, 9002, 9001]);

    assertRequested('ansprechpartner?full=true&page=0&size=2');
    assertRequested('ansprechpartner?full=true&page=1&size=2');
});

it('finds a contact', function (): void {
    fakeApi('contact');

    $contact = BayernPortal::contacts()->find(9001);

    expect($contact->authorityName)->toBe('Kreisverwaltungsreferat München');

    assertRequested('ansprechpartner/9001');
});

it('lists services of a contact', function (): void {
    fakeApi('authority-services');

    expect(BayernPortal::contacts()->services(9001, onlyDirectAssignments: true))->toHaveCount(3);

    assertRequested('ansprechpartner/9001/leistungen?nurDirekteLeistungszuordnungen=true');
});
