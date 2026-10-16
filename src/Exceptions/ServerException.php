<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown for HTTP 5xx responses (after retries are exhausted).
 */
class ServerException extends ResponseException {}
