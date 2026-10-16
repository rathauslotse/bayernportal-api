<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Enums;

/**
 * Formal requirements of a form (API field "schriftformErfordernis").
 */
enum WrittenFormRequirement: string
{
    /** 1: the form must be signed. */
    case SignatureRequired = '1';

    /** 2: the form must be signed and submitted in person. */
    case SignatureAndPersonalAppearanceRequired = '2';

    /** 3: no signature required. */
    case NoSignatureRequired = '3';

    public function requiresSignature(): bool
    {
        return $this !== self::NoSignatureRequired;
    }

    public function requiresPersonalAppearance(): bool
    {
        return $this === self::SignatureAndPersonalAppearanceRequired;
    }
}
