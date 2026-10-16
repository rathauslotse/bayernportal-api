<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use Rathauslotse\BayernPortal\Http\ConnectorOptions;

/**
 * Test double for the BayernPortal API, built on Laravel's Http::fake().
 *
 * Every endpoint answers with a realistic default fixture unless a custom
 * response is registered for its path:
 *
 * ```php
 * $fake = BayernPortal::fake([
 *     'leistungen/1010' => ['leistung' => ['id' => 1010, 'bezeichnung' => 'Personalausweis']],
 *     'dienststellen/*' => BayernPortalFake::error(400, 'Keine Dienststelle gefunden.'),
 * ]);
 *
 * // … exercise your code …
 *
 * $fake->assertRequested('leistungen/1010');
 * ```
 */
final class BayernPortalFake
{
    /**
     * Default fixture per path pattern (regular expressions on the path
     * relative to the API base URL).
     */
    private const array ROUTES = [
        '#^dienststellen$#' => 'agencies',
        '#^dienststellen/\d+$#' => 'agency',
        '#^dienststellen/\d+/leistungen$#' => 'services',
        '#^dienststellen/\d+/leistungsbeschreibungen$#' => 'service-descriptions',
        '#^dienststellen/\d+/lebenslagen$#' => 'life-situations',
        '#^behoerden$#' => 'authorities',
        '#^behoerden/\d+$#' => 'authority',
        '#^behoerden/\d+/gebaeude/\d+$#' => 'building',
        '#^behoerden/\d+/gebaeude/\d+/ansprechpartner/\d+$#' => 'building-contact',
        '#^behoerden/\d+/ansprechpartner$#' => 'contacts',
        '#^behoerden/\d+/(abgegebene-)?leistungen$#' => 'authority-services',
        '#^ansprechpartner$#' => 'contacts',
        '#^ansprechpartner/\d+$#' => 'contact',
        '#^ansprechpartner/\d+/leistungen$#' => 'authority-services',
        '#^leistungen$#' => 'services',
        '#^leistungen/\d+$#' => 'service',
        '#^leistungsbeschreibungen$#' => 'service-descriptions',
        '#^leistungsbeschreibungen/\d+$#' => 'service-description',
        '#^lebenslagen$#' => 'life-situations',
        '#^lebenslagen/\d+$#' => 'life-situation',
    ];

    /**
     * @param  array<string, mixed>  $responses  Custom responses keyed by path pattern (wildcards allowed).
     */
    public function __construct(
        private readonly Factory $http,
        private array $responses = [],
        private readonly string $baseUrl = ConnectorOptions::DEFAULT_BASE_URL,
    ) {}

    /**
     * Register the fake with the HTTP client factory.
     *
     * @internal Use BayernPortal::fake().
     */
    public function register(): self
    {
        $this->http->fake(fn (Request $request): PromiseInterface => $this->respond($request));

        return $this;
    }

    /**
     * Add or replace custom responses.
     *
     * @param  array<string, mixed>  $responses
     */
    public function respondWith(array $responses): self
    {
        $this->responses = [...$this->responses, ...$responses];

        return $this;
    }

