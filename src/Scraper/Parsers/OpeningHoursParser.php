<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Parsers;

/**
 * Parses displayed opening hours ("Mo 07:30 Uhr - 12:30 Uhr und 15:00 Uhr
 * - 18:00 Uhr Di …") into the REST "oeffnungszeiten" structure. The first
 * range of a day becomes the morning slot, the second the afternoon slot.
 *
 * @internal
 */
final class OpeningHoursParser
{
    private const string DAY = '(Mo|Di|Mi|Do|Fr|Sa|So)';

    private const string RANGE = '/(\d{1,2}[:.]\d{2})\s*(?:Uhr)?\s*[-–]\s*(\d{1,2}[:.]\d{2})\s*(?:Uhr)?/u';

    /**
     * @return array<string, mixed>|null
     */
    public static function parse(?string $text): ?array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text ?? ''));

        if ($text === '' || str_contains($text, 'keine Öffnungszeiten erfasst')) {
            return null;
        }

        $slots = [];
        preg_match_all('/\b'.self::DAY.'\b\s+(.*?)(?=\s\b'.self::DAY.'\b\s+\d|$)/u', $text, $days, PREG_SET_ORDER);

        foreach ($days as $day) {
            preg_match_all(self::RANGE, $day[2], $ranges, PREG_SET_ORDER);

            if ($ranges === []) {
                continue;
            }

            $slots[] = array_filter([
                'typ' => strtoupper($day[1]),
                'vonVormittags' => self::time($ranges[0][1]),
                'bisVormittags' => self::time($ranges[0][2]),
                'vonNachmittags' => isset($ranges[1]) ? self::time($ranges[1][1]) : null,
                'bisNachmittags' => isset($ranges[1]) ? self::time($ranges[1][2]) : null,
            ], static fn (?string $value): bool => $value !== null);
        }

        $byAppointment = preg_match('/nach (vorheriger )?Vereinbarung/iu', $text) === 1;

        if ($slots === [] && ! $byAppointment) {
            return ['sonderzeiten' => $text];
        }

        return array_filter([
            'oeffnungszeit' => $slots === [] ? null : [['zeitangabe' => $slots]],
            'nachVereinbarung' => $byAppointment,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function time(string $time): string
    {
        [$hours, $minutes] = explode(':', str_replace('.', ':', $time), 2) + [1 => '00'];

        return sprintf('%02d:%s', (int) $hours, $minutes);
    }
}
