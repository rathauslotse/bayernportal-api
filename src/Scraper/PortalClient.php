<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

use Composer\InstalledVersions;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException as HttpRequestException;
use Rathauslotse\BayernPortal\Exceptions\ConnectionException;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Exceptions\ScraperBlockedException;
use Rathauslotse\BayernPortal\Exceptions\ServerException;
use Rathauslotse\BayernPortal\Http\ApiResponse;
use Rathauslotse\BayernPortal\Http\HttpConnector;
use Rathauslotse\BayernPortal\Http\Query;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Throwable;

/**
 * Fetches pages from www.bayernportal.de, throttled and with retries.
 *
 * @internal
 */
class PortalClient
{
    public function __construct(
        private readonly Factory $http,
        private readonly ScraperOptions $options,
        private readonly Throttle $throttle,
    ) {}

    /**
     * @param  string  $path  Path relative to the website root, e.g. "dokumente/behoerde/66552873578".
     * @param  array<string, scalar|null>  $query
     *
     * @throws NotFoundException when the page does not exist.
     * @throws ScraperBlockedException when the website rate-limits or challenges the request.
     */
    public function get(string $path, array $query = []): Document
    {
        $path = ltrim($path, '/');
        $this->throttle->wait();

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->options->baseUrl, '/').'/')
                ->accept('text/html')
                ->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
                ->withUserAgent(HttpConnector::PACKAGE.'/'.self::version().' (+scraper)')
                ->timeout($this->options->timeout)
                ->connectTimeout($this->options->connectTimeout)
                ->retry(
                    $this->options->retryTimes + 1,
                    $this->options->retrySleepMilliseconds,
                    static fn (Throwable $exception): bool => $exception instanceof HttpConnectionException
                        || ($exception instanceof HttpRequestException && ($exception->response->serverError() || $exception->response->status() === 429)),
                    throw: false,
                )
                ->get($path, Query::normalise($query));
        } catch (HttpConnectionException $exception) {
            throw new ConnectionException(
                sprintf('Could not connect to %s [GET %s]: %s', $this->options->baseUrl, $path, $exception->getMessage()),
                0,
                $exception,
            );
        }

        $body = $response->body();
        $status = $response->status();

        if ($status === 429 || self::isChallenge($body)) {
            throw new ScraperBlockedException(sprintf(
                'www.bayernportal.de blocked the request [GET %s] (HTTP %d). Increase bayernportal.scraper.throttle_ms and try again later.',
                $path,
                $status,
            ));
        }

        $document = new Document($body, $path);

        // The website answers unknown documents with HTTP 400 and a "page not found" page.
        if ($status === 404 || ($status === 400 && self::isNotFoundPage($document))) {
            throw NotFoundException::fromResponse(ApiResponse::make($status, 'Seite nicht gefunden'), $path);
        }

        if ($response->successful()) {
            return $document;
        }

        $error = ApiResponse::make($status, (string) Document::clean(strip_tags($body)));

        throw $status >= 500
            ? ServerException::fromResponse($error, $path)
            : RequestException::fromResponse($error, $path);
    }

    private static function isNotFoundPage(Document $document): bool
    {
        return $document->text('//h1') === 'Seite nicht gefunden';
    }

    private static function isChallenge(string $body): bool
    {
        // The site loads FriendlyCaptcha; a widget in the page body means a bot check.
        return str_contains($body, 'class="frc-captcha');
    }

    private static function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion(HttpConnector::PACKAGE) ?? 'dev';
        } catch (Throwable) {
            return 'dev';
        }
    }
}
