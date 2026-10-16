<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Enums\WrittenFormRequirement;

it('maps weekdays to ISO numbers and back', function (Weekday $day): void {
    expect(Weekday::fromIsoNumber($day->isoNumber()))->toBe($day);
})->with(Weekday::cases());

it('describes written form requirements', function (): void {
    expect(WrittenFormRequirement::SignatureRequired->requiresSignature())->toBeTrue()
        ->and(WrittenFormRequirement::SignatureRequired->requiresPersonalAppearance())->toBeFalse()
        ->and(WrittenFormRequirement::SignatureAndPersonalAppearanceRequired->requiresPersonalAppearance())->toBeTrue()
        ->and(WrittenFormRequirement::NoSignatureRequired->requiresSignature())->toBeFalse();
});
