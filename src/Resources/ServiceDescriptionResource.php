<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Enums\ContactAssignmentMode;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Full service descriptions (Leistungsbeschreibungen).
 *
 * REST resource: `leistungsbeschreibungen`.
 */
final class ServiceDescriptionResource extends Resource
{
    private const array LIST = ['leistungsbeschreibungen', 'leistungsbeschreibung'];

    /**
     * All service descriptions (without responsibilities).
     *
     * `GET leistungsbeschreibungen?gemeindekennziffer=&mitRegionalenErgaenzungen=&mitIDallgemeineDaten=`
     *
     * This is a large response; consider enabling the response cache.
     *
     * @param  bool  $withRegionalSupplements  Include regional supplements (mitRegionalenErgaenzungen).
     * @param  bool  $withInternalIds  Include internal ids of sub-objects (mitIDallgemeineDaten).
     * @return Collection<int, ServiceDescription>
     *
     * @throws BayernPortalException
     */
    public function list(
        ?string $municipalityKey = null,
        bool $withRegionalSupplements = false,
        bool $withInternalIds = false,
    ): Collection {
        return $this->many('leistungsbeschreibungen', [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'mitRegionalenErgaenzungen' => $withRegionalSupplements,
            'mitIDallgemeineDaten' => $withInternalIds,
        ], self::LIST, ServiceDescription::fromPayload(...));
    }

    /**
     * A single service description. With a municipality key, the
     * responsible authorities for that municipality are included.
     *
     * `GET leistungsbeschreibungen/{leistung-id}`
     *
     * @param  bool  $withRegionalSupplements  Include regional supplements (mitRegionalenErgaenzungen, API default: true).
     * @param  bool  $withSecureContactForm  Include secure contact form links of responsible authorities (sicheresKontaktformular).
     * @param  ContactAssignmentMode|null  $contactAssignments  Which contacts to include with responsible authorities (behoerdenAnsprechpartnerZuordnungen); null uses the API default.
     * @param  bool  $withInternalIds  Include internal ids of sub-objects (mitIDallgemeineDaten).
     *
     * @throws BayernPortalException
     */
    public function find(
        int $serviceId,
        ?string $municipalityKey = null,
        bool $withRegionalSupplements = true,
        bool $withSecureContactForm = false,
        ?ContactAssignmentMode $contactAssignments = null,
        bool $withInternalIds = false,
    ): ServiceDescription {
        return $this->one('leistungsbeschreibungen/'.Validate::id($serviceId, 'service id'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'mitRegionalenErgaenzungen' => $withRegionalSupplements,
            'sicheresKontaktformular' => $withSecureContactForm,
            'behoerdenAnsprechpartnerZuordnungen' => $contactAssignments,
            'mitIDallgemeineDaten' => $withInternalIds,
        ], self::LIST, ServiceDescription::fromPayload(...));
    }
}
