<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Rathauslotse\BayernPortal\Exceptions\BayernPortalException;
use Rathauslotse\BayernPortal\Scraper\MunicipalityIndex;
use Rathauslotse\BayernPortal\Scraper\Pages\AuthorityPage;
use Rathauslotse\BayernPortal\Scraper\Pages\MunicipalityFolderPage;
use Rathauslotse\BayernPortal\Scraper\PortalClient;
use Rathauslotse\BayernPortal\Scraper\ScraperOptions;
use Rathauslotse\BayernPortal\Scraper\Throttle;

/**
 * Crawls www.bayernportal.de for the municipality key → authority id index
 * used by the scraper driver. One request per municipality (~2,050), so a
 * full run takes about 35 minutes at the default throttle.
 */
final class BuildMunicipalityIndexCommand extends Command
{
    protected $signature = 'bayernportal:scrape-index
        {--output= : Target file (default: bayernportal.scraper.index_path, or storage/app/bayernportal/municipalities.json)}
        {--limit= : Only crawl this many municipalities (for testing)}
        {--fresh : Ignore entries already in the target file}';

    protected $description = 'Build the municipality index for the BayernPortal scraper driver';

    public function handle(Factory $http, Repository $config): int
    {
        /** @var array<array-key, mixed> $settings */
        $settings = (array) $config->get('bayernportal', []);
        $options = ScraperOptions::fromConfig($settings);
        $client = new PortalClient($http, $options, new Throttle($options->throttleMilliseconds));
        $output = $this->target($settings);
        $limit = is_numeric($this->option('limit')) ? max(0, (int) $this->option('limit')) : null;

        $index = $this->option('fresh') || ! is_file($output) ? [] : (new MunicipalityIndex($output))->all();
        $known = array_flip(array_column($index, 'id'));

        $this->info('Reading the municipality folder …');
        $municipalities = MunicipalityFolderPage::parse($client->get(MunicipalityFolderPage::PATH));
        $todo = array_diff_key($municipalities, $known);

        if ($limit !== null) {
            $todo = array_slice($todo, 0, $limit, true);
        }

        $this->info(sprintf('%d municipalities, %d already indexed, %d to crawl.', count($municipalities), count($known), count($todo)));
        $bar = $this->output->createProgressBar(count($todo));
        $failed = [];

        foreach ($todo as $portalId => $name) {
            try {
                $authority = AuthorityPage::parse($client->get('dokumente/behoerde/'.$portalId), (string) $portalId);
                $key = $authority['gemeindekennziffer'] ?? null;

                if (is_string($key)) {
                    $index[$key] = ['id' => (string) $portalId, 'name' => $name];
                } else {
                    $failed[] = $name.' ('.$portalId.'): no municipality key on the page';
                }
            } catch (BayernPortalException $exception) {
                $failed[] = $name.' ('.$portalId.'): '.$exception->getMessage();
            }

            $bar->advance();

            // Save regularly so an interrupted run can resume.
            if ($bar->getProgress() % 50 === 0) {
                $this->write($output, $index);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->write($output, $index);
        $this->info(sprintf('Wrote %d entries to %s.', count($index), $output));

        foreach ($failed as $failure) {
            $this->warn($failure);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<array-key, mixed>  $settings
     */
    private function target(array $settings): string
    {
        $option = $this->option('output');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        $scraper = is_array($settings['scraper'] ?? null) ? $settings['scraper'] : [];

        if (is_string($scraper['index_path'] ?? null) && $scraper['index_path'] !== '') {
            return $scraper['index_path'];
        }

        return $this->laravel->storagePath('app/bayernportal/municipalities.json');
    }

    /**
     * @param  array<string, array{id: string, name: string}>  $index
     */
    private function write(string $path, array $index): void
    {
        ksort($index);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }
}
