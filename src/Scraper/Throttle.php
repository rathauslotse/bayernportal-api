<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Enforces a minimum delay between two requests to the public website.
 *
 * With a cache repository the last request time is shared between
 * processes (e.g. several queue workers); otherwise it is per process.
 * The shared variant is best effort and not a strict mutex.
 *
 * @internal
 */
final class Throttle
{
    private const string KEY = 'bayernportal:scraper:last-request';

    private float $lastRequestAt = 0.0;

    /** @var Closure(int): void */
    private Closure $sleep;

    /** @var Closure(): float */
    private Closure $clock;

    /**
     * @param  (Closure(int): void)|null  $sleep  Receives microseconds.
     * @param  (Closure(): float)|null  $clock  Returns seconds as float.
     */
    public function __construct(
        private readonly int $intervalMilliseconds,
        private readonly ?Repository $cache = null,
        ?Closure $sleep = null,
        ?Closure $clock = null,
    ) {
        $this->sleep = $sleep ?? static function (int $microseconds): void {
            usleep($microseconds);
        };
        $this->clock = $clock ?? static fn (): float => microtime(true);
    }

    public function wait(): void
    {
        if ($this->intervalMilliseconds <= 0) {
            return;
        }

        $last = max($this->lastRequestAt, $this->sharedLastRequestAt());
        $remaining = $last + $this->intervalMilliseconds / 1000 - ($this->clock)();

        if ($remaining > 0) {
            ($this->sleep)((int) ceil($remaining * 1_000_000));
        }

        $this->lastRequestAt = ($this->clock)();
        $this->cache?->put(self::KEY, $this->lastRequestAt, 60);
    }

    private function sharedLastRequestAt(): float
    {
        $value = $this->cache?->get(self::KEY);

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
