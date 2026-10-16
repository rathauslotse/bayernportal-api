<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A related service (verwandte Leistung) of a service description.
 */
final readonly class RelatedService
{
    /**
     * @param  bool  $direct  Whether the services are directly related (direktVerwandt).
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $url = null,
        public bool $direct = false,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->int('id') ?? 0,
            name: $payload->string('bezeichnung') ?? '',
            url: $payload->string('url'),
            direct: $payload->bool('direktVerwandt') ?? false,
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
