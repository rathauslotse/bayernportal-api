<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * An online procedure (Onlineverfahren): a digital way to apply for a service.
 */
final readonly class OnlineProcedure
{
    /**
     * @param  list<ServiceReference>  $services  Services the procedure belongs to (only when not grouped by service).
     * @param  int|null  $id  Internal id, only included when requested via `withInternalIds`.
     */
    public function __construct(
        public string $title,
        public ?string $url = null,
        public ?string $description = null,
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
        return new self(
            title: $payload->string('kurzbeschreibung', 'bezeichnung') ?? '',
            url: $payload->string('url'),
            description: $payload->string('langbeschreibung'),
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
