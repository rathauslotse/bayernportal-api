<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A building (Gebäude) of an authority, with addresses, phone numbers and
 * opening hours.
 */
final readonly class Building
{
    /**
     * @param  list<int>  $contactIds  Ids of contacts working in this building (behoerdenAnsprechpartnerZuordnungen).
     * @param  array<array-key, mixed>  $raw  The unmodified API payload.
     */
    public function __construct(
        public int $id,
        public int $authorityId,
        public string $name,
        public ?Address $streetAddress = null,
        public ?Address $postalAddress = null,
        public ?PhoneNumber $phone = null,
        public ?PhoneNumber $fax = null,
        public ?OpeningHours $openingHours = null,
        public ?Image $logo = null,
        public ?int $sortOrder = null,
        public array $contactIds = [],
        public array $raw = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::fromPayload(Payload::of($data));
    }

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->int('gebaeudeId', 'id') ?? 0,
            authorityId: $payload->int('behoerdeId') ?? 0,
            name: $payload->string('bezeichnung') ?? '',
            streetAddress: Address::fromParts(
                $payload->string('hausanschriftStrasse'),
                $payload->string('hausanschriftPLZ', 'hausanschriftPlz'),
                $payload->string('hausanschriftOrt'),
            ),
            postalAddress: Address::fromParts(
                $payload->string('postanschriftStrasse'),
                $payload->string('postanschriftPLZ', 'postanschriftPlz'),
                $payload->string('postanschriftOrt'),
            ),
            phone: PhoneNumber::fromPayload($payload, 'telefon'),
            fax: PhoneNumber::fromPayload($payload, 'fax'),
            openingHours: OpeningHours::fromPayload($payload->payload('oeffnungszeiten')),
            logo: Image::fromValue($payload->get('logo')),
            sortOrder: $payload->int('sortierreihenfolge'),
            contactIds: $payload->ids('behoerdenAnsprechpartnerZuordnungen', 'ansprechpartner'),
            raw: $payload->raw(),
        );
    }
}