    /**
     * Decoded content of a bundled fixture, e.g. `fixture('agency')`.
     *
     * @return array<array-key, mixed>
     */
    public static function fixture(string $name): array
    {
        $path = self::fixturePath($name);

        if (! is_file($path)) {
            throw new InvalidArgumentException(sprintf('Unknown BayernPortal fixture [%s].', $name));
        }

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Names of all bundled fixtures.
     *
     * @return list<string>
     */
    public static function fixtures(): array
    {
        $names = array_map(fn (string $file): string => basename($file, '.json'), glob(self::fixturePath('*')) ?: []);
        sort($names);

        return $names;
    }

    /**
     * An error response, e.g. `error(400, 'Sie haben keine Berechtigung …')`.
     */
    public static function error(int $status, string $message = ''): PromiseInterface
    {
        return Factory::response($message, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * A JSON response with optional pagination headers.
     *
     * @param  array<array-key, mixed>  $body
     */
    public static function json(array $body, int $status = 200, ?int $totalCount = null): PromiseInterface
    {
        $headers = $totalCount === null ? [] : ['X-Total-Count' => (string) $totalCount];

        return Factory::response($body, $status, $headers);
    }

    /**
     * Assert that a request was sent to the given path (relative to the
     * API base URL, wildcards allowed). Optionally assert query parameters
     * using their API (German) names.
     *
     * @param  array<string, string>  $query
     */
    public function assertRequested(string $path, array $query = []): void
    {
        PHPUnit::assertTrue(
            $this->recorded(fn (Request $request): bool => $this->matches($request, $path, $query)) > 0,
            sprintf('No BayernPortal request to [%s] was sent.', $path.($query === [] ? '' : '?'.http_build_query($query))),
        );
    }

    /**
     * @param  array<string, string>  $query
     */
    public function assertNotRequested(string $path, array $query = []): void
    {
        PHPUnit::assertSame(
            0,
            $this->recorded(fn (Request $request): bool => $this->matches($request, $path, $query)),
            sprintf('An unexpected BayernPortal request to [%s] was sent.', $path),
        );
    }

    public function assertRequestCount(int $count): void
    {
        PHPUnit::assertSame($count, $this->recorded(fn (): bool => true), 'Unexpected number of BayernPortal requests.');
    }

    public function assertNothingRequested(): void
    {
        $this->assertRequestCount(0);
    }

    private function respond(Request $request): PromiseInterface
    {
        $path = $this->path($request);

        foreach ($this->responses as $pattern => $response) {
            if (Str::is(ltrim($pattern, '/'), $path)) {
                return $this->toResponse($response instanceof Closure ? $response($request) : $response);
            }
        }

        return $this->toResponse($this->defaultFixture($path, $request));
    }

    private function toResponse(mixed $response): PromiseInterface
    {
        return match (true) {
            $response instanceof PromiseInterface => $response,
            is_array($response) => Factory::response($response),
            is_string($response) => Factory::response($response),
            default => Factory::response('', 204),
        };
    }

    /**
     * @return array<array-key, mixed>|PromiseInterface
     */
    private function defaultFixture(string $path, Request $request): array|PromiseInterface
    {
        $grouped = ($request->data()['gruppiertNachLeistungen'] ?? 'true') !== 'false';

        if (preg_match('#^dienststellen/\d+/formulare$#', $path) === 1) {
            return self::fixture($grouped ? 'forms-grouped' : 'forms');
        }

        if (preg_match('#^dienststellen/\d+/onlineverfahren$#', $path) === 1) {
            return self::fixture($grouped ? 'online-procedures-grouped' : 'online-procedures');
        }

        foreach (self::ROUTES as $pattern => $fixture) {
            if (preg_match($pattern, $path) === 1) {
                return self::fixture($fixture);
            }
        }

        return self::error(404, 'No BayernPortal fake response for ['.$path.'].');
    }

    /**
     * @param  array<string, string>  $query
     */
    private function matches(Request $request, string $path, array $query): bool
    {
        if (! Str::is(ltrim($path, '/'), $this->path($request))) {
            return false;
        }

        $sent = $request->data();

        foreach ($query as $key => $value) {
            if (! array_key_exists($key, $sent) || ! is_scalar($sent[$key]) || (string) $sent[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    private function recorded(Closure $filter): int
    {
        return $this->http->recorded(fn (Request $request): bool => str_starts_with($request->url(), rtrim($this->baseUrl, '/')) && $filter($request))->count();
    }

    private function path(Request $request): string
    {
        $url = strtok($request->url(), '?') ?: '';
        $base = rtrim($this->baseUrl, '/').'/';

        return trim(str_starts_with($url, $base) ? substr($url, strlen($base)) : (string) parse_url($url, PHP_URL_PATH), '/');
    }

    private static function fixturePath(string $name): string
    {
        return dirname(__DIR__, 2).'/resources/fixtures/'.$name.'.json';
    }
}
