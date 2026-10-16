<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;

/**
 * The agency used for the sub-resource tests: BAYERNPORTAL_TEST_AGENCY_KEY
 * or the first agency of the list.
 */
function liveAgencyKey(): string
{
    if ($key = getenv('BAYERNPORTAL_TEST_AGENCY_KEY')) {
        return $key;
    }

    $agency = BayernPortal::agencies()->list(size: 1)->items->first();

    expect($agency)->toBeInstanceOf(Agency::class);

    return $agency->agencyKey;
}

it('lists agencies with pagination metadata', function (): void {
    $page = BayernPortal::agencies()->list(size: 5);

    expect($page->items)->not->toBeEmpty()
        ->and($page->items->count())->toBeLessThanOrEqual(5)
        ->and($page->total)->toBeGreaterThan(5)
        ->and($page->hasNextPage())->toBeTrue()
        ->and($page->items->first()?->agencyKey)->toMatch('/^\d{7}$/')
        ->and($page->items->first()?->name)->not->toBeEmpty();

    expect($page->next()?->items)->not->toBeEmpty();
});

it('lists agencies in full form', function (): void {
    expect(BayernPortal::agencies()->list(full: true, size: 2)->items)->not->toBeEmpty();
});

it('finds an agency', function (): void {
    $key = liveAgencyKey();

    expect(BayernPortal::agencies()->find($key)->agencyKey)->toBe($key);
});

it('lists services, forms and online procedures of an agency', function (): void {
    $key = liveAgencyKey();
    $agencies = BayernPortal::agencies();

    expect($agencies->services($key, liveMunicipalityKey()))->toBeIterable()
        ->and($agencies->forms($key))->toBeIterable()
        ->and($agencies->formsByService($key))->toBeIterable()
        ->and($agencies->onlineProcedures($key))->toBeIterable()
        ->and($agencies->onlineProceduresByService($key))->toBeIterable();
});

it('lists service descriptions of an agency', function (): void {
    expect(BayernPortal::agencies()->serviceDescriptions(liveAgencyKey(), liveMunicipalityKey(), withResponsibilities: true))->toBeIterable();
});

it('lists life situations of an agency or explains why not', function (): void {
    try {
        expect(BayernPortal::agencies()->lifeSituations(liveAgencyKey()))->toBeIterable();
    } catch (RequestException $exception) {
        expect($exception->status())->toBe(400)->and($exception->apiMessage())->not->toBeEmpty();
    }
});
