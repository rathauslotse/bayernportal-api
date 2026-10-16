<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A building of a responsible authority, as embedded in service descriptions.
 */
final readonly class ResponsibleBuilding
{
    /**
     * @param  list<int>  $contactIds
     */
    public function __construct(
        public ?int $id,
        public ?Address $streetAddress = null,
        public ?Address $postalAddress = null,
        public ?string $phone = null,
        public ?string $fax = null,
        public array $contactIds = [],
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->int('gebaeudeId', 'id'),
            streetAddress: self::address($payload->payload('hausanschrift')),
            postalAddress: self::address($payload->payload('postanschrift')),
            phone: $payload->string('telefon'),
            fax: $payload->string('fax'),
            contactIds: $payload->ids('behoerdenAnsprechpartnerZuordnungen', 'ansprechpartner'),
        );
    }

    private static function address(?Payload $payload): ?Address
    {
        if ($payload === null) {
            return null;
        }

        return Address::fromParts($payload->string('strasse'), $payload->string('plz'), $payload->string('ort'));
    }
}
