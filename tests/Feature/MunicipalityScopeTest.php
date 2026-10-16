<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\BayernPortal as Client;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

it('sends the scoped municipality key to localisable endpoints', function (Closure $call, string $expected): void {
    Http::fake(['*' => Http::response(jsonFixture('service'))]);

    $call(BayernPortal::forMunicipality('09178124'));

    assertRequested($expected);
})->with([
    'services' => [fn (Client $c) => $c->services()->list(), 'leistungen?gemeindekennziffer=09178124'],
    'service' => [fn (Client $c) => $c->services()->find(1010), 'leistungen/1010?gemeindekennziffer=09178124'],
    'descriptions' => [fn (Client $c) => $c->serviceDescriptions()->list(), 'leistungsbeschreibungen?gemeindekennziffer=09178124&mitIDallgemeineDaten=false&mitRegionalenErgaenzungen=false'],
    'agency services' => [fn (Client $c) => $c->agencies()->services('0917812'), 'dienststellen/0917812/leistungen?gemeindekennziffer=09178124'],
    'agency forms' => [fn (Client $c) => $c->agencies()->forms('0917812'), 'dienststellen/0917812/formulare?gemeindekennziffer=09178124&gruppiertNachLeistungen=false'],
]);

it('lets an explicit key win over the scope', function (): void {
    fakeApi('services');

    BayernPortal::forMunicipality('09178124')->services()->list('09162000');

    assertRequested('leistungen?gemeindekennziffer=09162000');
});

it('does not send the key to endpoints without localisation', function (): void {
    fakeApi('life-situations');

    BayernPortal::forMunicipality('09178124')->lifeSituations()->list();
    BayernPortal::forMunicipality('09178124')->agencies()->lifeSituations('0917812');

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'gemeindekennziffer'));
});

it('uses the configured default municipality key', function (): void {
    config()->set('bayernportal.municipality_key', '09162000');
    app()->forgetInstance(Client::class);
    BayernPortal::clearResolvedInstances();
    fakeApi('services');

    BayernPortal::services()->list();

    assertRequested('leistungen?gemeindekennziffer=09162000');
});

it('can drop the scope again', function (): void {
    fakeApi('services');

    BayernPortal::forMunicipality('09178124')->forMunicipality(null)->services()->list();

    assertRequested('leistungen');
});
