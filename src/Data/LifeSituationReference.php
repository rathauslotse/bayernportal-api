<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Short reference to a life situation (Lebenslage).
 */
final readonly class LifeSituationReference
{
    public function __construct(
        public string $id,
        public ?string $name = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): ?self
    {
        $id = $payload->string('id');

        return $id === null ? null : new self($id, $payload->string('bezeichnung'));
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
