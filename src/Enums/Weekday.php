<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Enums;

/**
 * Day of the week used in opening and consultation hours.
 *
 * Backed by the API's two-letter German abbreviations (MO … SO).
 */
enum Weekday: string
{
    case Monday = 'MO';
    case Tuesday = 'DI';
    case Wednesday = 'MI';
    case Thursday = 'DO';
    case Friday = 'FR';
    case Saturday = 'SA';
    case Sunday = 'SO';

    /**
     * ISO-8601 day number (1 = Monday … 7 = Sunday).
     */
    public function isoNumber(): int
    {
        return match ($this) {
            self::Monday => 1,
            self::Tuesday => 2,
            self::Wednesday => 3,
            self::Thursday => 4,
            self::Friday => 5,
            self::Saturday => 6,
            self::Sunday => 7,
        };
    }

    public static function fromIsoNumber(int $number): self
    {
        return self::cases()[($number - 1) % 7];
    }
}
