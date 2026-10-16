<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A contact person (Ansprechpartner) of an authority.
 *
 * Returned by the contact endpoints and by the building contact endpoint;
 * the latter does not include authority/building names or services.
 */
final readonly class Contact
{
    /**
     * @param  string|null  $officialTitle  Official title (amtsbezeichnung), e.g. "Oberregierungsrat".
     * @param  string|null  $academicDegree  Academic degree (akademischerGrad), e.g. "Dr.".
     * @param  string|null  $role  Function (funktion), e.g. "Sachgebietsleitung".
     * @param  string|null  $jobTitle  Position (stellenbezeichnung).
     * @param  OpeningHours|null  $consultationHours  Consultation hours (sprechzeiten).
     * @param  list<ServiceReference>  $services  Services the contact is responsible for.
     * @param  array<array-key, mixed>  $raw  The unmodified API payload.
     */
    public function __construct(
        public int $id,
        public string $lastName,
        public ?string $firstName = null,
        public ?string $salutation = null,
        public ?string $officialTitle = null,
        public ?string $academicDegree = null,
        public ?string $role = null,
        public ?string $jobTitle = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $room = null,
        public ?int $sortOrder = null,
        public ?int $authorityId = null,
        public ?string $authorityName = null,
        public ?int $buildingId = null,
        public ?string $buildingName = null,
        public ?PhoneNumber $phone = null,
        public ?PhoneNumber $fax = null,
        public ?PhoneNumber $mobile = null,
        public ?OpeningHours $consultationHours = null,
        public ?Image $logo = null,
        public array $services = [],
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
            id: $payload->int('ansprechpartnerId', 'id') ?? 0,
            lastName: $payload->string('nachname') ?? '',
            firstName: $payload->string('vorname'),
            salutation: $payload->string('anrede'),
            officialTitle: $payload->string('amtsbezeichnung'),
            academicDegree: $payload->string('akademischerGrad'),
            role: $payload->string('funktion'),
            jobTitle: $payload->string('stellenbezeichnung'),
            email: $payload->string('email'),
            website: $payload->string('website'),
            room: $payload->string('zimmer'),
            sortOrder: $payload->int('sortierreihenfolge'),
            authorityId: $payload->int('behoerdeId'),
            authorityName: $payload->string('behoerdeBezeichnung'),
            buildingId: $payload->int('gebaeudeId'),
            buildingName: $payload->string('gebaeudeBezeichnung'),
            phone: PhoneNumber::fromPayload($payload, 'telefon'),
            fax: PhoneNumber::fromPayload($payload, 'fax'),
            mobile: PhoneNumber::fromPayload($payload, 'mobil'),
            consultationHours: OpeningHours::fromPayload($payload->payload('sprechzeiten')),
            logo: Image::fromValue($payload->get('logo')),
            services: ServiceReference::many($payload->list('leistungen', 'lg', 'leistung')),
            raw: $payload->raw(),
        );
    }

    /**
     * Degree, first and last name, e.g. "Dr. Maria Huber".
     */
    public function fullName(): string
    {
        return implode(' ', array_filter([$this->academicDegree, $this->firstName, $this->lastName], fn (?string $part): bool => $part !== null && $part !== ''));
    }
}
