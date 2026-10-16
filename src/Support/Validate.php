<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Support;

use Rathauslotse\BayernPortal\Exceptions\InvalidParameterException;

/**
 * Argument checks that run before a request is sent.
 *
 * @internal
 */
final class Validate
{
    public const int MAX_PAGE_SIZE = 100;

    /**
     * Official municipality key (Amtlicher Gemeindeschlüssel): exactly 8 digits.
     */
    public static function municipalityKey(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        if (preg_match('/^\d{8}$/', $key) !== 1) {
            throw new InvalidParameterException(sprintf(
                'The municipality key must consist of exactly 8 digits (e.g. "09162000" for Munich), "%s" given.',
                $key,
            ));
        }

        return $key;
    }

    /**
     * Agency key (Dienststellenschlüssel): exactly 7 digits.
     */
    public static function agencyKey(string $key): string
    {
        if (preg_match('/^\d{7}$/', $key) !== 1) {
            throw new InvalidParameterException(sprintf(
                'The agency key must consist of exactly 7 digits, "%s" given.',
                $key,
            ));
        }

        return $key;
    }

    public static function page(int $page): int
    {
        if ($page < 0) {
            throw new InvalidParameterException(sprintf('The page number is zero-based and must not be negative, %d given.', $page));
        }

        return $page;
    }

    public static function pageSize(int $size): int
    {
        if ($size < 1 || $size > self::MAX_PAGE_SIZE) {
            throw new InvalidParameterException(sprintf('The page size must be between 1 and %d, %d given.', self::MAX_PAGE_SIZE, $size));
        }

        return $size;
    }

    /**
     * Numeric identifiers (authority, building, contact, service): positive integers.
     */
    public static function id(int $id, string $name = 'id'): int
    {
        if ($id < 1) {
            throw new InvalidParameterException(sprintf('The %s must be a positive integer, %d given.', $name, $id));
        }

        return $id;
    }

    /**
     * Life situation identifiers are numeric strings, e.g. "10000100".
     */
    public static function lifeSituationId(string $id): string
    {
        if (preg_match('/^\d+$/', $id) !== 1) {
            throw new InvalidParameterException(sprintf('The life situation id must be numeric, "%s" given.', $id));
        }

        return $id;
    }
}
