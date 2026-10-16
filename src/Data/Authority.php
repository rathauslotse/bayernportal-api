<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * An authority (Behörde): an agency, branch office or organisational unit.
 */
readonly class Authority
{
    /**
     * @param  string  $type  Kind of authority (behoerdenart).
     * @param  string|null  $group  Authority group (behoerdengruppe).
     * @param  Image|null  $logoWithText  Logo variant including the long name (logoLangtext).
     * @param  list<int>  $subordinateAuthorityIds  Ids of assigned authorities (behoerdeZuordnungen).
     * @param  list<int>  $buildingIds  Ids of the authority's buildings (behoerdenGebaeudeZuordnungen).
     * @param  array<array-key, mixed>  $raw  The unmodified API payload.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $type = '',
        public ?string $group = null,
        public ?int $sortOrder = null,
        public ?string $summary = null,
        public ?string $description = null,
        public ?string $email = null,
        public ?string $deMail = null,
        public ?string $website = null,
        public ?Image $logo = null,
        public ?Image $logoWithText = null,
        public array $subordinateAuthorityIds = [],
        public array $buildingIds = [],
        public ?string $secureContactFormUrl = null,
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
        return new self(...self::authorityArguments($payload));
    }

    /**
     * Named constructor arguments shared with {@see Agency}.
     *
     * @return array{id: int, name: string, type: string, group: ?string, sortOrder: ?int, summary: ?string, description: ?string, email: ?string, deMail: ?string, website: ?string, logo: ?Image, logoWithText: ?Image, subordinateAuthorityIds: list<int>, buildingIds: list<int>, secureContactFormUrl: ?string, raw: array<array-key, mixed>}
     */
    protected static function authorityArguments(Payload $payload): array
    {
        return [
            'id' => $payload->int('id', 'behoerdeId') ?? 0,
            'name' => $payload->string('bezeichnung') ?? '',
            'type' => $payload->string('behoerdenart') ?? '',
            'group' => $payload->string('behoerdengruppe'),
            'sortOrder' => $payload->int('sortierreihenfolge'),
            'summary' => $payload->string('kurzbeschreibung'),
            'description' => $payload->string('langbeschreibung'),
            'email' => $payload->string('email'),
            'deMail' => $payload->string('deMail'),
            'website' => $payload->string('website'),
            'logo' => Image::fromValue($payload->get('logo')),
            'logoWithText' => Image::fromValue($payload->get('logoLangtext')),
            'subordinateAuthorityIds' => $payload->ids('behoerdeZuordnungen', 'behoerde'),
            'buildingIds' => $payload->ids('behoerdenGebaeudeZuordnungen', 'gebaeude'),
            'secureContactFormUrl' => $payload->string('linkSicheresKontaktformular'),
            'raw' => $payload->raw(),
        ];
    }
}
