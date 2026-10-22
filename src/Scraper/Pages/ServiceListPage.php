<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * Lists of service links: /dokumente/behoerde/{id}/leistungen and
 * /suche/leistung/alphabetisch. Both pages show every entry at once.
 *
 * @internal
 */
final class ServiceListPage
{
    /**
     * @return list<array{id: int, portalId: string, bezeichnung: string, url: string}>
     */
    public static function parse(Document $page): array
    {
        $services = [];

        foreach ($page->all('//ul[@id="leistungList" or @id="alphabeticResultList"]//a[contains(@href, "/dokumente/leistung/")]') as $link) {
            $id = Links::id($link->getAttribute('href'), 'leistung');
            $name = Document::clean($link->getAttribute('aria-label')) ?? Document::clean($link->textContent);

            if ($id === null || $name === null || isset($services[$id])) {
                continue;
            }

            $services[$id] = [
                'id' => (int) $id,
                'portalId' => $id,
                'bezeichnung' => $name,
                'url' => Links::document('leistung', $id),
            ];
        }

        return array_values($services);
    }

    /**
     * The website's localisation parameters (plz, behoerde, gemeinde) as
     * found on the links of a municipality's service list.
     *
     * @return array{plz: string, behoerde: string, gemeinde: string}|null
     */
    public static function localisation(Document $page): ?array
    {
        $href = $page->first('//a[contains(@href, "/dokumente/leistung/")][contains(@href, "gemeinde=")]')?->getAttribute('href');
        parse_str((string) parse_url(html_entity_decode((string) $href), PHP_URL_QUERY), $query);

        $plz = $query['plz'] ?? null;
        $authority = $query['behoerde'] ?? null;
        $municipality = $query['gemeinde'] ?? null;

        if (! is_string($plz) || ! is_string($authority) || ! is_string($municipality)) {
            return null;
        }

        return ['plz' => $plz, 'behoerde' => $authority, 'gemeinde' => $municipality];
    }
}
