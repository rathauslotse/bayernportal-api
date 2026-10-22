<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * /dokumente/behoerde/73886855438: the folder of all ~2,050 Bavarian
 * municipalities ("Gemeinden"), on a single page.
 *
 * @internal
 */
final class MunicipalityFolderPage
{
    public const string PATH = 'dokumente/behoerde/73886855438';

    /**
     * @return array<string, string> Portal id → displayed name, e.g. "Haar (Stadt)".
     */
    public static function parse(Document $page): array
    {
        $municipalities = [];
        $folder = basename(self::PATH);

        foreach ($page->all('//h1/following::a[contains(@href, "/dokumente/behoerde/")][@aria-label]') as $link) {
            $id = Links::id($link->getAttribute('href'), 'behoerde');

            if ($id !== null && $id !== $folder) {
                $municipalities[$id] = (string) Document::clean($link->getAttribute('aria-label'));
            }
        }

        return $municipalities;
    }
}
