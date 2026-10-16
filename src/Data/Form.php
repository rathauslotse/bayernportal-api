<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Enums\WrittenFormRequirement;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A form (Formular).
 */
final readonly class Form
{
    /**
     * @param  bool  $prefillable  Whether the form can be pre-filled with the authority's details (vorausfuellbar).
     * @param  string|null  $writtenFormRequirementDescription  The API's description of the requirement (German).
     * @param  list<ServiceReference>  $services  Services the form belongs to (only when forms are not grouped by service).
     * @param  int|null  $id  Internal id; always present on agency forms, otherwise only with `withInternalIds`.
     */
    public function __construct(
        public string $title,
        public ?string $url = null,
        public ?string $description = null,
        public ?WrittenFormRequirement $writtenFormRequirement = null,
        public ?string $writtenFormRequirementDescription = null,
        public bool $prefillable = false,
        public array $services = [],
        public ?int $id = null,
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
        $requirement = $payload->payload('schriftformErfordernis');

        return new self(
            title: $payload->string('kurzbeschreibung') ?? '',
            url: $payload->string('url'),
            description: $payload->string('langbeschreibung'),
            writtenFormRequirement: $requirement?->enum(WrittenFormRequirement::class, 'id'),
            writtenFormRequirementDescription: $requirement?->string('beschreibung'),
            prefillable: $payload->bool('vorausfuellbar') ?? false,
            services: ServiceReference::many($payload->list('leistungen', 'leistung')),
            id: $payload->int('id'),
        );
    }

    /**
     * @internal
     *
     * @param  list<Payload>  $items
     * @return list<self>
     */
    public static function many(array $items): array
    {
        return array_map(self::fromPayload(...), $items);
    }
}
