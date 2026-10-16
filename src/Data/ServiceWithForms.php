<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * A service together with its forms (agency forms grouped by service).
 */
final readonly class ServiceWithForms
{
    /**
     * @param  list<Form>  $forms
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ServiceReference $service,
        public array $forms = [],
        public array $raw = [],
    ) {}

    /**
     * @internal
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            service: ServiceReference::fromPayload($payload) ?? new ServiceReference(0),
            forms: Form::many($payload->list('formulare', 'formular')),
            raw: $payload->raw(),
        );
    }
}
