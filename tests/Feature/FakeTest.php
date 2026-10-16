<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\AssertionFailedError;
use Rathauslotse\BayernPortal\BayernPortal as Client;
use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;
use Rathauslotse\BayernPortal\Testing\BayernPortalFake;

it('answers every endpoint with a default fixture', function (Closure $call): void {
    BayernPortal::fake();

    $result = $call(BayernPortal::getFacadeRoot());

    expect($result)->not->toBeNull();

    if (is_countable($result)) {
        expect(count($result))->toBeGreaterThan(0);
    }
})->with([
    'agencies.list' => fn (Client $c) => $c->agencies()->list(),
    'agencies.all' => fn (Client $c) => $c->agencies()->all()->all(),
    'agencies.find' => fn (Client $c) => $c->agencies()->find('0916200'),
    'agencies.services' => fn (Client $c) => $c->agencies()->services('0916200'),
    'agencies.forms' => fn (Client $c) => $c->agencies()->forms('0916200'),
    'agencies.formsByService' => fn (Client $c) => $c->agencies()->formsByService('0916200'),
    'agencies.onlineProcedures' => fn (Client $c) => $c->agencies()->onlineProcedures('0916200'),
    'agencies.onlineProceduresByService' => fn (Client $c) => $c->agencies()->onlineProceduresByService('0916200'),
    'agencies.serviceDescriptions' => fn (Client $c) => $c->agencies()->serviceDescriptions('0916200'),
    'agencies.lifeSituations' => fn (Client $c) => $c->agencies()->lifeSituations('0916200'),
    'authorities.list' => fn (Client $c) => $c->authorities()->list(),
    'authorities.find' => fn (Client $c) => $c->authorities()->find(4711),
    'authorities.building' => fn (Client $c) => $c->authorities()->building(4711, 801),
    'authorities.buildingContact' => fn (Client $c) => $c->authorities()->buildingContact(4711, 801, 9001),
    'authorities.contacts' => fn (Client $c) => $c->authorities()->contacts(4711),
    'authorities.services' => fn (Client $c) => $c->authorities()->services(4711),
    'authorities.delegatedServices' => fn (Client $c) => $c->authorities()->delegatedServices(4711),
    'contacts.list' => fn (Client $c) => $c->contacts()->list(),
    'contacts.paginate' => fn (Client $c) => $c->contacts()->paginate(),
    'contacts.find' => fn (Client $c) => $c->contacts()->find(9001),
    'contacts.services' => fn (Client $c) => $c->contacts()->services(9001),
    'services.list' => fn (Client $c) => $c->services()->list(),
    'services.find' => fn (Client $c) => $c->services()->find(1010),
    'serviceDescriptions.list' => fn (Client $c) => $c->serviceDescriptions()->list(),
    'serviceDescriptions.find' => fn (Client $c) => $c->serviceDescriptions()->find(1010),
    'lifeSituations.list' => fn (Client $c) => $c->lifeSituations()->list(),
    'lifeSituations.tree' => fn (Client $c) => $c->lifeSituations()->tree(),
    'lifeSituations.find' => fn (Client $c) => $c->lifeSituations()->find('10000300'),
]);

it('works without credentials', function (): void {
    config()->set('bayernportal.username', null);
    BayernPortal::fake();

    expect(BayernPortal::agencies()->find('0916200'))->toBeInstanceOf(Agency::class);
});

it('uses custom responses by path pattern', function (): void {
    $fake = BayernPortal::fake([
        'leistungen/1010' => ['leistung' => ['id' => 1010, 'bezeichnung' => 'Eigene Leistung']],
        'dienststellen/*/lebenslagen' => BayernPortalFake::error(400, 'Keine Leistungen zugeordnet.'),
        'lebenslagen/*' => fn (Request $request) => BayernPortalFake::json(['lebenslage' => ['id' => '1', 'bezeichnung' => basename($request->url())]]),
        'behoerden' => BayernPortalFake::json(BayernPortalFake::fixture('authorities'), totalCount: 2),
    ]);

    expect(BayernPortal::services()->find(1010)->name)->toBe('Eigene Leistung')
        ->and(BayernPortal::services()->find(1030)->name)->toBe('Personalausweis beantragen')
        ->and(BayernPortal::lifeSituations()->find('10000300')->name)->toBe('10000300')
        ->and(BayernPortal::authorities()->list()->total)->toBe(2)
        ->and(fn () => BayernPortal::agencies()->lifeSituations('0916200'))->toThrow(RequestException::class, 'Keine Leistungen');

    $fake->assertRequested('leistungen/1010');
    $fake->assertRequested('leistungen/*');
    $fake->assertRequestCount(5);
});

it('accepts string and empty custom responses and can be extended', function (): void {
    $fake = BayernPortal::fake(['leistungen' => '{"leistungen":{"leistung":[{"id":1,"bezeichnung":"A"}]}}']);
    $fake->respondWith(['leistungen/1' => null]);

    expect(BayernPortal::services()->list()->first()?->name)->toBe('A')
        ->and(fn () => BayernPortal::services()->find(1))->toThrow(NotFoundException::class);
});

it('answers unknown paths with 404', function (): void {
    BayernPortal::fake();

    app(Rathauslotse\BayernPortal\Contracts\Connector::class)->get('unbekannt');
})->throws(NotFoundException::class, 'No BayernPortal fake response');

it('asserts requests with query parameters', function (): void {
    $fake = BayernPortal::fake();

    BayernPortal::forMunicipality('09162000')->services()->list();

    $fake->assertRequested('leistungen', ['gemeindekennziffer' => '09162000']);
    $fake->assertNotRequested('leistungen', ['gemeindekennziffer' => '09178124']);
    $fake->assertNotRequested('lebenslagen');

    expect(fn () => $fake->assertRequested('lebenslagen'))->toThrow(AssertionFailedError::class, 'No BayernPortal request to [lebenslagen]')
        ->and(fn () => $fake->assertRequested('leistungen', ['gemeindekennziffer' => '1']))->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertNothingRequested())->toThrow(AssertionFailedError::class);
});

it('asserts that nothing was requested', function (): void {
    BayernPortal::fake()->assertNothingRequested();
});

it('only counts requests to the BayernPortal API', function (): void {
    $fake = BayernPortal::fake();
    Http::get('https://example.org/other');

    $fake->assertNothingRequested();
});

it('serves the grouped or ungrouped fixture depending on the query', function (): void {
    BayernPortal::fake();

    expect(BayernPortal::agencies()->formsByService('0916200')->first()?->forms)->not->toBeEmpty()
        ->and(BayernPortal::agencies()->forms('0916200')->first()?->services)->not->toBeEmpty();
});

it('lists and loads bundled fixtures', function (): void {
    expect(BayernPortalFake::fixtures())->toContain('agency', 'service-description', 'life-situations')
        ->and(BayernPortalFake::fixture('agency'))->toHaveKey('dienststelle');

    BayernPortalFake::fixture('nope');
})->throws(InvalidArgumentException::class, 'Unknown BayernPortal fixture [nope]');
