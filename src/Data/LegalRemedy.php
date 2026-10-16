<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Available legal remedy (Rechtsbehelf), optionally pointing to a service
 * that describes it.
 */
final readonly class LegalRemedy
{
    public function __construct(
        public ?string $summary = null,
        public ?ServiceReference $service = null,
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(?Payload $payload): ?self
    {
        if ($payload === null || $payload->isEmpty()) {
            return null;
        }

        return new self(
            summary: $payload->string('kurzbeschreibung', 'value'),
            service: ServiceReference::fromPayload(Payload::of([
                'leistungId' => $payload->get('leistungId'),
                'leistungBezeichnung' => $payload->get('leistungBezeichnung'),
                'leistungUrl' => $payload->get('leistungUrl'),
            ])),
        );
    }
}
