<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Short reference to a service (Leistung): id, name and portal URL.
 */
final readonly class ServiceReference
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $url = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        return self::fromPayload(Payload::of($data));
    }

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): ?self
    {
        $id = $payload->int('id', 'leistungId');

        if ($id === null) {
            return null;
        }

        return new self(
            id: $id,
            name: $payload->string('bezeichnung', 'leistungBezeichnung'),
            url: $payload->string('url', 'leistungUrl'),
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
        return array_values(array_filter(array_map(self::fromPayload(...), $items)));
    }
}
