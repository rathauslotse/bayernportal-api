<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Scraper\MunicipalityIndex;
use Rathauslotse\BayernPortal\Scraper\ScraperOptions;
use Rathauslotse\BayernPortal\Scraper\ScraperState;
use Rathauslotse\BayernPortal\Scraper\Throttle;

it('waits between requests', function (): void {
    $now = 100.0;
    $slept = [];
    $throttle = new Throttle(1000, null, function (int $microseconds) use (&$now, &$slept): void {
        $slept[] = $microseconds;
        $now += $microseconds / 1_000_000;
    }, function () use (&$now): float {
        return $now;
    });

    $throttle->wait();
    $now += 0.25;
    $throttle->wait();
    $now += 2.0;
    $throttle->wait();

    expect($slept)->toBe([750000]);
});

it('shares the last request time through the cache', function (): void {
    $cache = new Repository(new ArrayStore);
    $now = 100.0;
    $slept = [];
    $sleep = function (int $microseconds) use (&$now, &$slept): void {
        $slept[] = $microseconds;
        $now += $microseconds / 1_000_000;
    };

    (new Throttle(500, $cache, $sleep, function () use (&$now): float {
        return $now;
    }))->wait();
    (new Throttle(500, $cache, $sleep, function () use (&$now): float {
        return $now;
    }))->wait();

    expect($slept)->toBe([500000]);
});

it('does not wait when throttling is disabled', function (): void {
    $throttle = new Throttle(0, null, fn () => throw new RuntimeException('slept'));
    $throttle->wait();
    $throttle->wait();

    expect(true)->toBeTrue();
});

it('reads scraper options from config', function (): void {
    $options = ScraperOptions::fromConfig([
        'connect_timeout' => 5,
        'scraper' => ['base_url' => 'https://proxy.example/', 'throttle_ms' => -1, 'timeout' => '12', 'retry' => ['times' => 3, 'sleep_ms' => 10]],
    ]);

    expect($options->baseUrl)->toBe('https://proxy.example/')
        ->and($options->throttleMilliseconds)->toBe(0)
        ->and($options->timeout)->toBe(12)
        ->and($options->connectTimeout)->toBe(5)
        ->and($options->retryTimes)->toBe(3)
        ->and($options->retrySleepMilliseconds)->toBe(10)
        ->and(ScraperOptions::fromConfig([])->baseUrl)->toBe(ScraperOptions::DEFAULT_BASE_URL);
});

it('looks up municipalities by municipality and agency key', function (): void {
    $index = MunicipalityIndex::fromArray([
        '09184123' => ['id' => '66552873578', 'name' => 'Haar (Stadt)'],
        '09162000' => ['id' => '1', 'name' => 'München'],
        '09162001' => ['id' => '2', 'name' => 'Ambiguous'],
    ]);

    expect($index->find('09184123'))->toBe(['id' => '66552873578', 'name' => 'Haar (Stadt)'])
        ->and($index->find('00000000'))->toBeNull()
        ->and($index->findByAgencyKey('9184123'))->toBe(['key' => '09184123', 'id' => '66552873578', 'name' => 'Haar (Stadt)'])
        ->and($index->findByAgencyKey('0918412'))->toBe(['key' => '09184123', 'id' => '66552873578', 'name' => 'Haar (Stadt)'])
        ->and($index->findByAgencyKey('0916200'))->toBeNull()
        ->and($index->all())->toHaveCount(3);
});

it('loads the index from a JSON file', function (): void {
    $path = sys_get_temp_dir().'/bayernportal-index-'.uniqid().'.json';
    file_put_contents($path, json_encode(['09184123' => ['id' => '66552873578', 'name' => 'Haar'], 'broken' => ['name' => 'x']]));

    expect((new MunicipalityIndex($path))->all())->toBe(['09184123' => ['id' => '66552873578', 'name' => 'Haar']])
        ->and((new MunicipalityIndex('/does/not/exist.json'))->all())->toBe([]);

    file_put_contents($path, '{');
    expect(fn () => (new MunicipalityIndex($path))->all())->toThrow(BayernPortalException::class, 'not valid JSON');

    unlink($path);
});

it('ships a municipality index', function (): void {
    $index = new MunicipalityIndex;

    expect(is_file(MunicipalityIndex::BUNDLED))->toBeTrue()
        ->and(count($index->all()))->toBeGreaterThan(2000)
        ->and($index->find('09184123')['id'] ?? null)->toBe('66552873578');
});

it('remembers portal ids with leading zeros and localisations', function (): void {
    $state = new ScraperState(new Repository(new ArrayStore));
    $state->rememberIds(['047980064430', '66552873578', null]);
    $state->rememberLocalisation('66552873578', ['plz' => '85540', 'behoerde' => '66552873578', 'gemeinde' => '365968738674']);

    expect($state->portalIds(47980064430))->toBe(['047980064430'])
        ->and($state->portalIds(66552873578))->toBe(['66552873578', '066552873578'])
        ->and($state->localisation('66552873578'))->toBe(['plz' => '85540', 'behoerde' => '66552873578', 'gemeinde' => '365968738674'])
        ->and($state->localisation('1'))->toBeNull();
});
