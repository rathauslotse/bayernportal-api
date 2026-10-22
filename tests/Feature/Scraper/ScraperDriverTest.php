<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\Cache\CachingConnector;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Data\Authority;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Data\Form;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\OnlineProcedure;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Data\ServiceWithForms;
use Rathauslotse\BayernPortal\Data\ServiceWithOnlineProcedures;
use Rathauslotse\BayernPortal\Enums\LifeSituationCategory;
use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Exceptions\ScraperBlockedException;
use Rathauslotse\BayernPortal\Exceptions\ServerException;
use Rathauslotse\BayernPortal\Exceptions\UnsupportedByScraperException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;
use Rathauslotse\BayernPortal\Http\HttpConnector;
use Rathauslotse\BayernPortal\Scraper\ScrapingConnector;

beforeEach(fn () => useScraper());

const HAAR = 66552873578;

it('binds the scraping connector for the scraper driver', function (): void {
    expect(app(Connector::class))->toBeInstanceOf(ScrapingConnector::class);

    useScraper(cache: true);

    expect(app(Connector::class))->toBeInstanceOf(CachingConnector::class)
        ->and(app(Connector::class)->uncached())->toBeInstanceOf(ScrapingConnector::class);
});

it('reports whether the client uses the scraper', function (): void {
    expect(BayernPortal::usesScraper())->toBeTrue();

    useScraper(cache: true);

    expect(BayernPortal::usesScraper())->toBeTrue();

    config()->set('bayernportal.driver', 'api');
    app()->forgetInstance(Connector::class);
    app()->forgetInstance(Rathauslotse\BayernPortal\BayernPortal::class);
    BayernPortal::clearResolvedInstances();

    expect(BayernPortal::usesScraper())->toBeFalse();
});

it('keeps the API driver as default', function (): void {
    config()->set('bayernportal.driver', 'api');
    app()->forgetInstance(Connector::class);

    expect(app(Connector::class))->toBeInstanceOf(HttpConnector::class);
});

it('rejects an unknown driver', function (): void {
    config()->set('bayernportal.driver', 'carrier-pigeon');
    app()->forgetInstance(Connector::class);

    expect(fn () => app(Connector::class))->toThrow(InvalidArgumentException::class, 'carrier-pigeon');
});

it('finds an agency by agency key', function (): void {
    fakeWebsite();

    $agency = BayernPortal::agencies()->find('9184123');

    expect($agency)->toBeInstanceOf(Agency::class)
        ->and($agency->id)->toBe(HAAR)
        ->and($agency->name)->toBe('Stadt Haar')
        ->and($agency->agencyKey)->toBe('9184123')
        ->and($agency->email)->toBe('info@stadt-haar.de')
        ->and($agency->website)->toBe('http://www.stadt-haar.de')
        ->and($agency->secureContactFormUrl)->toStartWith('https://formularserver-bp.bayern.de/sichererKontakt')
        ->and($agency->buildingIds)->toHaveCount(2)
        ->and($agency->raw['gemeindekennziffer'])->toBe('09184123')
        ->and($agency->raw['hausanschriftStrasse'])->toBe('Bahnhofstr. 7');
});

it('throws not found for an agency key missing from the index', function (): void {
    fakeWebsite();

    BayernPortal::agencies()->find('9999999');
})->throws(NotFoundException::class, 'agency key 9999999');

it('reads an authority with its buildings, opening hours and contacts', function (): void {
    fakeWebsite();

    $authority = BayernPortal::authorities()->find(HAAR);
    $building = BayernPortal::authorities()->building(HAAR, $authority->buildingIds[0]);

    expect($authority)->toBeInstanceOf(Authority::class)
        ->and($authority->type)->toBe('Gemeinden')
        ->and($building->name)->toBe('Stadt Haar')
        ->and($building->streetAddress?->format())->toBe('Bahnhofstr. 7, 85540 Haar')
        ->and($building->postalAddress?->street)->toBe('Postfach 1209')
        ->and($building->openingHours?->timeSlots)->toHaveCount(5)
        ->and($building->openingHours?->timeSlots[2]->day)->toBe(Weekday::Wednesday)
        ->and($building->openingHours?->timeSlots[2]->afternoonUntil)->toBe('18:00')
        ->and($building->contactIds)->toHaveCount(1);

    $contact = BayernPortal::authorities()->buildingContact(HAAR, $building->id, $building->contactIds[0]);

    expect($contact->lastName)->toBe('Bukowski')
        ->and($contact->firstName)->toBe('Andreas')
        ->and($contact->role)->toBe('Erster Bürgermeister')
        ->and($contact->academicDegree)->toBe('Dr.')
        ->and($contact->buildingId)->toBe($building->id);
});

