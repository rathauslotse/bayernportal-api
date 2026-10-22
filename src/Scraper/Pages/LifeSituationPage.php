<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * Life situation pages:
 *
 * - /suche/lebenslage/hierarchisch/{category} lists the top-level situations,
 * - /dokumente/lebenslage/{id} shows one situation with its sub-situations
 *   (inner node) or its services (leaf).
 *
 * @internal
 */
final class LifeSituationPage
{
    public const array CATEGORIES = ['buerger', 'unternehmer', 'verwaltung'];

    /**
     * @return array<string, mixed>
     */
    public static function parse(Document $page, string $id): array
    {
        $breadcrumb = $page->all('//a[@href="/" and normalize-space()="Start"]/following-sibling::a');
        $category = null;
        $parent = null;

        foreach ($breadcrumb as $link) {
            $href = $link->getAttribute('href');

            if (preg_match('#/suche/lebenslage/hierarchisch/(\w+)#', $href, $match) === 1) {
                $category = $match[1];
            }

            $parent = Links::id($href, 'lebenslage') ?? $parent;
        }

        $parentName = $parent === null ? null : Document::clean($breadcrumb[count($breadcrumb) - 1]->textContent);
        $name = (string) $page->text('//h1');

        // Sub-situations repeat their parent's name: "Geburt - Vor der Geburt".
        if ($parentName !== null && str_starts_with($name, $parentName.' - ')) {
            $name = substr($name, strlen($parentName.' - '));
        }

        $services = [];

        foreach ($page->all('//h2[normalize-space()="Leistungen"]/following-sibling::*[1]//a[contains(@href, "/dokumente/leistung/")]') as $link) {
            $serviceId = Links::id($link->getAttribute('href'), 'leistung');

            if ($serviceId !== null) {
                $services[] = [
                    'id' => (int) $serviceId,
                    'bezeichnung' => Document::clean($link->getAttribute('aria-label')) ?? Document::clean($link->textContent),
                    'url' => Links::document('leistung', $serviceId),
                ];
            }
        }

        return array_filter([
            'id' => $id,
            'bezeichnung' => $name,
            'kurzbeschreibung' => $page->text(Document::SUMMARY),
            'kategorie' => in_array($category, self::CATEGORIES, true) ? $category : null,
            'vorgaengerId' => $parent,
            'leistungen' => $services === [] ? null : ['leistung' => $services],
            'unterlebenslagen' => self::children($page) ?: null,
            'url' => Links::document('lebenslage', $id),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Ids of the situations listed on a page (top level on the category
     * page, sub-situations on a detail page).
     *
     * @return list<string>
     */
    public static function children(Document $page): array
    {
        $ids = [];

        foreach ($page->all('//h1/following::a[contains(@href, "/dokumente/lebenslage/")][.//h2]') as $link) {
            $id = Links::id($link->getAttribute('href'), 'lebenslage');

            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
