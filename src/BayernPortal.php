<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal;

use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Entry point of the package.
 *
 * Instances are immutable: {@see self::forMunicipality()} returns a copy.
 */
final class BayernPortal
{
    public function __construct(
        protected readonly Connector $connector,
        protected readonly ?string $municipalityKey = null,
    ) {
        Validate::municipalityKey($municipalityKey);
    }

    /**
     * Return a client that sends the given official municipality key
     * (Gemeindekennziffer) with every request that supports it.
     *
     * An explicit `municipalityKey` argument on a single call still wins.
     */
    public function forMunicipality(?string $municipalityKey): self
    {
        return new self($this->connector, $municipalityKey);
    }

    /**
     * The municipality key this client is scoped to, if any.
     */
    public function municipalityKey(): ?string
    {
        return $this->municipalityKey;
    }

    public function connector(): Connector
    {
        return $this->connector;
    }
}
