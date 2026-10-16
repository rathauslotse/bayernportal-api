<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Http;

/**
 * Connection settings for {@see HttpConnector}.
 */
final readonly class ConnectorOptions
{
    public const string DEFAULT_BASE_URL = 'https://www.bayernportal-webservices.bayern.de/rest/allgemein/v3/';

    /**
     * @param  int  $retryTimes  Additional attempts after the first one fails (0 disables retries).
     */
    public function __construct(
        public string $baseUrl = self::DEFAULT_BASE_URL,
        public ?string $username = null,
        public ?string $password = null,
        public int $timeout = 30,
        public int $connectTimeout = 10,
        public int $retryTimes = 2,
        public int $retrySleepMilliseconds = 250,
    ) {}

    /**
     * @param  array<array-key, mixed>  $config  The "bayernportal" config array.
     */
    public static function fromConfig(array $config): self
    {
        $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];

        return new self(
            baseUrl: self::string($config['base_url'] ?? null) ?? self::DEFAULT_BASE_URL,
            username: self::string($config['username'] ?? null),
            password: self::string($config['password'] ?? null),
            timeout: self::int($config['timeout'] ?? null, 30),
            connectTimeout: self::int($config['connect_timeout'] ?? null, 10),
            retryTimes: max(0, self::int($retry['times'] ?? null, 2)),
            retrySleepMilliseconds: max(0, self::int($retry['sleep_ms'] ?? null, 250)),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function int(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}
