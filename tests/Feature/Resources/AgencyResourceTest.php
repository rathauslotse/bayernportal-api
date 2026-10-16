<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Data\Form;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\OnlineProcedure;
use Rathauslotse\BayernPortal\Data\Service;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Data\ServiceWithForms;
use Rathauslotse\BayernPortal\Data\ServiceWithOnlineProcedures;
use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;
use Rathauslotse\BayernPortal\Pagination\Page;

it('lists agencies', function (): void {
    fakeApi('agencies', ['X-Total-Count' => '3']);

    $page = BayernPortal::agencies()->list(full: true, page: 0, size: 50);

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page->items)->toHaveCount(3)
        ->and($page->items->first())->toBeInstanceOf(Agency::class)
        ->and($page->items->first()?->agencyKey)->toBe('0916200')
        ->and($page->total)->toBe(3)
        ->and($page->hasNextPage())->toBeFalse();

    assertRequested('dienststellen?full=true&page=0&size=50');
});

it('iterates all agencies lazily', function (): void {
    fakeApi('agencies', ['X-Total-Count' => '3']);

    $all = BayernPortal::agencies()->all();

    expect($all)->toBeInstanceOf(LazyCollection::class)
        ->and($all->map(fn (Agency $agency): string => $agency->name)->all())->toBe(['Landeshauptstadt München', 'Gemeinde Hallbergmoos', 'Landratsamt Freising']);

    assertRequested('dienststellen?full=false&page=0&size=100');
});

it('finds an agency', function (): void {
    fakeApi('agency');

    $agency = BayernPortal::agencies()->find('0916200');

    expect($agency->name)->toBe('Landeshauptstadt München')
        ->and($agency->agencyKey)->toBe('0916200');

    assertRequested('dienststellen/0916200');
});

it('throws not found for an empty agency response', function (): void {
    Http::fake(['*' => Http::response('', 200)]);

    BayernPortal::agencies()->find('0916200');
})->throws(NotFoundException::class);

it('lists services of an agency', function (): void {
    fakeApi('services');

    $services = BayernPortal::agencies()->services('0916200', municipalityKey: '09162000');

    expect($services)->toHaveCount(2)
        ->and($services->first())->toBeInstanceOf(Service::class);

    assertRequested('dienststellen/0916200/leistungen?gemeindekennziffer=09162000');
});

it('lists forms of an agency', function (): void {
    fakeApi('forms');

    $forms = BayernPortal::agencies()->forms('0916200');

    expect($forms)->toHaveCount(2)
        ->and($forms->first())->toBeInstanceOf(Form::class)
        ->and($forms->first()?->services)->toHaveCount(1);

    assertRequested('dienststellen/0916200/formulare?gruppiertNachLeistungen=false');
});

it('lists forms grouped by service', function (): void {
    fakeApi('forms-grouped');

    $groups = BayernPortal::agencies()->formsByService('0916200', '09162000');

    expect($groups)->toHaveCount(2)
        ->and($groups->first())->toBeInstanceOf(ServiceWithForms::class)
        ->and($groups->first()?->forms[0]->title)->toBe('Antrag auf Ausstellung eines Personalausweises');

    assertRequested('dienststellen/0916200/formulare?gemeindekennziffer=09162000&gruppiertNachLeistungen=true');
});

it('lists online procedures of an agency', function (): void {
    fakeApi('online-procedures');

    $procedures = BayernPortal::agencies()->onlineProcedures('0916200');

    expect($procedures)->toHaveCount(1)
        ->and($procedures->first())->toBeInstanceOf(OnlineProcedure::class)
        ->and($procedures->first()?->services[0]->id)->toBe(1030);

    assertRequested('dienststellen/0916200/onlineverfahren?gruppiertNachLeistungen=false');
});

it('lists online procedures grouped by service', function (): void {
    fakeApi('online-procedures-grouped');

    $groups = BayernPortal::agencies()->onlineProceduresByService('0916200');

    expect($groups->first())->toBeInstanceOf(ServiceWithOnlineProcedures::class)
        ->and($groups->first()?->onlineProcedures[0]->title)->toBe('Elektronische Wohnsitzanmeldung');

    assertRequested('dienststellen/0916200/onlineverfahren?gruppiertNachLeistungen=true');
});

it('lists service descriptions of an agency', function (): void {
    fakeApi('service-descriptions');

    $descriptions = BayernPortal::agencies()->serviceDescriptions('0916200', '09162000', withRegionalSupplements: true, withResponsibilities: true);

    expect($descriptions)->toHaveCount(2)
        ->and($descriptions->first())->toBeInstanceOf(ServiceDescription::class);

    assertRequested('dienststellen/0916200/leistungsbeschreibungen?gemeindekennziffer=09162000&mitIDallgemeineDaten=false&mitRegionalenErgaenzungen=true&mitZustaendigkeiten=true');
});

it('lists life situations of an agency', function (): void {
    fakeApi('life-situations');

    $situations = BayernPortal::agencies()->lifeSituations('0916200');

    expect($situations)->toHaveCount(4)
        ->and($situations->first())->toBeInstanceOf(LifeSituation::class);

    assertRequested('dienststellen/0916200/lebenslagen');
});

it('surfaces the German error for agencies without services', function (): void {
    Http::fake(['*' => Http::response('Es wurde keine Dienststelle gefunden bzw. der Dienststelle sind keine Leistungen zugeordnet.', 400)]);

    try {
        BayernPortal::agencies()->lifeSituations('0000000');
        $this->fail('No exception thrown.');
    } catch (RequestException $exception) {
        expect($exception->apiMessage())->toContain('keine Dienststelle gefunden');
    }
});

it('validates agency and municipality keys before sending', function (Closure $call): void {
    Http::fake();

    expect($call)->toThrow(InvalidParameterException::class);

    Http::assertNothingSent();
})->with([
    'short agency key' => fn () => BayernPortal::agencies()->find('123'),
    'bad municipality key' => fn () => BayernPortal::agencies()->services('0916200', '0916'),
    'page size' => fn () => BayernPortal::agencies()->list(size: 500),
]);
