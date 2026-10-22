<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown by the scraper driver for calls the public website cannot serve,
 * e.g. paginated lists of every authority. Switch to the API driver for
 * these. See docs/scraper.md for the supported calls.
 */
class UnsupportedByScraperException extends BayernPortalException
{
    public static function forPath(string $path, string $reason = 'the website has no equivalent page'): self
    {
        return new self(sprintf('The scraper driver cannot serve [GET %s]: %s. Use the API driver for this call.', $path, $reason));
    }
}
