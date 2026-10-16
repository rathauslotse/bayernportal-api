<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Data\Agency;
use Rathauslotse\BayernPortal\Data\Form;
use Rathauslotse\BayernPortal\Data\LifeSituation;
use Rathauslotse\BayernPortal\Data\OnlineProcedure;
use Rathauslotse\BayernPortal\Data\Service;
use Rathauslotse\BayernPortal\Data\ServiceDescription;
use Rathauslotse\BayernPortal\Data\ServiceWithForms;
use Rathauslotse\BayernPortal\Data\ServiceWithOnlineProcedures;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Pagination\Page;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Agencies (Dienststellen) and their services, forms, online procedures,
 * service descriptions and life situations.
 *
 * REST resource: `dienststellen`.
 */
final class AgencyResource extends Resource
{
    private const array LIST = ['dienststellen', 'dienststelle'];

    /**
     * One page of agencies.
     *
     * `GET dienststellen?full=&page=&size=`
     *
     * @param  bool  $full  Include all details (full) instead of the short form.
     * @param  int  $page  Zero-based page number (page).
     * @param  int  $size  Page size, 1–100 (size).
     * @return Page<Agency>
     *
     * @throws BayernPortalException
     */
    public function list(bool $full = false, int $page = 0, int $size = 100): Page
    {
        return $this->page('dienststellen', ['full' => $full], $page, $size, self::LIST, Agency::fromPayload(...));
    }

    /**
     * All agencies, fetched page by page while iterating.
     *
     * @param  bool  $full  Include all details (full).
     * @param  int  $pageSize  Items per request, 1–100 (size).
     * @return LazyCollection<int, Agency>
     */
    public function all(bool $full = false, int $pageSize = 100): LazyCollection
    {
        return $this->lazy('dienststellen', ['full' => $full], $pageSize, self::LIST, Agency::fromPayload(...));
    }

    /**
     * A single agency.
     *
     * `GET dienststellen/{dienststellenschluessel}`
     *
     * @param  string  $agencyKey  7-digit agency key (Dienststellenschlüssel).
     *
     * @throws BayernPortalException
     */
    public function find(string $agencyKey): Agency
    {
        return $this->one($this->path($agencyKey), [], self::LIST, Agency::fromPayload(...));
    }

    /**
     * Services offered by an agency.
     *
     * `GET dienststellen/{dienststellenschluessel}/leistungen?gemeindekennziffer=`
     *
     * @param  string|null  $municipalityKey  8-digit municipality key (gemeindekennziffer) to localise the result.
     * @return Collection<int, Service>
     *
     * @throws BayernPortalException
     */
    public function services(string $agencyKey, ?string $municipalityKey = null): Collection
    {
        return $this->many($this->path($agencyKey, 'leistungen'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
        ], ['leistungen', 'leistung'], Service::fromPayload(...));
    }

    /**
     * Forms of an agency, each with the services it belongs to.
     *
     * `GET dienststellen/{dienststellenschluessel}/formulare?gruppiertNachLeistungen=false&gemeindekennziffer=`
     *
     * @return Collection<int, Form>
     *
     * @throws BayernPortalException
     */
    public function forms(string $agencyKey, ?string $municipalityKey = null): Collection
    {
        return $this->many($this->path($agencyKey, 'formulare'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'gruppiertNachLeistungen' => false,
        ], ['formulare', 'formular'], Form::fromPayload(...));
    }

    /**
     * Forms of an agency, grouped by service.
     *
     * `GET dienststellen/{dienststellenschluessel}/formulare?gruppiertNachLeistungen=true&gemeindekennziffer=`
     *
     * @return Collection<int, ServiceWithForms>
     *
     * @throws BayernPortalException
     */
    public function formsByService(string $agencyKey, ?string $municipalityKey = null): Collection
    {
        return $this->many($this->path($agencyKey, 'formulare'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'gruppiertNachLeistungen' => true,
        ], ['formulare', 'leistungMitFormularen|leistungenMitFormularen'], ServiceWithForms::fromPayload(...));
    }

    /**
     * Online procedures of an agency, each with the services it belongs to.
     *
     * `GET dienststellen/{dienststellenschluessel}/onlineverfahren?gruppiertNachLeistungen=false&gemeindekennziffer=`
     *
     * @return Collection<int, OnlineProcedure>
     *
     * @throws BayernPortalException
     */
    public function onlineProcedures(string $agencyKey, ?string $municipalityKey = null): Collection
    {
        return $this->many($this->path($agencyKey, 'onlineverfahren'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'gruppiertNachLeistungen' => false,
        ], ['onlineverfahren|onlineVerfahren', 'onlineVerfahren|onlineverfahren'], OnlineProcedure::fromPayload(...));
    }

    /**
     * Online procedures of an agency, grouped by service.
     *
     * `GET dienststellen/{dienststellenschluessel}/onlineverfahren?gruppiertNachLeistungen=true&gemeindekennziffer=`
     *
     * @return Collection<int, ServiceWithOnlineProcedures>
     *
     * @throws BayernPortalException
     */
    public function onlineProceduresByService(string $agencyKey, ?string $municipalityKey = null): Collection
    {
        return $this->many($this->path($agencyKey, 'onlineverfahren'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'gruppiertNachLeistungen' => true,
        ], ['onlineverfahren|onlineVerfahren', 'leistungMitOnlineverfahren|leistungMitOnlineVerfahren'], ServiceWithOnlineProcedures::fromPayload(...));
    }

    /**
     * Full descriptions of all services of an agency.
     *
     * `GET dienststellen/{dienststellenschluessel}/leistungsbeschreibungen`
     *
     * @param  bool  $withRegionalSupplements  Include regional supplements (mitRegionalenErgaenzungen).
     * @param  bool  $withResponsibilities  Include responsible authorities (mitZustaendigkeiten).
     * @param  bool  $withInternalIds  Include internal ids of sub-objects (mitIDallgemeineDaten); mostly relevant for IT service providers.
     * @return Collection<int, ServiceDescription>
     *
     * @throws BayernPortalException
     */
    public function serviceDescriptions(
        string $agencyKey,
        ?string $municipalityKey = null,
        bool $withRegionalSupplements = false,
        bool $withResponsibilities = false,
        bool $withInternalIds = false,
    ): Collection {
        return $this->many($this->path($agencyKey, 'leistungsbeschreibungen'), [
            'gemeindekennziffer' => $this->municipality($municipalityKey),
            'mitRegionalenErgaenzungen' => $withRegionalSupplements,
            'mitZustaendigkeiten' => $withResponsibilities,
            'mitIDallgemeineDaten' => $withInternalIds,
        ], ['leistungsbeschreibungen', 'leistungsbeschreibung'], ServiceDescription::fromPayload(...));
    }

    /**
     * Life situations the agency's services are assigned to.
     *
     * `GET dienststellen/{dienststellenschluessel}/lebenslagen`
     *
     * The API answers with HTTP 400 (→ RequestException) when the agency
     * does not exist or has no services.
     *
     * @return Collection<int, LifeSituation>
     *
     * @throws BayernPortalException
     */
    public function lifeSituations(string $agencyKey): Collection
    {
        return $this->many($this->path($agencyKey, 'lebenslagen'), [], ['lebenslagen', 'lebenslage'], LifeSituation::fromPayload(...));
    }

    private function path(string $agencyKey, ?string $sub = null): string
    {
        return 'dienststellen/'.Validate::agencyKey($agencyKey).($sub === null ? '' : '/'.$sub);
    }
}
