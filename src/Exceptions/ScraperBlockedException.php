<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Exceptions;

/**
 * Thrown when www.bayernportal.de answers with a rate limit or a bot
 * challenge (captcha) instead of the requested page. Increase
 * "bayernportal.scraper.throttle_ms" and try again later.
 */
class ScraperBlockedException extends BayernPortalException {}
