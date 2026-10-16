<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A further link (weiterführender Link) of a service description.
 */
final readonly class Link
{
    /**
     * @param  int|null  $id  Internal id, only included when requested via `withInternalIds`.
     */
    public function __construct(
        public string $title,
        public ?string $url = null,
        public ?string $description = null,
        public ?int $id = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            title: $payload->string('kurzbeschreibung', 'bezeichnung') ?? '',
            url: $payload->string('url'),
            description: $payload->string('langbeschreibung'),
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
