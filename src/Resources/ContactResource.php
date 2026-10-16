<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Resources;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Rathauslotse\BayernPortal\Data\Contact;
use Rathauslotse\BayernPortal\Data\ServiceReference;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Pagination\Page;
use Rathauslotse\BayernPortal\Support\Payload;
use Rathauslotse\BayernPortal\Support\Validate;

/**
 * Contact persons (Ansprechpartner).
 *
 * REST resource: `ansprechpartner`. The API paginates this list only in
 * its detailed form (`full=true`), hence the split into {@see self::list()}
 * and {@see self::paginate()} / {@see self::all()}.
 */
final class ContactResource extends Resource
{
    private const array LIST = ['ansprechpartner', 'ap'];

    /**
     * All contacts in short form, unpaginated.
     *
     * `GET ansprechpartner?full=false`
     *
     * @return Collection<int, Contact>
     *
     * @throws BayernPortalException
     */
    public function list(): Collection
    {
        return $this->many('ansprechpartner', ['full' => false], self::LIST, Contact::fromPayload(...));
    }

    /**
     * One page of contacts with all details.
     *
     * `GET ansprechpartner?full=true&page=&size=`
     *
     * @return Page<Contact>
     *
     * @throws BayernPortalException
     */
    public function paginate(int $page = 0, int $size = 100): Page
    {
        return $this->page('ansprechpartner', ['full' => true], $page, $size, self::LIST, Contact::fromPayload(...));
    }

    /**
     * All contacts with all details, fetched page by page while iterating.
     *
     * @return LazyCollection<int, Contact>
     */
    public function all(int $pageSize = 100): LazyCollection
    {
        return $this->lazy('ansprechpartner', ['full' => true], $pageSize, self::LIST, Contact::fromPayload(...));
    }

    /**
     * A single contact with logo, communication data, consultation hours and
     * directly assigned services.
     *
     * `GET ansprechpartner/{ansprechpartner-id}`
     *
     * @throws BayernPortalException
     */
    public function find(int $contactId): Contact
    {
        return $this->one($this->path($contactId), [], self::LIST, Contact::fromPayload(...));
    }

    /**
     * Services a contact is responsible for.
     *
     * `GET ansprechpartner/{ansprechpartner-id}/leistungen?nurDirekteLeistungszuordnungen=`
     *
     * @return Collection<int, ServiceReference>
     *
     * @throws BayernPortalException
     */
    public function services(int $contactId, ?bool $onlyDirectAssignments = null): Collection
    {
        /** @var Collection<int, ServiceReference> */
        return $this->many($this->path($contactId).'/leistungen', [
            'nurDirekteLeistungszuordnungen' => $onlyDirectAssignments,
        ], ['leistungen', 'leistung'], fn (Payload $payload): ?ServiceReference => ServiceReference::fromPayload($payload))
            ->filter()
            ->values();
    }

    private function path(int $contactId): string
    {
        return 'ansprechpartner/'.Validate::id($contactId, 'contact id');
    }
}
