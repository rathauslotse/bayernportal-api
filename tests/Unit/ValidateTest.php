<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;
use Rathauslotse\BayernPortal\Support\Validate;

it('accepts valid municipality keys and null', function (?string $key): void {
    expect(Validate::municipalityKey($key))->toBe($key);
})->with(['09162000', '09178124', null]);

it('rejects invalid municipality keys', function (string $key): void {
    Validate::municipalityKey($key);
})->throws(InvalidParameterException::class)->with(['9162000', '091620000', '0916200a', '', ' 09162000']);

it('accepts valid agency keys', function (): void {
    expect(Validate::agencyKey('0916200'))->toBe('0916200');
});

it('rejects invalid agency keys', function (string $key): void {
    Validate::agencyKey($key);
})->throws(InvalidParameterException::class)->with(['916200', '09162000', 'abcdefg', '']);

it('validates page numbers', function (): void {
    expect(Validate::page(0))->toBe(0)->and(Validate::page(42))->toBe(42);
    Validate::page(-1);
})->throws(InvalidParameterException::class, 'must not be negative');

it('validates page sizes', function (int $size, bool $valid): void {
    if (! $valid) {
        $this->expectException(InvalidParameterException::class);
    }

    expect(Validate::pageSize($size))->toBe($size);
})->with([[1, true], [100, true], [50, true], [0, false], [101, false], [-5, false]]);

it('validates positive ids', function (): void {
    expect(Validate::id(1))->toBe(1);
    Validate::id(0, 'authority id');
})->throws(InvalidParameterException::class, 'The authority id must be a positive integer, 0 given.');

it('validates life situation ids', function (): void {
    expect(Validate::lifeSituationId('10000100'))->toBe('10000100');
    Validate::lifeSituationId('abc');
})->throws(InvalidParameterException::class);
