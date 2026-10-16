<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Stringable;

/**
 * Street or postal address.
 */
final readonly class Address implements Stringable
{
    public function __construct(
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
    ) {}

    /**
     * Returns null when every part is empty.
     */
    public static function fromParts(?string $street, ?string $postalCode, ?string $city): ?self
    {
        if ($street === null && $postalCode === null && $city === null) {
            return null;
        }

        return new self($street, $postalCode, $city);
    }

    /**
     * Single-line representation, e.g. "Marienplatz 8, 80331 München".
     */
    public function format(string $separator = ', '): string
    {
        $locality = trim(($this->postalCode ?? '').' '.($this->city ?? ''));

        return implode($separator, array_filter([$this->street, $locality], fn (?string $part): bool => $part !== null && $part !== ''));
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
