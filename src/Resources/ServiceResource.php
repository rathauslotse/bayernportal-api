<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Rathauslotse\BayernPortal\Data\Service;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Services (Leistungen).
 *
 * REST resource: `leistungen`.
 */
final class ServiceResource extends Resource
{
    private const array LIST = ['leistungen', 'leistung'];

    /**
     * All services, optionally localised for a municipality.
     *
     * `GET leistungen?gemeindekennziffer=`
     *
     * @return Collection<int, Service>
     *
     * @throws BayernPortalException
     */
    public function list(?string $municipalityKey = null): Collection
    {
        return $this->many('leistungen', [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
        ], self::LIST, Service::fromPayload(...));
    }

    /**
     * A single service.
     *
     * `GET leistungen/{leistung-id}?gemeindekennziffer=`
     *
     * @throws BayernPortalException
     */
    public function find(int $serviceId, ?string $municipalityKey = null): Service
    {
        return $this->one('leistungen/'.Validate::id($serviceId, 'service id'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
        ], self::LIST, Service::fromPayload(...));
    }
}
