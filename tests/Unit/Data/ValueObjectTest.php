<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Data\Address;
use Rathauslotse\BayernPortal\Data\Image;
use Rathauslotse\BayernPortal\Data\OpeningHours;
use Rathauslotse\BayernPortal\Data\PhoneNumber;
use Rathauslotse\BayernPortal\Data\TimeSlot;
use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Support\Payload;

it('formats addresses', function (): void {
    $address = Address::fromParts('Marienplatz 8', '80331', 'München');

    expect((string) $address)->toBe('Marienplatz 8, 80331 München')
        ->and(Address::fromParts(null, '80331', 'München')?->format())->toBe('80331 München')
        ->and(Address::fromParts(null, null, null))->toBeNull();
});

it('formats phone numbers', function (?string $country, ?string $area, ?string $number, ?string $ext, string $formatted, ?string $e164): void {
    $phone = new PhoneNumber($country, $area, $number, $ext);

    expect($phone->format())->toBe($formatted)
        ->and((string) $phone)->toBe($formatted)
        ->and($phone->toE164())->toBe($e164);
})->with([
    ['+49', '089', '233', '96000', '+49 89 233-96000', '+498923396000'],
    ['0049', '0151', '12345678', null, '+49 151 12345678', '+4915112345678'],
    ['49', '8161', '600', '', '+49 8161 600', '+498161600'],
    [null, '089', '233', '96000', '089 233-96000', null],
    [null, null, null, '12', '12', null],
]);

it('reads split phone numbers from payloads', function (): void {
    $payload = Payload::of(['mobilLandvorwahl' => '+49', 'mobilOrtsvorwahl' => '0151', 'mobilNummer' => '1234']);

    expect(PhoneNumber::fromPayload($payload, 'mobil')?->format())->toBe('+49 151 1234')
        ->and(PhoneNumber::fromPayload($payload, 'fax'))->toBeNull();
});

it('reads opening hours', function (): void {
    $hours = OpeningHours::fromPayload(Payload::of([
        'nachVereinbarung' => 'true',
        'sonderzeiten' => 'Feiertags geschlossen',
        'oeffnungszeit' => [
            ['zeitangabe' => [
                ['typ' => 'MO', 'vonVormittags' => '08:00', 'bisVormittags' => '12:00', 'vonNachmittags' => '', 'bisNachmittags' => ''],
                ['typ' => 'XX'],
            ]],
            ['zeitangabe' => ['typ' => 'DI', 'vonVormittags' => '08:00', 'bisVormittags' => '12:00', 'vonNachmittags' => '14:00', 'bisNachmittags' => '16:00']],
        ],
    ]));

    expect($hours)->not->toBeNull()
        ->and($hours->byAppointment)->toBeTrue()
        ->and($hours->specialHours)->toBe('Feiertags geschlossen')
        ->and($hours->timeSlots)->toHaveCount(2)
        ->and($hours->forDay(Weekday::Monday)[0]->format())->toBe('08:00–12:00')
        ->and($hours->forDay(Weekday::Tuesday)[0]->format())->toBe('08:00–12:00, 14:00–16:00')
        ->and($hours->forDay(Weekday::Sunday))->toBe([])
        ->and($hours->isEmpty())->toBeFalse()
        ->and(OpeningHours::fromPayload(null))->toBeNull()
        ->and((new OpeningHours)->isEmpty())->toBeTrue();
});

it('accepts flattened time slots', function (): void {
    $hours = OpeningHours::fromPayload(Payload::of(['zeitangabe' => ['typ' => 'FR', 'vonVormittags' => '09:00']]));

    expect($hours?->timeSlots)->toHaveCount(1)
        ->and($hours?->timeSlots[0]->ranges())->toBe([['from' => '09:00', 'until' => null]])
        ->and($hours?->timeSlots[0]->format())->toBe('09:00');
});

it('builds time slots', function (): void {
    expect(TimeSlot::fromPayload(Payload::of(['typ' => 'so'])))->toEqual(new TimeSlot(Weekday::Sunday))
        ->and(TimeSlot::fromPayload(Payload::of([])))->toBeNull();
});

it('reads images in different renderings', function (): void {
    $png = base64_encode('png-bytes');

    $image = Image::fromValue(['value' => $png, 'mimetype' => 'image/png', 'alt' => 'Wappen', 'title' => 'T', 'quelle' => 'Q']);

    expect($image?->contents())->toBe('png-bytes')
        ->and($image?->toDataUri())->toBe('data:image/png;base64,'.$png)
        ->and($image?->alt)->toBe('Wappen')
        ->and($image?->source)->toBe('Q')
        ->and(Image::fromValue($png)?->toDataUri())->toBe('data:application/octet-stream;base64,'.$png)
        ->and(Image::fromValue(['@mimetype' => 'image/png', '$' => "aGk=\n"])?->base64)->toBe('aGk=')
        ->and(Image::fromValue(null))->toBeNull()
        ->and(Image::fromValue(['mimetype' => 'image/png']))->toBeNull();
});
