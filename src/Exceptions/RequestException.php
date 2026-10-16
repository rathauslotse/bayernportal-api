<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown for HTTP 4xx responses that have no more specific exception, most notably HTTP 400, which the API uses for "nothing found" and "no permission" cases.
 */
class RequestException extends ResponseException {}
