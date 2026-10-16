<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal;

use Rathauslotse\BayernPortal\Cache\CachingConnector;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Resources\AgencyResource;
use Rathauslotse\BayernPortal\Resources\AuthorityResource;
use Rathauslotse\BayernPortal\Resources\ContactResource;
use Rathauslotse\BayernPortal\Resources\LifeSituationResource;
use Rathauslotse\BayernPortal\Resources\ServiceDescriptionResource;
use Rathauslotse\BayernPortal\Resources\ServiceResource;
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
     * Agencies (Dienststellen) and their services, forms, online procedures,
     * service descriptions and life situations.
     */
    public function agencies(): AgencyResource
    {
        return new AgencyResource($this->connector, $this->municipalityKey);
    }

    /**
     * Authorities (Behörden), their buildings, contacts and services.
     */
    public function authorities(): AuthorityResource
    {
        return new AuthorityResource($this->connector, $this->municipalityKey);
    }

    /**
     * Contact persons (Ansprechpartner).
     */
    public function contacts(): ContactResource
    {
        return new ContactResource($this->connector, $this->municipalityKey);
    }

    /**
     * Services (Leistungen).
     */
    public function services(): ServiceResource
    {
        return new ServiceResource($this->connector, $this->municipalityKey);
    }

    /**
     * Full service descriptions (Leistungsbeschreibungen).
     */
    public function serviceDescriptions(): ServiceDescriptionResource
    {
        return new ServiceDescriptionResource($this->connector, $this->municipalityKey);
    }

    /**
     * Life situations (Lebenslagen).
     */
    public function lifeSituations(): LifeSituationResource
    {
        return new LifeSituationResource($this->connector, $this->municipalityKey);
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
     * Return a client that bypasses the response cache (if enabled).
     */
    public function withoutCache(): self
    {
        if (! $this->connector instanceof CachingConnector) {
            return $this;
        }

        return new self($this->connector->uncached(), $this->municipalityKey);
    }

    /**
     * Invalidate all cached responses. Does nothing when caching is disabled.
     */
    public function flushCache(): void
    {
        if ($this->connector instanceof CachingConnector) {
            $this->connector->flush();
        }
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
