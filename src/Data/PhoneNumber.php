<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;
use Stringable;

/**
 * Telephone, fax or mobile number.
 *
 * The API splits numbers into country code (Landvorwahl), area code
 * (Ortsvorwahl), main number (Anlage / Nummer) and extension (Durchwahl).
 */
final readonly class PhoneNumber implements Stringable
{
    public function __construct(
        public ?string $countryCode,
        public ?string $areaCode,
        public ?string $number,
        public ?string $extension = null,
    ) {}

    /**
     * Read a number split into "<prefix>Landvorwahl", "<prefix>Ortsvorwahl",
     * "<prefix>Anlage"/"<prefix>Nummer" and "<prefix>Durchwahl".
     *
     * @internal
     */
    public static function fromPayload(Payload $payload, string $prefix): ?self
    {
        $countryCode = $payload->string($prefix.'Landvorwahl');
        $areaCode = $payload->string($prefix.'Ortsvorwahl');
        $number = $payload->string($prefix.'Anlage', $prefix.'Nummer');
        $extension = $payload->string($prefix.'Durchwahl');

        if ($number === null && $extension === null) {
            return null;
        }

        return new self($countryCode, $areaCode, $number, $extension);
    }

    /**
     * Human-readable form, e.g. "+49 89 233-96000".
     */
    public function format(): string
    {
        $country = self::digits($this->countryCode);
        $area = self::digits($this->areaCode);

        $parts = [];

        if ($country !== '') {
            $parts[] = '+'.ltrim($country, '0');
            $area = ltrim($area, '0');
        }

        if ($area !== '') {
            $parts[] = $area;
        }

        $local = trim($this->number ?? '');

        if ($this->extension !== null && $this->extension !== '') {
            $local .= ($local === '' ? '' : '-').trim($this->extension);
        }

        if ($local !== '') {
            $parts[] = $local;
        }

        return implode(' ', $parts);
    }

    /**
     * Number for "tel:" links: only digits and a leading "+", or null if
     * no country code is known.
     */
    public function toE164(): ?string
    {
        $country = ltrim(self::digits($this->countryCode), '0');

        if ($country === '') {
            return null;
        }

        return '+'.$country.ltrim(self::digits($this->areaCode), '0').self::digits($this->number).self::digits($this->extension);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', $value ?? '') ?? '';
    }
}
