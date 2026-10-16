<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown for HTTP 401 and 403: the credentials are missing, wrong or lack permission.
 */
class AuthenticationException extends RequestException {}
