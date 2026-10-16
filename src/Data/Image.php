<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Data;

use Rathauslotse\BayernPortal\Support\Payload;

/**
 * Embedded image such as a logo (API type "Bild"): base64 data plus metadata.
 */
final readonly class Image
{
    public function __construct(
        public string $base64,
        public ?string $mimeType = null,
        public ?string $alt = null,
        public ?string $title = null,
        public ?string $source = null,
    ) {}

    /**
     * @internal
     */
    public static function fromValue(mixed $value): ?self
    {
        if (is_string($value)) {
            $value = ['value' => $value];
        }

        $payload = Payload::of($value);
        $data = $payload->string('value', '$', '#text', 'content', 'data', 'base64');

        if ($data === null) {
            return null;
        }

        return new self(
            base64: preg_replace('/\s+/', '', $data) ?? $data,
            mimeType: $payload->string('mimetype', 'mimeType'),
            alt: $payload->string('alt'),
            title: $payload->string('title'),
            source: $payload->string('quelle'),
        );
    }

    /**
     * Decoded binary content.
     */
    public function contents(): string
    {
        return base64_decode($this->base64, true) ?: '';
    }

    /**
     * "data:" URI for direct use in an <img src>.
     */
    public function toDataUri(): string
    {
        return 'data:'.($this->mimeType ?? 'application/octet-stream').';base64,'.$this->base64;
    }
}
