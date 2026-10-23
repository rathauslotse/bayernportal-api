<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\UnsupportedByScraperException;
use Rathauslotse\BayernPortal\Http\ApiResponse;
use Rathauslotse\BayernPortal\Http\Query;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Pages\AuthorityPage;
use Rathauslotse\BayernPortal\Scraper\Pages\BuildingsPage;
use Rathauslotse\BayernPortal\Scraper\Pages\ContactsPage;
use Rathauslotse\BayernPortal\Scraper\Pages\LifeSituationPage;
use Rathauslotse\BayernPortal\Scraper\Pages\OnlineProceduresPage;
use Rathauslotse\BayernPortal\Scraper\Pages\ServiceDescriptionPage;
use Rathauslotse\BayernPortal\Scraper\Pages\ServiceListPage;
use Rathauslotse\BayernPortal\Scraper\Support\Links;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Serves REST API paths from the public website www.bayernportal.de.
 *
 * Every route scrapes one or more pages and answers with the JSON the REST
 * API would return, so resources, DTOs and caching work unchanged. Paths
 * without a website equivalent throw {@see UnsupportedByScraperException}.
 *
 * Aggregating routes (e.g. all service descriptions of a municipality)
 * fetch their items through {@see self::useForSubrequests()}, normally the
 * caching decorator, so every item is cached on its own and an interrupted
 * run resumes where it stopped.
 */
final class ScrapingConnector implements Connector
{
    /**
     * Path pattern → handler method. The first match wins.
     */
    private const array ROUTES = [
        '#^dienststellen/(\d{7})$#' => 'agency',
        '#^dienststellen/(\d{7})/leistungen$#' => 'agencyServices',
        '#^dienststellen/(\d{7})/leistungsbeschreibungen$#' => 'agencyServiceDescriptions',
        '#^dienststellen/(\d{7})/formulare$#' => 'agencyForms',
        '#^dienststellen/(\d{7})/onlineverfahren$#' => 'agencyOnlineProcedures',
        '#^behoerden/(\d+)$#' => 'authority',
        '#^behoerden/(\d+)/gebaeude/(\d+)$#' => 'building',
        '#^behoerden/(\d+)/gebaeude/(\d+)/ansprechpartner/(\d+)$#' => 'buildingContact',
        '#^behoerden/(\d+)/ansprechpartner$#' => 'authorityContacts',
        '#^behoerden/(\d+)/leistungen$#' => 'authorityServices',
        '#^leistungen$#' => 'services',
        '#^leistungen/(\d+)$#' => 'service',
        '#^leistungsbeschreibungen$#' => 'serviceDescriptions',
        '#^leistungsbeschreibungen/(\d+)$#' => 'serviceDescription',
        '#^lebenslagen$#' => 'lifeSituations',
        '#^lebenslagen/(\d+)$#' => 'lifeSituation',
    ];

    private ?Connector $subrequests = null;

    public function __construct(
        private readonly PortalClient $client,
        private readonly MunicipalityIndex $index,
        private readonly ScraperState $state,
    ) {}

    /**
     * Connector used for the per-item requests of aggregating routes.
     */
    public function useForSubrequests(Connector $connector): void
    {
        $this->subrequests = $connector;
    }

    public function get(string $path, array $query = []): ApiResponse
    {
        $path = trim($path, '/');
        $query = array_map(strval(...), Query::normalise($query));

        foreach (self::ROUTES as $pattern => $handler) {
            if (preg_match($pattern, $path, $match) === 1) {
                /** @var array<array-key, mixed> $payload */
                $payload = $this->{$handler}(array_slice($match, 1), $query, $path);

                return ApiResponse::make(200, (string) json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), [
                    'Content-Type' => 'application/json',
                ]);
            }
        }

