<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    useScraper();
    $this->output = sys_get_temp_dir().'/bayernportal-index-'.uniqid().'/municipalities.json';
});

afterEach(function (): void {
    if (is_file($this->output)) {
        unlink($this->output);
        rmdir(dirname($this->output));
    }
});

function fakeMunicipalityFolder(): void
{
    $folder = '<html><body><h1>Gemeinden</h1><ul>'
        .'<li><a href="/dokumente/behoerde/73886855438/A">A</a></li>'
        .'<li><a href="/dokumente/behoerde/66552873578" aria-label="Haar (Stadt)">Haar (Stadt)</a></li>'
        .'<li><a href="/dokumente/behoerde/111" aria-label="Ohne Statistik">Ohne Statistik</a></li>'
        .'<li><a href="/dokumente/behoerde/222" aria-label="Verschwunden">Verschwunden</a></li>'
        .'</ul></body></html>';

    fakeWebsite([
        'dokumente/behoerde/73886855438' => Http::response($folder),
        'dokumente/behoerde/111' => Http::response('<html><body><h1>Ohne Statistik</h1></body></html>'),
    ]);
}

it('crawls the municipality folder into an index', function (): void {
    fakeMunicipalityFolder();

    $this->artisan('bayernportal:scrape-index', ['--output' => $this->output])
        ->expectsOutputToContain('3 municipalities, 0 already indexed, 3 to crawl.')
        ->expectsOutputToContain('Ohne Statistik (111): no municipality key on the page')
        ->expectsOutputToContain('Verschwunden (222)')
        ->assertSuccessful();

    expect(json_decode((string) file_get_contents($this->output), true))
        ->toBe(['09184123' => ['id' => '66552873578', 'name' => 'Haar (Stadt)']]);
});

it('resumes an existing index and honours the limit', function (): void {
    fakeMunicipalityFolder();
    mkdir(dirname($this->output));
    file_put_contents($this->output, json_encode(['09184123' => ['id' => '66552873578', 'name' => 'Haar (Stadt)']]));

    $this->artisan('bayernportal:scrape-index', ['--output' => $this->output, '--limit' => 1])
        ->expectsOutputToContain('3 municipalities, 1 already indexed, 1 to crawl.')
        ->assertSuccessful();

    Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/66552873578'));
});

it('writes to the configured index path by default', function (): void {
    fakeMunicipalityFolder();
    config()->set('bayernportal.scraper.index_path', $this->output);

    $this->artisan('bayernportal:scrape-index', ['--limit' => 1])->assertSuccessful();

    expect(is_file($this->output))->toBeTrue();
});