it('lists the contacts of an authority', function (): void {
    fakeWebsite();

    $contacts = BayernPortal::authorities()->contacts(HAAR);

    expect($contacts)->toHaveCount(2)
        ->and($contacts->first())->toBeInstanceOf(Contact::class)
        ->and($contacts->first()?->lastName)->toBe('actago GmbH')
        ->and($contacts->first()?->firstName)->toBeNull()
        ->and($contacts->first()?->phone?->format())->toBe('+49 89 46002-224')
        ->and($contacts->first()?->email)->toBe('datenschutz@actago.de');
});

it('throws not found for an unknown building or contact', function (): void {
    fakeWebsite();

    expect(fn () => BayernPortal::authorities()->building(HAAR, 1))->toThrow(NotFoundException::class)
        ->and(fn () => BayernPortal::authorities()->buildingContact(HAAR, 1, 1))->toThrow(NotFoundException::class);
});

it('lists the services of an authority', function (): void {
    fakeWebsite();

    $services = BayernPortal::authorities()->services(HAAR);

    expect($services)->toHaveCount(25)
        ->and($services->first()?->id)->toBe(75774914617)
        ->and($services->first()?->name)->toBe('Abfallentsorgung; Mitwirkung der Gemeinde');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/leistungen?rekursiv=true'));
});

it('lists all services or those of a municipality', function (): void {
    fakeWebsite();

    expect(BayernPortal::services()->list())->toHaveCount(25)
        ->and(BayernPortal::services()->list()->first()?->name)->toBe('Abendgymnasium; Anmeldung')
        ->and(BayernPortal::services()->list('09184123'))->toHaveCount(25)
        ->and(BayernPortal::agencies()->services('9184123'))->toHaveCount(25);
});

it('reads a localised service description with responsibilities and supplements', function (): void {
    fakeWebsite();

    $description = BayernPortal::serviceDescriptions()->find(971310434493, '09184123');

    expect($description)->toBeInstanceOf(ServiceDescription::class)
        ->and($description->name)->toBe('Meldebescheinigung; Beantragung')
        ->and($description->summary)->toStartWith('Eine Meldebescheinigung')
        ->and($description->fees)->toContain('5,00 EUR')
        ->and($description->requiredDocuments[0]->title)->toBe('Personalausweis oder Reisepass')
        ->and($description->onlineProcedures)->toHaveCount(3)
        ->and($description->responsibilities[0]->name)->toBe('Stadt Haar')
        ->and($description->responsibilities[0]->authorityId)->toBe(HAAR)
        ->and($description->responsibilities[0]->email)->toBe('info@stadt-haar.de')
        ->and($description->responsibilities[0]->buildings[0]->streetAddress?->city)->toBe('Haar')
        ->and($description->updatedAt?->toDateString())->toBe('2026-07-20')
        ->and($description->responsibleAuthority)->toBe('Bayerisches Staatsministerium des Innern, für Sport und Integration')
        ->and($description->raw['regionaleErgaenzungen'][0]['bezeichnung'])->toBe('Stadt Haar');

    // Localisation parameters are learned from the municipality's service list first.
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'leistung/971310434493')
        && str_contains($request->url(), 'plz=85540')
        && str_contains($request->url(), 'behoerde=66552873578')
        && str_contains($request->url(), 'gemeinde=365968738674'));
});

