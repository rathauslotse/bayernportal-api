<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Http;

use BackedEnum;

/**
 * Serialises query parameters the way the API expects them.
 *
 * @internal
 */
final class Query
{
    /**
     * Drop null values, render booleans as "true"/"false" and enums as their
     * backing value. Keys are sorted so equal queries produce equal strings.
     *
     * @param  array<string, scalar|BackedEnum|null>  $query
     * @return array<string, string|int|float>
     */
    public static function normalise(array $query): array
    {
        $normalised = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalised[$key] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                $value instanceof BackedEnum => $value->value,
                default => $value,
            };
        }

        ksort($normalised);

        return $normalised;
    }
}
