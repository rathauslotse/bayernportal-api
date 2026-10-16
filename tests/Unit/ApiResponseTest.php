<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Http\ApiResponse;

it('normalises header names and values', function (): void {
    $response = ApiResponse::make(200, '{}', ['X-Total-Count' => ['42'], 'Link' => '<a>; rel="next"']);

    expect($response->header('x-total-count'))->toBe('42')
        ->and($response->header('LINK'))->toBe('<a>; rel="next"')
        ->and($response->header('missing'))->toBeNull()
        ->and($response->successful())->toBeTrue();
});

it('decodes json bodies', function (): void {
    expect(ApiResponse::make(200, '{"a":1}')->json())->toBe(['a' => 1])
        ->and(ApiResponse::make(200, '')->json())->toBe([])
        ->and(ApiResponse::make(200, '"text"')->json())->toBe(['value' => 'text']);
});

it('throws on invalid json', function (): void {
    ApiResponse::make(200, '<xml/>')->json();
})->throws(BayernPortalException::class, 'invalid JSON');

it('is serialisable', function (): void {
    $response = ApiResponse::make(200, '{"a":1}', ['X-Total-Count' => '1']);

    expect(unserialize(serialize($response)))->toEqual($response);
});
