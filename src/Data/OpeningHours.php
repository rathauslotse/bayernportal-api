<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Opening hours of a building or consultation hours of a contact
 * (API type "Oeffnungszeiten").
 */
final readonly class OpeningHours
{
    /**
     * @param  list<TimeSlot>  $timeSlots
     */
    public function __construct(
        public bool $byAppointment = false,
        public ?string $specialHours = null,
        public array $timeSlots = [],
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(?Payload $payload): ?self
    {
        if ($payload === null || $payload->isEmpty()) {
            return null;
        }

        $slots = [];

        // <oeffnungszeit> groups contain the actual <zeitangabe> entries.
        foreach ($payload->list('oeffnungszeit') as $group) {
            foreach ($group->list('zeitangabe') as $entry) {
                $slot = TimeSlot::fromPayload($entry);

                if ($slot !== null) {
                    $slots[] = $slot;
                }
            }
        }

        // Tolerate a flattened rendering without the group level.
        foreach ($payload->list('zeitangabe') as $entry) {
            $slot = TimeSlot::fromPayload($entry);

            if ($slot !== null) {
                $slots[] = $slot;
            }
        }

        return new self(
            byAppointment: $payload->bool('nachVereinbarung') ?? false,
            specialHours: $payload->string('sonderzeiten'),
            timeSlots: $slots,
        );
    }

    /**
     * @return list<TimeSlot>
     */
    public function forDay(Weekday $day): array
    {
        return array_values(array_filter($this->timeSlots, fn (TimeSlot $slot): bool => $slot->day === $day));
    }

    public function isEmpty(): bool
    {
        return $this->timeSlots === [] && $this->specialHours === null && ! $this->byAppointment;
    }
}
