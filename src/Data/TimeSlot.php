<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Enums\Weekday;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Opening times of one weekday, split into a morning and an afternoon range
 * (API type "Zeitangabe"). Times are kept as returned, e.g. "08:00".
 */
final readonly class TimeSlot
{
    public function __construct(
        public Weekday $day,
        public ?string $morningFrom = null,
        public ?string $morningUntil = null,
        public ?string $afternoonFrom = null,
        public ?string $afternoonUntil = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): ?self
    {
        $day = $payload->enum(Weekday::class, 'typ');

        if ($day === null) {
            return null;
        }

        return new self(
            day: $day,
            morningFrom: $payload->string('vonVormittags'),
            morningUntil: $payload->string('bisVormittags'),
            afternoonFrom: $payload->string('vonNachmittags'),
            afternoonUntil: $payload->string('bisNachmittags'),
        );
    }

    /**
     * The non-empty time ranges of this day.
     *
     * @return list<array{from: string, until: string|null}>
     */
    public function ranges(): array
    {
        $ranges = [];

        foreach ([[$this->morningFrom, $this->morningUntil], [$this->afternoonFrom, $this->afternoonUntil]] as [$from, $until]) {
            if ($from !== null) {
                $ranges[] = ['from' => $from, 'until' => $until];
            }
        }

        return $ranges;
    }

    /**
     * E.g. "08:00–12:00, 14:00–16:00".
     */
    public function format(): string
    {
        return implode(', ', array_map(
            fn (array $range): string => $range['from'].($range['until'] !== null ? '–'.$range['until'] : ''),
            $this->ranges(),
        ));
    }
}
