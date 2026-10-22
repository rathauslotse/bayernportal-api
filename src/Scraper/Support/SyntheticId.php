<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Support;

/**
 * Stable integer ids for entities the website shows without an id
 * (buildings and contacts). They are derived from the visible content, so
 * they survive repeated scrapes but are not the REST API's ids.
 *
 * @internal
 */
final class SyntheticId
{
    public static function building(int $authorityId, string $name, ?string $street, ?string $postalCode): int
    {
        return self::of('building', (string) $authorityId, $name, (string) $street, (string) $postalCode);
    }

    public static function contact(int $authorityId, string $name): int
    {
        return self::of('contact', (string) $authorityId, $name);
    }

    public static function of(string ...$parts): int
    {
        $normalised = array_map(static fn (string $part): string => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $part))), $parts);

        // 13 hex digits = 52 bits: positive and exact on every platform.
        return (int) hexdec(substr(sha1(implode('|', $normalised)), 0, 13)) ?: 1;
    }
}
