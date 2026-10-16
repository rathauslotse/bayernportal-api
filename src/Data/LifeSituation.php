<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Carbon\CarbonImmutable;
use Rathauslotse\BayernPortal\Enums\LifeSituationCategory;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A life situation (Lebenslage), e.g. "Umzug" or "Geburt".
 *
 * Life situations form a two-level tree: top-level categories without a
 * parent and sub-categories pointing to their parent via `parentId`.
 * Services are only assigned to sub-categories.
 */
final readonly class LifeSituation
{
    /**
     * @param  list<string>  $synonyms
     * @param  list<ServiceReference>  $services
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $summary = null,
        public ?string $description = null,
        public ?LifeSituationCategory $category = null,
        public ?string $parentId = null,
        public ?CarbonImmutable $updatedAt = null,
        public array $synonyms = [],
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
        $parentId = $payload->string('vorgaengerId');

        return new self(
            id: $payload->string('id') ?? '',
            name: $payload->string('bezeichnung') ?? '',
            summary: $payload->string('kurzbeschreibung'),
            description: $payload->string('langbeschreibung'),
            category: $payload->enum(LifeSituationCategory::class, 'kategorie'),
            parentId: $parentId === null || trim($parentId, '0') === '' ? null : $parentId,
            updatedAt: $payload->date('stand'),
            synonyms: $payload->strings('synonyme', 'synonym'),
            services: ServiceReference::many($payload->list('leistungen', 'leistung')),
            raw: $payload->raw(),
        );
    }

    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }
}
