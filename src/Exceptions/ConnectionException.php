<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown when the API could not be reached (DNS, TLS, timeout, …).
 */
class ConnectionException extends BayernPortalException {}
