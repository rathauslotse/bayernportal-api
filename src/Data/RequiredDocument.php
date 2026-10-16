<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A document the applicant has to provide (erforderliche Unterlage).
 *
 * Some documents are themselves obtained through another service, which is
 * then referenced by `service`.
 */
final readonly class RequiredDocument
{
    /**
     * @param  int|null  $id  Internal id, only included when requested via `withInternalIds`.
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?ServiceReference $service = null,
        public ?int $id = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            title: $payload->string('kurzbeschreibung') ?? '',
            description: $payload->string('langbeschreibung'),
            service: ServiceReference::fromPayload(Payload::of([
                'leistungId' => $payload->get('leistungId'),
                'leistungUrl' => $payload->get('leistungUrl'),
            ])),
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
