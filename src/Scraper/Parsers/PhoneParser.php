<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Parsers;

/**
 * Splits a displayed phone number ("+49 89 46002-0") into the REST payload
 * parts used by {@see \Rathauslotse\BayernPortal\Data\PhoneNumber}.
 *
 * @internal
 */
final class PhoneParser
{
    /**
     * @return array<string, string> Keys: {prefix}Landvorwahl, {prefix}Ortsvorwahl, {prefix}Anlage, {prefix}Durchwahl.
     */
    public static function parts(?string $display, string $prefix): array
    {
        $display = trim((string) preg_replace('/\s+/u', ' ', $display ?? ''));

        if ($display === '') {
            return [];
        }

        $country = null;
        $area = null;
        $rest = $display;

        if (preg_match('/^(\+\d{1,3}|00\d{1,3})\s+(.*)$/', $rest, $match) === 1) {
            $country = '+'.ltrim($match[1], '+0');
            $rest = $match[2];
        }

        if (preg_match('/^\(?(0?\d{2,5})\)?[\s\/]+(.*)$/', $rest, $match) === 1) {
            $area = $match[1];
            $rest = $match[2];
        }

        [$number, $extension] = array_pad(explode('-', str_replace(' ', '', $rest), 2), 2, null);

        return array_filter([
            $prefix.'Landvorwahl' => $country,
            $prefix.'Ortsvorwahl' => $area,
            $prefix.'Anlage' => $number === '' ? null : $number,
            $prefix.'Durchwahl' => $extension === '' ? null : $extension,
        ], static fn (?string $value): bool => $value !== null);
    }
}
