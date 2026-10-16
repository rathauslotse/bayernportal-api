<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\BayernPortal as Client;
use Rathauslotse\BayernPortal\Cache\CachingConnector;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Facades\BayernPortal;
use Rathauslotse\BayernPortal\Http\HttpConnector;

function enableCache(int $ttl = 60): void
{
    config()->set('cache.default', 'array');
    config()->set('bayernportal.cache.enabled', true);
    config()->set('bayernportal.cache.ttl', $ttl);
    app()->forgetInstance(Connector::class);
    app()->forgetInstance(Client::class);
    BayernPortal::clearResolvedInstances();
}

it('is disabled by default', function (): void {
    expect(app(Connector::class))->toBeInstanceOf(HttpConnector::class);
});

it('serves repeated requests from the cache', function (): void {
    enableCache();
    fakeApi('agencies', ['X-Total-Count' => '3', 'Link' => '<x?page=0>; rel="last"', 'X-Other' => 'dropped']);

    $first = BayernPortal::agencies()->list();
    $second = BayernPortal::agencies()->list();

    expect(app(Connector::class))->toBeInstanceOf(CachingConnector::class)
        ->and($second->items->map->name->all())->toBe($first->items->map->name->all())
        ->and($second->total)->toBe(3)
        ->and($second->links)->toBe(['last' => 'x?page=0']);

    Http::assertSentCount(1);
});

it('distinguishes requests by path and query', function (): void {
    enableCache();
    fakeApi('services');

    BayernPortal::services()->list();
    BayernPortal::services()->list('09162000');
    BayernPortal::forMunicipality('09162000')->services()->list();

    Http::assertSentCount(2);
});

it('does not cache error responses', function (): void {
    enableCache();
    Http::fake(['*' => Http::sequence()->push('Fehler', 400)->push(jsonFixture('services'))]);

    expect(fn () => BayernPortal::services()->list())->toThrow(RequestException::class)
        ->and(BayernPortal::services()->list())->toHaveCount(2);

    Http::assertSentCount(2);
});

it('respects the ttl', function (): void {
    enableCache(ttl: 10);
    fakeApi('services');

    BayernPortal::services()->list();
    $this->travel(11)->seconds();
    BayernPortal::services()->list();

    Http::assertSentCount(2);
});

it('can be bypassed per call', function (): void {
    enableCache();
    fakeApi('services');

    BayernPortal::services()->list();
    BayernPortal::withoutCache()->services()->list();
    BayernPortal::withoutCache()->withoutCache()->services()->list();

    Http::assertSentCount(3);
});

it('can be flushed', function (): void {
    enableCache();
    fakeApi('services');

    BayernPortal::services()->list();
    BayernPortal::flushCache();
    BayernPortal::services()->list();
    BayernPortal::services()->list();

    Http::assertSentCount(2);
});

it('ignores flush and bypass when disabled', function (): void {
    fakeApi('services');

    BayernPortal::flushCache();

    expect(BayernPortal::withoutCache())->toBe(BayernPortal::getFacadeRoot());
});

it('uses the configured store and prefix', function (): void {
    config()->set('cache.stores.bayernportal', ['driver' => 'array']);
    config()->set('bayernportal.cache.store', 'bayernportal');
    config()->set('bayernportal.cache.prefix', 'bp-test');
    enableCache();
    fakeApi('services');

    BayernPortal::services()->list();

    /** @var CachingConnector $connector */
    $connector = app(Connector::class);

    expect(Cache::store('bayernportal')->has($connector->key('leistungen')))->toBeTrue()
        ->and($connector->key('leistungen'))->toStartWith('bp-test:0:')
        ->and(Cache::store('array')->has($connector->key('leistungen')))->toBeFalse();
});

it('ignores malformed cache entries', function (): void {
    enableCache();
    fakeApi('services');

    /** @var CachingConnector $connector */
    $connector = app(Connector::class);
    Cache::put($connector->key('leistungen'), 'garbage', 60);

    expect(BayernPortal::services()->list())->toHaveCount(2);

    Http::assertSentCount(1);
});
