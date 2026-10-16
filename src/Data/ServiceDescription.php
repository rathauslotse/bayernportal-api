<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Carbon\CarbonImmutable;
use Rathauslotse\BayernPortal\Enums\ServiceStatus;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * The full description of a service (Leistungsbeschreibung).
 *
 * Text fields contain HTML as maintained by the editorial office.
 */
final readonly class ServiceDescription extends Service
{
    /**
     * @param  list<string>  $synonyms
     * @param  list<LifeSituationReference>  $lifeSituations
     * @param  string|null  $prerequisites  Requirements (voraussetzungen).
     * @param  string|null  $procedure  Procedure (verfahrensablauf).
     * @param  string|null  $notes  Notes (hinweise).
     * @param  string|null  $deadlines  Deadlines (fristen).
     * @param  string|null  $processingTime  Processing time (bearbeitungsdauer).
     * @param  string|null  $fees  Fees (kosten).
     * @param  list<RequiredDocument>  $requiredDocuments
     * @param  list<Form>  $forms
     * @param  list<OnlineProcedure>  $onlineProcedures
     * @param  list<LegalBasis>  $legalBases
     * @param  list<Link>  $links
     * @param  list<RelatedService>  $relatedServices
     * @param  CarbonImmutable|null  $updatedAt  Date of the editorial status (stand).
     * @param  string|null  $responsibleAuthority  Authority responsible for the content (verantwortlicheBehoerde).
     * @param  list<int>  $regionalSupplementIds  Ids of regional supplements (regionaleErgaenzungen).
     * @param  list<Responsibility>  $responsibilities  Responsible authorities for the requested municipality.
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        int $id,
        string $name,
        ?string $url = null,
        array $synonyms = [],
        array $lifeSituations = [],
        ?CarbonImmutable $lastModifiedAt = null,
        public ?ServiceStatus $status = null,
        public ?string $summary = null,
        public ?string $description = null,
        public ?string $prerequisites = null,
        public ?string $procedure = null,
        public ?string $notes = null,
        public ?string $deadlines = null,
        public ?string $processingTime = null,
        public ?string $fees = null,
        public array $requiredDocuments = [],
        public array $forms = [],
        public array $onlineProcedures = [],
        public array $legalBases = [],
        public ?LegalRemedy $legalRemedy = null,
        public array $links = [],
        public array $relatedServices = [],
        public ?CarbonImmutable $updatedAt = null,
        public ?string $responsibleAuthority = null,
        public array $regionalSupplementIds = [],
        public array $responsibilities = [],
        array $raw = [],
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            url: $url,
            synonyms: $synonyms,
            lifeSituations: $lifeSituations,
            lastModifiedAt: $lastModifiedAt,
            raw: $raw,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return self::fromPayload(Payload::of($data));
    }

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        $onlineProcedures = $payload->list('onlineVerfahren', 'onlineVerfahren')
            ?: $payload->list('onlineverfahren', 'onlineverfahren');

        return new self(
            ...self::serviceArguments($payload),
            status: $payload->enum(ServiceStatus::class, 'status'),
            summary: $payload->string('kurzbeschreibung'),
            description: $payload->string('langbeschreibung'),
            prerequisites: $payload->string('voraussetzungen'),
            procedure: $payload->string('verfahrensablauf'),
            notes: $payload->string('hinweise'),
            deadlines: $payload->string('fristen'),
            processingTime: $payload->string('bearbeitungsdauer'),
            fees: $payload->string('kosten'),
            requiredDocuments: RequiredDocument::many($payload->list('unterlagen', 'unterlage')),
            forms: Form::many($payload->list('formulare', 'formular')),
            onlineProcedures: OnlineProcedure::many($onlineProcedures),
            legalBases: LegalBasis::many($payload->list('rechtsvorschriften', 'rechtsvorschrift')),
            legalRemedy: LegalRemedy::fromPayload($payload->payload('rechtsbehelf')),
            links: Link::many($payload->list('links', 'link')),
            relatedServices: RelatedService::many($payload->list('verwandteLeistungen', 'verwandteLeistung')),
            updatedAt: $payload->date('stand'),
            responsibleAuthority: $payload->string('verantwortlicheBehoerde'),
            regionalSupplementIds: $payload->ids('regionaleErgaenzungen', 'regionaleErgaenzung'),
            responsibilities: array_map(Responsibility::fromPayload(...), $payload->list('zustaendigkeiten', 'zustaendig')),
        );
    }

    public function isLive(): bool
    {
        return $this->status === ServiceStatus::Live;
    }
}
