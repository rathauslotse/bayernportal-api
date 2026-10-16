<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Carbon\CarbonImmutable;
use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A public service (Leistung), e.g. "Personalausweis beantragen".
 */
readonly class Service
{
    /**
     * @param  string|null  $url  Service page on the BayernPortal.
     * @param  list<string>  $synonyms  Alternative search terms.
     * @param  list<LifeSituationReference>  $lifeSituations  Life situations the service belongs to.
     * @param  array<array-key, mixed>  $raw  The unmodified API payload.
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $url = null,
        public array $synonyms = [],
        public array $lifeSituations = [],
        public ?CarbonImmutable $lastModifiedAt = null,
        public array $raw = [],
    ) {}

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
        return new self(...self::serviceArguments($payload));
    }

    /**
     * Named constructor arguments shared with {@see ServiceDescription}.
     *
     * @return array{id: int, name: string, url: ?string, synonyms: list<string>, lifeSituations: list<LifeSituationReference>, lastModifiedAt: ?CarbonImmutable, raw: array<array-key, mixed>}
     */
    protected static function serviceArguments(Payload $payload): array
    {
        return [
            'id' => $payload->int('id', 'leistungId') ?? 0,
            'name' => $payload->string('bezeichnung', 'leistungBezeichnung') ?? '',
            'url' => $payload->string('url', 'leistungUrl'),
            'synonyms' => $payload->strings('synonyme', 'synonym'),
            'lifeSituations' => LifeSituationReference::many($payload->list('lebenslagen', 'lebenslage')),
            'lastModifiedAt' => $payload->date('letzteAenderung'),
            'raw' => $payload->raw(),
        ];
    }
}
