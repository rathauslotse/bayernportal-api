<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Rathauslotse\BayernPortal\Enums\ServiceStatus;
use Rathauslotse\BayernPortal\Support\Payload;

it('reads attributes with or without prefix', function (string $key): void {
    expect(Payload::of([$key => 42])->int('id'))->toBe(42);
})->with(['id', '@id', '_id', '-id']);

it('prefers the unprefixed key and skips nulls', function (): void {
    $payload = Payload::of(['id' => null, '@id' => '7']);

    expect($payload->int('id'))->toBe(7)
        ->and($payload->has('id'))->toBeTrue()
        ->and($payload->has('missing'))->toBeFalse();
});

it('reads text content of elements with attributes', function (string $textKey): void {
    expect(Payload::of(['bezeichnung' => [$textKey => ' Umzug ', 'lang' => 'de']])->string('bezeichnung'))->toBe('Umzug');
})->with(['value', '$', '#text', '_', 'content']);

it('casts typed values', function (): void {
    $payload = Payload::of([
        'empty' => '  ',
        'num' => '12',
        'float' => 1.5,
        'yes' => 'true',
        'no' => 0,
        'bool' => false,
        'weird' => 'maybe',
        'date' => '2026-07-01T00:00:00+02:00',
        'epoch' => 1767225600000,
        'baddate' => 'not a date',
        'list' => [1, 2],
        'status' => 'LIVE',
    ]);

    expect($payload->string('empty'))->toBeNull()
        ->and($payload->int('num'))->toBe(12)
        ->and($payload->int('yes'))->toBeNull()
        ->and($payload->string('float'))->toBe('1.5')
        ->and($payload->string('bool'))->toBe('false')
        ->and($payload->string('list'))->toBeNull()
        ->and($payload->bool('yes'))->toBeTrue()
        ->and($payload->bool('no'))->toBeFalse()
        ->and($payload->bool('bool'))->toBeFalse()
        ->and($payload->bool('weird'))->toBeNull()
        ->and($payload->date('date'))->toBeInstanceOf(CarbonImmutable::class)
        ->and($payload->date('date')?->toDateString())->toBe('2026-07-01')
        ->and($payload->date('epoch')?->year)->toBe(2026)
        ->and($payload->date('baddate'))->toBeNull()
        ->and($payload->date('missing'))->toBeNull()
        ->and($payload->enum(ServiceStatus::class, 'status'))->toBe(ServiceStatus::Live)
        ->and($payload->enum(ServiceStatus::class, 'missing'))->toBeNull()
        ->and($payload->first('missing', 'num'))->toBe('12');
});

it('unwraps lists along an optional wrapper path', function (mixed $body): void {
    $items = Payload::items($body, 'dienststellen', 'dienststelle');

    expect($items)->toHaveCount(2)
        ->and($items[1]->int('id'))->toBe(2);
})->with([
    'wrapped' => [['dienststellen' => ['dienststelle' => [['id' => 1], ['id' => 2]]]]],
    'inner wrapper only' => [['dienststelle' => [['id' => 1], ['id' => 2]]]],
    'plain list' => [[['id' => 1], ['id' => 2]]],
]);

it('treats a single wrapped object as a one-element list', function (): void {
    $items = Payload::items(['dienststellen' => ['dienststelle' => ['id' => 1]]], 'dienststellen', 'dienststelle');

    expect($items)->toHaveCount(1)->and($items[0]->int('id'))->toBe(1);
});

it('does not descend into item keys that match a wrapper', function (): void {
    $body = ['formulare' => ['leistungMitFormularen' => ['leistungId' => 1, 'formulare' => ['formular' => ['id' => 9]]]]];
    $items = Payload::items($body, 'formulare', 'leistungMitFormularen|formular');

    expect($items)->toHaveCount(1)->and($items[0]->int('leistungId'))->toBe(1);
});

it('handles empty and scalar bodies', function (): void {
    expect(Payload::items([], 'a'))->toBe([])
        ->and(Payload::items(null, 'a'))->toBe([])
        ->and(Payload::items('text', 'a')[0]->string('value'))->toBe('text')
        ->and(Payload::single(null)->isEmpty())->toBeTrue();
});

it('reads nested lists, strings and ids', function (): void {
    $payload = Payload::of([
        'synonyme' => ['synonym' => ['Perso', ' ', 'Ausweis']],
        'flat' => ['a', 'b'],
        'single' => ['synonym' => 'Einzel'],
        'gebaeude' => [['id' => 1], ['@id' => '2'], ['name' => 'no id']],
        'nested' => ['inner' => ['id' => 3]],
        'synonym' => ['Direct'],
    ]);

    expect($payload->strings('synonyme', 'synonym'))->toBe(['Perso', 'Ausweis'])
        ->and($payload->strings('flat'))->toBe(['a', 'b'])
        ->and($payload->strings('single', 'synonym'))->toBe(['Einzel'])
        ->and($payload->strings('absent', 'synonym'))->toBe(['Direct'])
        ->and($payload->strings('absent', 'nothing'))->toBe([])
        ->and($payload->ids('gebaeude'))->toBe([1, 2])
        ->and($payload->payload('nested')?->payload('inner')?->int('id'))->toBe(3)
        ->and($payload->payload('flat'))->not->toBeNull()
        ->and($payload->payload('missing'))->toBeNull();
});
