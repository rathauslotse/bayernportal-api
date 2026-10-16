<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown before any HTTP request is sent when an argument violates the
 * constraints documented by the BayernPortal API.
 */
class InvalidParameterException extends BayernPortalException {}
