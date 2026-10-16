<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Rathauslotse\BayernPortal\Contracts\Connector;
use Rathauslotse\BayernPortal\Exceptions\AuthenticationException;
use Rathauslotse\BayernPortal\Exceptions\ConnectionException;
use Rathauslotse\BayernPortal\Exceptions\NotFoundException;
use Rathauslotse\BayernPortal\Exceptions\RequestException;
use Rathauslotse\BayernPortal\Exceptions\ServerException;
use Rathauslotse\BayernPortal\Tests\TestCase;

it('sends authenticated json GET requests to the base url', function (): void {
    Http::fake(['*' => Http::response(['ok' => true], 200, ['X-Total-Count' => '7'])]);

    $response = app(Connector::class)->get('/dienststellen', ['full' => true, 'page' => 2, 'gemeindekennziffer' => null]);

    expect($response->json())->toBe(['ok' => true])
        ->and($response->header('X-Total-Count'))->toBe('7');

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === TestCase::BASE_URL.'dienststellen?full=true&page=2'
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-user:test-secret'))
            && str_starts_with($request->header('User-Agent')[0] ?? '', 'rathauslotse/bayernportal-api/');
    });
});

it('omits basic auth when no credentials are configured', function (): void {
    config()->set('bayernportal.username', null);
    Http::fake(['*' => Http::response([])]);

    app(Connector::class)->get('lebenslagen');

    Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
});

it('maps error responses to exceptions', function (int $status, string $exception): void {
    Http::fake(['*' => Http::response('Es wurde keine Dienststelle gefunden.', $status)]);

    try {
        app(Connector::class)->get('dienststellen/0000000/lebenslagen');
        $this->fail('No exception thrown.');
    } catch (RequestException|ServerException $e) {
        expect($e)->toBeInstanceOf($exception)
            ->and($e->status())->toBe($status)
            ->and($e->apiMessage())->toBe('Es wurde keine Dienststelle gefunden.')
            ->and($e->path)->toBe('dienststellen/0000000/lebenslagen')
            ->and($e->getMessage())->toContain('HTTP '.$status)->toContain('keine Dienststelle');
    }
})->with([
    [400, RequestException::class],
    [401, AuthenticationException::class],
    [403, AuthenticationException::class],
    [404, NotFoundException::class],
    [422, RequestException::class],
    [500, ServerException::class],
    [503, ServerException::class],
]);

it('retries server errors and then succeeds', function (): void {
    Http::fakeSequence()
        ->push('boom', 502)
        ->push(['ok' => true], 200);

    expect(app(Connector::class)->get('lebenslagen')->json())->toBe(['ok' => true]);

    Http::assertSentCount(2);
});

it('gives up after the configured number of retries', function (): void {
    config()->set('bayernportal.retry.times', 1);
    Http::fake(['*' => Http::response('down', 500)]);

    expect(fn () => app(Connector::class)->get('lebenslagen'))->toThrow(ServerException::class);

    Http::assertSentCount(2);
});

it('does not retry client errors', function (): void {
    Http::fake(['*' => Http::response('nope', 400)]);

    expect(fn () => app(Connector::class)->get('lebenslagen'))->toThrow(RequestException::class);

    Http::assertSentCount(1);
});

it('wraps connection failures', function (): void {
    config()->set('bayernportal.retry.times', 0);
    Http::fake(fn () => throw new HttpConnectionException('cURL error 6: Could not resolve host'));

    app(Connector::class)->get('lebenslagen');
})->throws(ConnectionException::class, 'Could not resolve host');
