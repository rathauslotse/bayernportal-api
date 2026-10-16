<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * An authority responsible for a service in the requested municipality
 * (Zuständigkeit).
 */
final readonly class Responsibility
{
    /**
     * @param  string|null  $portalUrl  The authority's page on the BayernPortal (bwwlink).
     * @param  list<ResponsibleBuilding>  $buildings
     */
    public function __construct(
        public ?int $authorityId,
        public string $name,
        public array $buildings = [],
        public ?string $email = null,
        public ?string $website = null,
        public ?string $portalUrl = null,
        public ?string $secureContactFormUrl = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            // The schema spells this field "behoerdId".
            authorityId: $payload->int('behoerdId', 'behoerdeId', 'id'),
            name: $payload->string('bezeichnung') ?? '',
            buildings: array_map(ResponsibleBuilding::fromPayload(...), $payload->list('anschriften', 'gebaeude')),
            email: $payload->string('email'),
            website: $payload->string('website'),
            portalUrl: $payload->string('bwwlink'),
            secureContactFormUrl: $payload->string('linkSicheresKontaktformular'),
        );
    }
}
