<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * An agency (Dienststelle), identified by its 7-digit agency key
 * (Dienststellenschlüssel). Branch offices (Außenstellen) share the key of
 * their main agency and have a branch number of 1 … n; the main agency has 0.
 */
final readonly class Agency extends Authority
{
    /**
     * @param  string|null  $nameSuffix  Additional name part (bezeichnungZusatz).
     * @param  string|null  $administrativeAssociation  Administrative association the municipality belongs to (mitgliedVonVerwaltungsgemeinschaft).
     * @param  list<int>  $subordinateAuthorityIds
     * @param  list<int>  $buildingIds
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        int $id,
        string $name,
        public string $agencyKey,
        public int $branchNumber = 0,
        public ?string $nameSuffix = null,
        public ?string $administrativeAssociation = null,
        string $type = '',
        ?string $group = null,
        ?int $sortOrder = null,
        ?string $summary = null,
        ?string $description = null,
        ?string $email = null,
        ?string $deMail = null,
        ?string $website = null,
        ?Image $logo = null,
        ?Image $logoWithText = null,
        array $subordinateAuthorityIds = [],
        array $buildingIds = [],
        ?string $secureContactFormUrl = null,
        array $raw = [],
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            type: $type,
            group: $group,
            sortOrder: $sortOrder,
            summary: $summary,
            description: $description,
            email: $email,
            deMail: $deMail,
            website: $website,
            logo: $logo,
            logoWithText: $logoWithText,
            subordinateAuthorityIds: $subordinateAuthorityIds,
            buildingIds: $buildingIds,
            secureContactFormUrl: $secureContactFormUrl,
            raw: $raw,
        );
    }

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
            ...self::authorityArguments($payload),
            agencyKey: $payload->string('dienststellenschluessel') ?? '',
            branchNumber: $payload->int('dienststelleLfdNr') ?? 0,
            nameSuffix: $payload->string('bezeichnungZusatz'),
            administrativeAssociation: $payload->string('mitgliedVonVerwaltungsgemeinschaft'),
        );
    }

    public function isBranchOffice(): bool
    {
        return $this->branchNumber > 0;
    }

    /**
     * Name including the suffix, e.g. "Landratsamt Freising – Außenstelle Moosburg".
     */
    public function fullName(string $separator = ' – '): string
    {
        return $this->nameSuffix === null ? $this->name : $this->name.$separator.$this->nameSuffix;
    }
}