it('maps every section of a full service description', function (): void {
    fakeWebsite();

    $description = BayernPortal::serviceDescriptions()->find(8110751776);

    expect($description->prerequisites)->toStartWith('<ul>')
        ->and($description->procedure)->toContain('Bundesdruckerei')
        ->and($description->processingTime)->toContain('zwei Wochen')
        ->and($description->deadlines)->toBeNull()
        ->and($description->legalRemedy)->toBeNull()
        ->and($description->forms)->toHaveCount(1)
        ->and($description->forms[0])->toBeInstanceOf(Form::class)
        ->and($description->forms[0]->url)->not->toContain('caller=')
        ->and($description->legalBases[0]->description)->toBe('Ausweispflicht')
        ->and($description->links)->not->toBeEmpty()
        ->and($description->relatedServices[0]->id)->toBe(8070860553300)
        ->and($description->synonyms)->toContain('Perso')
        ->and($description->isLive())->toBeTrue();
});

it('drops regional supplements on request', function (): void {
    fakeWebsite();

    $description = BayernPortal::serviceDescriptions()->find(971310434493, '09184123', withRegionalSupplements: false);

    expect($description->raw)->not->toHaveKey('regionaleErgaenzungen');
});

it('reads a service', function (): void {
    fakeWebsite();

    $service = BayernPortal::services()->find(21664765410);

    expect($service->name)->toBe('Abfall; Entsorgung')
        ->and($service->url)->toBe('https://www.bayernportal.de/dokumente/leistung/21664765410');
});

it('aggregates the service descriptions and forms of a municipality', function (): void {
    useScraper(cache: true);
    fakeWebsite();

    $descriptions = BayernPortal::agencies()->serviceDescriptions('9184123');
    $forms = BayernPortal::agencies()->forms('9184123');
    $grouped = BayernPortal::agencies()->formsByService('9184123');
    $listed = BayernPortal::serviceDescriptions()->list('09184123');

    expect($descriptions)->toHaveCount(25)
        ->and($descriptions->first())->toBeInstanceOf(ServiceDescription::class)
        ->and($descriptions->first()?->id)->toBe(75774914617)
        ->and($forms)->toBeEmpty()
        ->and($grouped)->toBeEmpty()
        ->and($listed)->toHaveCount(25);

    // Every description was fetched once and then served from the cache.
    Http::assertSentCount(1 + 25);
});

it('groups the forms of a municipality by service', function (): void {
    fakeWebsite(['dokumente/leistung/75774914617' => 'service-description-full']);

    $grouped = BayernPortal::agencies()->formsByService('9184123');
    $forms = BayernPortal::agencies()->forms('9184123');

    expect($grouped)->not->toBeEmpty()
        ->and($grouped->first())->toBeInstanceOf(ServiceWithForms::class)
        ->and($grouped->first()?->service->id)->toBe(75774914617)
        ->and($forms->first()?->title)->toBe('Vollmacht zur Abholung des neuen Personalausweises')
        ->and($forms->first()?->services[0]->id)->toBe(75774914617);
});

it('lists the online procedures of a municipality', function (): void {
    fakeWebsite();

    $procedures = BayernPortal::agencies()->onlineProcedures('9184123');
    $grouped = BayernPortal::agencies()->onlineProceduresByService('9184123');

    expect($procedures->first())->toBeInstanceOf(OnlineProcedure::class)
        ->and($procedures->first()?->title)->toBe('Anmeldung / Ummeldung / Abmeldung von Abfallbehältern')
        ->and($procedures->first()?->services[0]->id)->toBe(75774914617)
        ->and($procedures->pluck('id')->duplicates())->toBeEmpty()
        ->and($grouped->first())->toBeInstanceOf(ServiceWithOnlineProcedures::class)
        ->and($grouped->first()?->onlineProcedures)->toHaveCount(2);
});

it('reads life situations', function (): void {
    fakeWebsite();

    $leaf = BayernPortal::lifeSituations()->find('781175888731167');
    $node = BayernPortal::lifeSituations()->find('781142333177167');

    expect($leaf)->toBeInstanceOf(LifeSituation::class)
        ->and($leaf->name)->toBe('Vor der Geburt')
        ->and($leaf->parentId)->toBe('781142333177167')
        ->and($leaf->category)->toBe(LifeSituationCategory::Citizen)
        ->and($leaf->services)->toHaveCount(6)
        ->and($node->isTopLevel())->toBeTrue()
        ->and($node->summary)->toStartWith('Sie erwarten ein Baby?');
});

