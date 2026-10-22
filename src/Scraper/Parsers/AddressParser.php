<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Parsers;

/**
 * Turns the displayed address lines ("Bahnhofstr. 7", "85540 Haar") into
 * street, postal code and city.
 *
 * @internal
 */
final class AddressParser
{
    /**
     * @param  list<string>  $lines
     * @return array{strasse: ?string, plz: ?string, ort: ?string}|null
     */
    public static function parse(array $lines): ?array
    {
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        if ($lines === []) {
            return null;
        }

        $postalCode = null;
        $city = null;
        $last = $lines[count($lines) - 1];

        if (preg_match('/^(\d{5})\s+(.+)$/u', $last, $match) === 1) {
            $postalCode = $match[1];
            $city = $match[2];
            array_pop($lines);
        }

        return [
            'strasse' => $lines === [] ? null : implode(', ', $lines),
            'plz' => $postalCode,
            'ort' => $city,
        ];
    }

    /**
     * Flat REST keys, e.g. hausanschriftStrasse / hausanschriftPLZ / hausanschriftOrt.
     *
     * @param  list<string>  $lines
     * @return array<string, string>
     */
    public static function flat(array $lines, string $prefix): array
    {
        $address = self::parse($lines);

        if ($address === null) {
            return [];
        }

        return array_filter([
            $prefix.'Strasse' => $address['strasse'],
            $prefix.'PLZ' => $address['plz'],
            $prefix.'Ort' => $address['ort'],
        ], static fn (?string $value): bool => $value !== null);
    }
}
