<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Http;

use Composer\InstalledVersions;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException as HttpRequestException;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\AuthenticationException;
use Rathauslotse\BayernPortal\Exceptions\ConnectionException;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Exceptions\ServerException;
use Throwable;

/**
 * Connector backed by the Laravel HTTP client.
 */
final class HttpConnector implements Connector
{
    public const string PACKAGE = 'rathauslotse/bayernportal-api';

    public function __construct(
        private readonly Factory $http,
        private readonly ConnectorOptions $options,
    ) {}

    public function get(string $path, array $query = []): ApiResponse
    {
        $path = ltrim($path, '/');

        try {
            $response = $this->request()->get($path, Query::normalise($query));
        } catch (HttpConnectionException $exception) {
            throw new ConnectionException(
                sprintf('Could not connect to the BayernPortal API [GET %s]: %s', $path, $exception->getMessage()),
                0,
                $exception,
            );
        }

        $apiResponse = ApiResponse::make($response->status(), $response->body(), $response->headers());

        if ($apiResponse->successful()) {
            return $apiResponse;
        }

        throw match (true) {
            in_array($apiResponse->status, [401, 403], true) => AuthenticationException::fromResponse($apiResponse, $path),
            $apiResponse->status === 404 => NotFoundException::fromResponse($apiResponse, $path),
            $apiResponse->status >= 500 => ServerException::fromResponse($apiResponse, $path),
            default => RequestException::fromResponse($apiResponse, $path),
        };
    }

    private function request(): PendingRequest
    {
        $request = $this->http
            ->baseUrl(rtrim($this->options->baseUrl, '/').'/')
            ->acceptJson()
            ->withUserAgent(self::PACKAGE.'/'.self::version())
            ->timeout($this->options->timeout)
            ->connectTimeout($this->options->connectTimeout);

        if ($this->options->username !== null && $this->options->username !== '') {
            $request->withBasicAuth($this->options->username, $this->options->password ?? '');
        }

        if ($this->options->retryTimes > 0) {
            $request->retry(
                $this->options->retryTimes + 1,
                $this->options->retrySleepMilliseconds,
                static fn (Throwable $exception): bool => $exception instanceof HttpConnectionException
                    || ($exception instanceof HttpRequestException && $exception->response->serverError()),
                throw: false,
            );
        }

        return $request;
    }

    private static function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion(self::PACKAGE) ?? 'dev';
        } catch (Throwable) {
            return 'dev';
        }
    }
}
