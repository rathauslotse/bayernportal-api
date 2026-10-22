<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

/**
 * Connection settings for {@see PortalClient}.
 */
final readonly class ScraperOptions
{
    public const string DEFAULT_BASE_URL = 'https://www.bayernportal.de/';

    /**
     * @param  int  $throttleMilliseconds  Minimum delay between two page requests.
     * @param  int  $retryTimes  Additional attempts after the first one fails (0 disables retries).
     */
    public function __construct(
        public string $baseUrl = self::DEFAULT_BASE_URL,
        public int $throttleMilliseconds = 1000,
        public int $timeout = 30,
        public int $connectTimeout = 10,
        public int $retryTimes = 2,
        public int $retrySleepMilliseconds = 2000,
    ) {}

    /**
     * @param  array<array-key, mixed>  $config  The "bayernportal" config array.
     */
    public static function fromConfig(array $config): self
    {
        $scraper = is_array($config['scraper'] ?? null) ? $config['scraper'] : [];
        $retry = is_array($scraper['retry'] ?? null) ? $scraper['retry'] : [];

        return new self(
            baseUrl: is_string($scraper['base_url'] ?? null) && $scraper['base_url'] !== '' ? $scraper['base_url'] : self::DEFAULT_BASE_URL,
            throttleMilliseconds: max(0, self::int($scraper['throttle_ms'] ?? null, 1000)),
            timeout: self::int($scraper['timeout'] ?? null, 30),
            connectTimeout: self::int($config['connect_timeout'] ?? null, 10),
            retryTimes: max(0, self::int($retry['times'] ?? null, 2)),
            retrySleepMilliseconds: max(0, self::int($retry['sleep_ms'] ?? null, 2000)),
        );
    }

    private static function int(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}
