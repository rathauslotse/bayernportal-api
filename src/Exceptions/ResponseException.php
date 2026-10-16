<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

use Rathauslotse\BayernPortal\Http\ApiResponse;
use Throwable;

/**
 * Base class for exceptions caused by an unsuccessful HTTP response.
 *
 * The API returns its error messages as German plain text. The original
 * text is available via {@see self::apiMessage()}.
 */
abstract class ResponseException extends BayernPortalException
{
    final public function __construct(
        string $message,
        public readonly ApiResponse $response,
        public readonly string $path,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response->status, $previous);
    }

    public static function fromResponse(ApiResponse $response, string $path): static
    {
        $apiMessage = trim($response->body);
        $message = sprintf('BayernPortal API request [GET %s] failed with HTTP %d', $path, $response->status);

        if ($apiMessage !== '') {
            $message .= ': '.mb_strimwidth($apiMessage, 0, 500, '…');
        }

        return new static($message, $response, $path);
    }

    public function status(): int
    {
        return $this->response->status;
    }

    /**
     * The raw error text returned by the API (usually German).
     */
    public function apiMessage(): string
    {
        return trim($this->response->body);
    }
}
