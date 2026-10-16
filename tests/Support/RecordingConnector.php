<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Tests\Support;

use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Http\ApiResponse;
use Rathauslotse\BayernPortal\Http\Query;

/**
 * Writes every live response to disk so the bundled fixtures and mappers
 * can be compared against real data (see tests/Contract).
 */
final class RecordingConnector implements Connector
{
    public function __construct(
        private readonly Connector $connector,
        private readonly string $directory,
    ) {}

    public function get(string $path, array $query = []): ApiResponse
    {
        $response = $this->connector->get($path, $query);
        $normalised = Query::normalise($query);
        $path = ltrim($path, '/');

        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }

        $name = str_replace('/', '__', $path).($normalised === [] ? '' : '--'.substr(sha1(http_build_query($normalised)), 0, 8));

        file_put_contents($this->directory.'/'.$name.'.json', json_encode([
            'path' => $path,
            'query' => $normalised,
            'status' => $response->status,
            'headers' => $response->headers,
            'body' => json_decode($response->body, true) ?? $response->body,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }
}
