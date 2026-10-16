<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Data\Authority;
use Rathauslotse\BayernPortal\Data\Building;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Data\ServiceReference;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Pagination\Page;
use Rathauslotse\BayernPortal\Support\Payload;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Authorities (Behörden), their buildings, contacts and services.
 *
 * REST resource: `behoerden`.
 */
final class AuthorityResource extends Resource
{
    private const array LIST = ['behoerden', 'behoerde'];

    /**
     * One page of authorities.
     *
     * `GET behoerden?full=&page=&size=`
     *
     * @return Page<Authority>
     *
     * @throws BayernPortalException
     */
    public function list(bool $full = false, int $page = 0, int $size = 100): Page
    {
        return $this->page('behoerden', ['full' => $full], $page, $size, self::LIST, Authority::fromPayload(...));
    }

    /**
     * All authorities, fetched page by page while iterating.
     *
     * @return LazyCollection<int, Authority>
     */
    public function all(bool $full = false, int $pageSize = 100): LazyCollection
    {
        return $this->lazy('behoerden', ['full' => $full], $pageSize, self::LIST, Authority::fromPayload(...));
    }

    /**
     * A single authority.
     *
     * `GET behoerden/{behoerde-id}?sicheresKontaktformular=`
     *
     * @param  bool  $withSecureContactForm  Include the link to the secure contact form (sicheresKontaktformular).
     *
     * @throws BayernPortalException
     */
    public function find(int $authorityId, bool $withSecureContactForm = false): Authority
    {
        return $this->one($this->path($authorityId), [
            'sicheresKontaktformular' => $withSecureContactForm,
        ], self::LIST, Authority::fromPayload(...));
    }

    /**
     * A building of an authority, with addresses and opening hours.
     *
     * `GET behoerden/{behoerde-id}/gebaeude/{gebaeude-id}`
     *
     * @throws BayernPortalException
     */
    public function building(int $authorityId, int $buildingId): Building
    {
        return $this->one(
            $this->path($authorityId, 'gebaeude/'.Validate::id($buildingId, 'building id')),
            [],
            ['BehoerdenGebaeude|behoerdenGebaeude|gebaeude'],
            Building::fromPayload(...),
        );
    }

    /**
     * A contact person in a building of an authority.
     *
     * `GET behoerden/{behoerde-id}/gebaeude/{gebaeude-id}/ansprechpartner/{ansprechpartner-id}`
     *
     * @throws BayernPortalException
     */
    public function buildingContact(int $authorityId, int $buildingId, int $contactId): Contact
    {
        return $this->one(
            $this->path($authorityId, sprintf('gebaeude/%d/ansprechpartner/%d', Validate::id($buildingId, 'building id'), Validate::id($contactId, 'contact id'))),
            [],
            ['ansprechpartner', 'ap'],
            Contact::fromPayload(...),
        );
    }

    /**
     * All contact persons of an authority.
     *
     * `GET behoerden/{behoerde-id}/ansprechpartner`
     *
     * @return Collection<int, Contact>
     *
     * @throws BayernPortalException
     */
    public function contacts(int $authorityId): Collection
    {
        return $this->many($this->path($authorityId, 'ansprechpartner'), [], ['ansprechpartner', 'ap'], Contact::fromPayload(...));
    }

    /**
     * Services the authority is responsible for.
     *
     * `GET behoerden/{behoerde-id}/leistungen?nurDirekteLeistungszuordnungen=`
     *
     * @param  bool|null  $onlyDirectAssignments  Only services assigned directly to the authority (nurDirekteLeistungszuordnungen); null uses the API default.
     * @return Collection<int, ServiceReference>
     *
     * @throws BayernPortalException
     */
    public function services(int $authorityId, ?bool $onlyDirectAssignments = null): Collection
    {
        return $this->references($this->path($authorityId, 'leistungen'), [
            'nurDirekteLeistungszuordnungen' => $onlyDirectAssignments,
        ]);
    }

    /**
     * Services delegated by a district government (Bezirksregierung).
     *
     * `GET behoerden/{behoerde-id}/abgegebene-leistungen`
     *
     * Only available for the seven Bavarian district governments; the API
     * answers with HTTP 400 (→ RequestException) for other authorities.
     *
     * @return Collection<int, ServiceReference>
     *
     * @throws BayernPortalException
     */
    public function delegatedServices(int $authorityId): Collection
    {
        return $this->references($this->path($authorityId, 'abgegebene-leistungen'), []);
    }

    /**
     * @param  array<string, bool|null>  $query
     * @return Collection<int, ServiceReference>
     */
    private function references(string $path, array $query): Collection
    {
        /** @var Collection<int, ServiceReference> */
        return $this->many($path, $query, ['leistungen', 'leistung'], fn (Payload $payload): ?ServiceReference => ServiceReference::fromPayload($payload))
            ->filter()
            ->values();
    }

    private function path(int $authorityId, ?string $sub = null): string
    {
        return 'behoerden/'.Validate::id($authorityId, 'authority id').($sub === null ? '' : '/'.$sub);
    }
}