        throw UnsupportedByScraperException::forPath($path);
    }

    // ---------------------------------------------------------------- agencies

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function agency(array $match, array $query, string $path): array
    {
        $municipality = $this->agencyMunicipality($match[0], $path);

        return ['dienststelle' => [
            ...$this->authorityPayload($municipality['id']),
            'dienststellenschluessel' => $match[0],
            'dienststelleLfdNr' => 0,
            'gemeindekennziffer' => $municipality['key'],
        ]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function agencyServices(array $match, array $query, string $path): array
    {
        return ['leistungen' => ['leistung' => $this->municipalityServices($this->agencyMunicipality($match[0], $path)['id'])]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function agencyServiceDescriptions(array $match, array $query, string $path): array
    {
        $municipality = $this->agencyMunicipality($match[0], $path);

        return ['leistungsbeschreibungen' => ['leistungsbeschreibung' => $this->descriptionsFor($municipality, $query)]];
    }

    /**
     * Forms are only listed on the service descriptions, so this reads
     * every service description of the municipality.
     *
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function agencyForms(array $match, array $query, string $path): array
    {
        $municipality = $this->agencyMunicipality($match[0], $path);
        $grouped = [];
        $flat = [];

        foreach ($this->descriptionsFor($municipality, ['mitRegionalenErgaenzungen' => 'true']) as $description) {
            $forms = Payload::of($description)->list('formulare', 'formular');

            if ($forms === []) {
                continue;
            }

            $service = ['id' => $description['id'] ?? null, 'bezeichnung' => $description['bezeichnung'] ?? null, 'url' => $description['url'] ?? null];
            $grouped[] = [...$service, 'formulare' => ['formular' => array_map(static fn (Payload $form): array => $form->raw(), $forms)]];

            foreach ($forms as $form) {
                $flat[] = [...$form->raw(), 'leistungen' => ['leistung' => [$service]]];
            }
        }

        return ($query['gruppiertNachLeistungen'] ?? 'false') === 'true'
            ? ['formulare' => ['leistungMitFormularen' => $grouped]]
            : ['formulare' => ['formular' => $flat]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function agencyOnlineProcedures(array $match, array $query, string $path): array
    {
        $municipality = $this->agencyMunicipality($match[0], $path);
        $groups = OnlineProceduresPage::parse($this->fetch('dokumente/behoerde/'.$municipality['id'].'/onlineverfahren', $this->localisation($municipality['id']) ?? []));

        if (($query['gruppiertNachLeistungen'] ?? 'false') === 'true') {
            return ['onlineverfahren' => ['leistungMitOnlineverfahren' => $groups]];
        }

        // A procedure can serve several services; list it once with all of them.
        $procedures = [];
        $services = [];

        foreach ($groups as $group) {
            foreach ($group['onlineVerfahren'] as $procedure) {
                $procedures[$procedure['id']] ??= $procedure;
                $services[$procedure['id']][] = ['id' => $group['id'], 'bezeichnung' => $group['bezeichnung'], 'url' => $group['url']];
            }
        }

        foreach ($procedures as $id => $procedure) {
            $procedures[$id] = [...$procedure, 'leistungen' => ['leistung' => $services[$id]]];
        }

        return ['onlineverfahren' => ['onlineVerfahren' => array_values($procedures)]];
    }

    // ------------------------------------------------------------- authorities

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function authority(array $match, array $query, string $path): array
    {
        [$page, $portalId] = $this->fetchById('behoerde', (int) $match[0]);

        return ['behoerde' => $this->authorityPayload($portalId, $page)];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function building(array $match, array $query, string $path): array
    {
        [$page, $portalId] = $this->fetchById('behoerde', (int) $match[0], '/adressen');

        foreach (BuildingsPage::parse($page, (int) $portalId) as $building) {
            if (($building['gebaeudeId'] ?? null) === (int) $match[1]) {
                return ['BehoerdenGebaeude' => $building];
            }
        }

        return ['BehoerdenGebaeude' => []];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function buildingContact(array $match, array $query, string $path): array
    {
        $building = $this->building($match, $query, $path)['BehoerdenGebaeude'];
        $contactIds = is_array($building) ? Payload::of($building)->ids('behoerdenAnsprechpartnerZuordnungen', 'ansprechpartner') : [];

        if (! in_array((int) $match[2], $contactIds, true)) {
            return ['ansprechpartner' => []];
        }

        foreach ($this->contacts((int) $match[0]) as $contact) {
            if (($contact['ansprechpartnerId'] ?? null) === (int) $match[2]) {
                return ['ansprechpartner' => [...$contact, 'gebaeudeId' => (int) $match[1], 'gebaeudeBezeichnung' => $building['bezeichnung'] ?? null]];
            }
        }

        return ['ansprechpartner' => []];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function authorityContacts(array $match, array $query, string $path): array
    {
        return ['ansprechpartner' => ['ap' => $this->contacts((int) $match[0])]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function authorityServices(array $match, array $query, string $path): array
    {
        $direct = ($query['nurDirekteLeistungszuordnungen'] ?? 'false') === 'true';
        [$page] = $this->fetchById('behoerde', (int) $match[0], '/leistungen', $direct ? [] : ['rekursiv' => 'true']);

        return ['leistungen' => ['leistung' => ServiceListPage::parse($page)]];
    }

    // ---------------------------------------------------------------- services

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function services(array $match, array $query, string $path): array
    {
        $key = $query['gemeindekennziffer'] ?? null;

        if ($key !== null) {
            return ['leistungen' => ['leistung' => $this->municipalityServices($this->municipality($key, $path)['id'])]];
        }

        return ['leistungen' => ['leistung' => ServiceListPage::parse($this->fetch('suche/leistung/alphabetisch'))]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function service(array $match, array $query, string $path): array
    {
        return ['leistung' => $this->descriptionPayload((int) $match[0], $query, $path)];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function serviceDescriptions(array $match, array $query, string $path): array
    {
        $key = $query['gemeindekennziffer'] ?? null;

        if ($key === null) {
            throw UnsupportedByScraperException::forPath($path, 'reading all ~3,400 service descriptions takes about an hour; pass a municipality key or list services() and fetch the descriptions you need');
        }

        $municipality = ['key' => $key, ...$this->municipality($key, $path)];

        return ['leistungsbeschreibungen' => ['leistungsbeschreibung' => $this->descriptionsFor($municipality, $query)]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function serviceDescription(array $match, array $query, string $path): array
    {
        return ['leistungsbeschreibung' => $this->descriptionPayload((int) $match[0], $query, $path)];
    }

    // --------------------------------------------------------- life situations

    /**
     * Crawls the category trees: one request per life situation.
     *
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function lifeSituations(array $match, array $query, string $path): array
    {
        $connector = $this->subrequests ?? $this;
        $queue = [];
        $situations = [];

        foreach (LifeSituationPage::CATEGORIES as $category) {
            array_push($queue, ...LifeSituationPage::children($this->fetch('suche/lebenslage/hierarchisch/'.$category)));
        }

        while (($id = array_shift($queue)) !== null) {
            if (isset($situations[$id])) {
                continue;
            }

            $situation = Payload::single($connector->get('lebenslagen/'.$id)->json(), 'lebenslage')->raw();
            $situations[$id] = $situation;

            foreach (Payload::of($situation)->strings('unterlebenslagen') as $child) {
                $queue[] = $child;
            }
        }

        if (($query['leistungenZugeordnet'] ?? null) === 'true') {
            $situations = array_filter($situations, static fn (array $situation): bool => isset($situation['leistungen']));
        }

        return ['lebenslagen' => ['lebenslage' => array_values($situations)]];
    }

    /**
     * @param  list<string>  $match
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function lifeSituation(array $match, array $query, string $path): array
    {
        return ['lebenslage' => LifeSituationPage::parse($this->fetch('dokumente/lebenslage/'.$match[0]), $match[0])];
    }

    // ----------------------------------------------------------------- helpers

    /**
     * @return array<string, mixed>
     */
    private function authorityPayload(string $portalId, ?Document $page = null): array
    {
        $payload = AuthorityPage::parse($page ?? $this->fetch('dokumente/behoerde/'.$portalId), $portalId);
        $buildings = BuildingsPage::parse($this->fetch('dokumente/behoerde/'.$portalId.'/adressen'), (int) $portalId);

        if ($buildings !== []) {
            $payload['behoerdenGebaeudeZuordnungen'] = ['gebaeude' => array_map(
                static fn (array $building): array => ['id' => $building['gebaeudeId'] ?? null],
                $buildings,
            )];
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contacts(int $authorityId): array
    {
        [$page, $portalId] = $this->fetchById('behoerde', $authorityId, '/mitarbeiter/alle');

        return ContactsPage::parse($page, (int) $portalId);
    }

    /**
     * Services of a municipality, including those of its administrative
     * association (Verwaltungsgemeinschaft), which handles most tasks of
     * its member municipalities. The municipality's own services come first.
     *
     * @return list<array{id: int, portalId: string, bezeichnung: string, url: string}>
     */
    private function municipalityServices(string $portalId): array
    {
        $page = $this->ownServices($portalId);
        $services = ServiceListPage::parse($page);
        $association = ServiceListPage::administrativeAssociationId($page);

        if ($association === null) {
            return $services;
        }

        $known = array_column($services, 'portalId', 'portalId');
        $associationPage = $this->fetch('dokumente/behoerde/'.$association.'/leistungen', ['rekursiv' => 'true']);

        foreach (ServiceListPage::parse($associationPage) as $service) {
            if (! isset($known[$service['portalId']])) {
                $services[] = $service;
            }
        }

        return $services;
    }

    /**
     * The municipality's own service list. Remembers its localisation, so
     * service descriptions keep the municipality's local context even when
     * the service belongs to the administrative association.
     */
    private function ownServices(string $portalId): Document
    {
        $page = $this->fetch('dokumente/behoerde/'.$portalId.'/leistungen', ['rekursiv' => 'true']);
        $context = ServiceListPage::localisation($page);

        if ($context !== null) {
            $this->state->rememberLocalisation($portalId, $context);
        }

        return $page;
    }

    /**
     * Full service descriptions of a municipality, one subrequest each.
     *
     * @param  array{key: string, id: string, name: string}  $municipality
     * @param  array<string, string>  $query
     * @return list<array<array-key, mixed>>
     */
    private function descriptionsFor(array $municipality, array $query): array
    {
        $connector = $this->subrequests ?? $this;
        $withSupplements = ($query['mitRegionalenErgaenzungen'] ?? 'false') === 'true';
        $services = Payload::items($connector->get('leistungen', ['gemeindekennziffer' => $municipality['key']])->json(), 'leistungen', 'leistung');
        $descriptions = [];

        foreach ($services as $service) {
            // Always request the supplements: it is the same page, so every
            // variant of this aggregate shares one cache entry per service.
            $description = Payload::single($connector->get('leistungsbeschreibungen/'.$service->string('id'), [
                'gemeindekennziffer' => $municipality['key'],
                'mitRegionalenErgaenzungen' => 'true',
            ])->json(), 'leistungsbeschreibung')->raw();

            if (! $withSupplements) {
                unset($description['regionaleErgaenzungen']);
            }

            $descriptions[] = $description;
        }

        return $descriptions;
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function descriptionPayload(int $serviceId, array $query, string $path): array
    {
        $key = $query['gemeindekennziffer'] ?? null;
        $context = $key === null ? [] : ($this->localisation($this->municipality($key, $path)['id']) ?? []);
        [$page, $portalId] = $this->fetchById('leistung', $serviceId, '', $context);
        $payload = ServiceDescriptionPage::parse($page, $portalId);

        if (($query['mitRegionalenErgaenzungen'] ?? 'true') === 'false') {
            unset($payload['regionaleErgaenzungen']);
        }

        return $payload;
    }

    /**
     * The website's localisation parameters for a municipality, learned
     * from its service list (one extra request the first time).
     *
     * @return array{plz: string, behoerde: string, gemeinde: string}|null
     */
    private function localisation(string $portalId): ?array
    {
        $context = $this->state->localisation($portalId);

        if ($context === null) {
            $this->ownServices($portalId);
            $context = $this->state->localisation($portalId);
        }

        return $context;
    }

    /**
     * @return array{key: string, id: string, name: string}
     */
    private function agencyMunicipality(string $agencyKey, string $path): array
    {
        return $this->index->findByAgencyKey($agencyKey) ?? throw NotFoundException::fromResponse(
            ApiResponse::make(404, sprintf('No municipality matches agency key %s in the scraper index.', $agencyKey)),
            $path,
        );
    }

    /**
     * @return array{key: string, id: string, name: string}
     */
    private function municipality(string $municipalityKey, string $path): array
    {
        $entry = $this->index->find($municipalityKey) ?? throw NotFoundException::fromResponse(
            ApiResponse::make(404, sprintf('Municipality key %s is not in the scraper index. Run "php artisan bayernportal:scrape-index" to refresh it.', $municipalityKey)),
            $path,
        );

        return ['key' => $municipalityKey, ...$entry];
    }

    /**
     * Fetch a document page by integer id. Portal ids may have leading
     * zeros the integer lost, so the known spellings are tried in turn.
     *
     * @param  string  $suffix  Sub-page, e.g. "/adressen".
     * @param  array<string, string>  $query
     * @return array{0: Document, 1: string} The page and the portal id that worked.
     */
    private function fetchById(string $type, int $id, string $suffix = '', array $query = []): array
    {
        $candidates = $this->state->portalIds($id);

        foreach ($candidates as $position => $candidate) {
            try {
                return [$this->fetch('dokumente/'.$type.'/'.$candidate.$suffix, $query), $candidate];
            } catch (NotFoundException $exception) {
                if ($position === count($candidates) - 1) {
                    throw $exception;
                }
            }
        }

        throw NotFoundException::fromResponse(ApiResponse::make(404, ''), 'dokumente/'.$type.'/'.$id);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function fetch(string $path, array $query = []): Document
    {
        $page = $this->client->get($path, $query);
        $this->state->rememberIds(Links::documentIds($page));

        return $page;
    }
}
