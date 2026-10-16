<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A service together with its online procedures (agency online procedures
 * grouped by service).
 */
final readonly class ServiceWithOnlineProcedures
{
    /**
     * @param  list<OnlineProcedure>  $onlineProcedures
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ServiceReference $service,
        public array $onlineProcedures = [],
        public array $raw = [],
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        $procedures = $payload->list('onlineVerfahren', 'onlineVerfahren')
            ?: $payload->list('onlineverfahren', 'onlineverfahren');

        return new self(
            service: ServiceReference::fromPayload($payload) ?? new ServiceReference(0),
            onlineProcedures: OnlineProcedure::many($procedures),
            raw: $payload->raw(),
        );
    }
}