it('crawls the life situation tree', function (): void {
    fakeWebsite();

    $all = BayernPortal::lifeSituations()->list();
    $withServices = BayernPortal::lifeSituations()->list(onlyWithServices: true);

    // 31 top-level situations plus the 5 children of "Geburt"; the fake serves
    // the same leaf page for every other situation.
    expect($all)->toHaveCount(36)
        ->and($all->first()?->name)->toBe('Geburt')
        ->and($withServices)->toHaveCount(35)
        ->and($withServices->every(fn (LifeSituation $situation): bool => $situation->services !== []))->toBeTrue();
});

it('retries a portal id with its leading zero', function (): void {
    fakeWebsite([
        'dokumente/leistung/47980064430' => Http::response(htmlFixture('not-found'), 400),
        'dokumente/leistung/047980064430' => 'service-description',
    ]);

    expect(BayernPortal::services()->find(47980064430)->name)->toBe('Abfall; Entsorgung');
});

it('remembers leading zeros seen in links', function (): void {
    fakeWebsite(['dokumente/leistung/47980064430' => Http::response('', 500)]);

    // The leaf page links to service 047980064430.
    BayernPortal::lifeSituations()->find('781175888731167');
    BayernPortal::services()->find(47980064430);

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'leistung/47980064430'));
});

it('throws not found for unknown documents', function (): void {
    fakeWebsite();

    BayernPortal::authorities()->find(123);
})->throws(NotFoundException::class);

it('refuses calls the website cannot serve', function (Closure $call): void {
    fakeWebsite();

    expect($call)->toThrow(UnsupportedByScraperException::class);
})->with([
    'agency list' => [fn () => BayernPortal::agencies()->list()],
    'authority list' => [fn () => BayernPortal::authorities()->list()],
    'contacts' => [fn () => BayernPortal::contacts()->list()],
    'delegated services' => [fn () => BayernPortal::authorities()->delegatedServices(HAAR)],
    'agency life situations' => [fn () => BayernPortal::agencies()->lifeSituations('9184123')],
    'all descriptions' => [fn () => BayernPortal::serviceDescriptions()->list()],
]);

it('detects rate limits and bot challenges', function (): void {
    fakeWebsite([
        'dokumente/leistung/1' => Http::response('', 429),
        'dokumente/leistung/2' => Http::response('<div class="frc-captcha" data-sitekey="x"></div>'),
    ]);

    expect(fn () => BayernPortal::services()->find(1))->toThrow(ScraperBlockedException::class)
        ->and(fn () => BayernPortal::services()->find(2))->toThrow(ScraperBlockedException::class);
});

it('maps other error responses', function (): void {
    fakeWebsite([
        'dokumente/leistung/1' => Http::response('<p>Wartung</p>', 503),
        'dokumente/leistung/2' => Http::response('<p>Ungültige Anfrage</p>', 400),
        'dokumente/behoerde/3' => Http::response('', 404),
    ]);

    expect(fn () => BayernPortal::services()->find(1))->toThrow(ServerException::class, 'Wartung')
        ->and(fn () => BayernPortal::services()->find(2))->toThrow(RequestException::class, 'Ungültige Anfrage')
        ->and(fn () => BayernPortal::authorities()->find(3))->toThrow(NotFoundException::class);
});

it('identifies itself and sends no credentials', function (): void {
    fakeWebsite();

    BayernPortal::services()->find(21664765410);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://www.bayernportal.de/')
        && str_contains($request->header('User-Agent')[0] ?? '', '(+scraper)')
        && ! $request->hasHeader('Authorization'));
});

it('lets BayernPortal::fake() work with the scraper driver', function (): void {
    BayernPortal::fake();

    expect(BayernPortal::services()->list())->toHaveCount(2)
        ->and(app(Connector::class))->toBeInstanceOf(HttpConnector::class);
});
