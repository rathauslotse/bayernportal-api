<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use DOMElement;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Parsers\AddressParser;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * /dokumente/leistung/{id}: a service description.
 *
 * The detail sections are accordions whose ids match the REST field names
 * ("heading-voraussetzungen" → voraussetzungen). With a localisation
 * (plz, behoerde, gemeinde) the page also shows the responsible
 * authorities and the municipality's regional supplements inline.
 *
 * @internal
 */
final class ServiceDescriptionPage
{
    /**
     * Section id on the website → REST field holding the section's HTML.
     */
    private const array HTML_SECTIONS = [
        'langBeschreibung' => 'langbeschreibung',
        'voraussetzungen' => 'voraussetzungen',
        'verfahrensablauf' => 'verfahrensablauf',
        'kosten' => 'kosten',
        'fristen' => 'fristen',
        'bearbeitungsdauer' => 'bearbeitungsdauer',
        'hinweise' => 'hinweise',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function parse(Document $page, string $portalId): array
    {
        $payload = [
            'id' => (int) $portalId,
            'portalId' => $portalId,
            'bezeichnung' => $page->text('//h1') ?? '',
            'url' => Links::document('leistung', $portalId),
            'status' => 'live',
            'synonyme' => self::synonyms($page),
            'kurzbeschreibung' => $page->text(Document::SUMMARY),
        ];

        foreach (self::HTML_SECTIONS as $section => $field) {
            $payload[$field] = self::sectionHtml($page, $section);
        }

        $legalRemedy = self::panel($page, 'rechtsbehelf');
        $legalRemedyText = $legalRemedy === null ? null : Document::clean($legalRemedy->textContent);

        $payload += [
            'unterlagen' => self::wrap('unterlage', self::items($page, 'unterlagen')),
            'formulare' => self::wrap('formular', self::items($page, 'formulare')),
            'onlineVerfahren' => self::wrap('onlineVerfahren', self::items($page, 'onlineVerfahren')),
            'rechtsvorschriften' => self::wrap('rechtsvorschrift', self::items($page, 'rechtsvorschriften')),
            'links' => self::wrap('link', self::items($page, 'links')),
            'verwandteLeistungen' => self::wrap('verwandteLeistung', self::relatedServices($page)),
            'rechtsbehelf' => $legalRemedyText === null || $legalRemedyText === 'nicht angegeben' ? null : ['kurzbeschreibung' => $legalRemedyText],
            'stand' => self::updatedAt($page),
            'verantwortlicheBehoerde' => $page->text('//text()[contains(., "Redaktionell verantwortlich")]/following::a[1]'),
            'zustaendigkeiten' => self::wrap('zustaendig', self::responsibilities($page)),
            'regionaleErgaenzungen' => self::supplements($page),
        ];

        return array_filter($payload, static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * HTML of a text section, without the website's "bp-richContent" wrapper.
     */
    private static function sectionHtml(Document $page, string $section): ?string
    {
        $panel = self::panel($page, $section);

        if ($panel === null || Document::clean($panel->textContent) === 'nicht angegeben') {
            return null;
        }

        $wrappers = $page->all('./div[contains(concat(" ", normalize-space(@class), " "), " bp-richContent ")]', $panel);

        if (count($wrappers) === 1 && Document::clean($panel->textContent) === Document::clean($wrappers[0]->textContent)) {
            return $page->innerHtml($wrappers[0]);
        }

        return $page->innerHtml($panel);
    }

    private static function panel(Document $page, string $section): ?DOMElement
    {
        return $page->first(sprintf('//*[@id=%s][@role="region"]', Document::literal($section)));
    }

    /**
     * @return array{synonym: list<string>}|null
     */
    private static function synonyms(Document $page): ?array
    {
        $keywords = $page->first('//meta[@name="keywords"]')?->getAttribute('content');
        $synonyms = array_values(array_filter(array_map('trim', explode(',', (string) $keywords)), static fn (string $word): bool => $word !== ''));

        return $synonyms === [] ? null : ['synonym' => $synonyms];
    }

    /**
     * Top-level list entries of a section: a link (title + URL) and the remaining text.
     *
     * @return list<array<string, mixed>>
     */
    private static function items(Document $page, string $section): array
    {
        $panel = self::panel($page, $section);
        $items = [];

        foreach ($panel === null ? [] : $page->all('.//li[not(ancestor::li)]', $panel) as $item) {
            $link = $page->first('.//a[@href]', $item);
            $title = $link === null ? null : Document::clean($link->textContent);
            $text = Document::clean($item->textContent);
            $description = $title === null ? null : Document::clean(substr((string) $text, strlen($title)));

            if ($link !== null && $title !== null) {
                $serviceId = Links::id($link->getAttribute('href'), 'leistung');

                $items[] = array_filter([
                    'kurzbeschreibung' => $title,
                    'url' => Links::withoutCaller(Links::absolute(html_entity_decode($link->getAttribute('href')))),
                    'langbeschreibung' => $description,
                    'leistungId' => $serviceId === null ? null : (int) $serviceId,
                ], static fn (mixed $value): bool => $value !== null);
            } elseif ($text !== null) {
                $items[] = ['kurzbeschreibung' => $text];
            }
        }

        return $items;
    }

    /**
     * @return list<array{id: int, bezeichnung: string, url: string, direktVerwandt: bool}>
     */
    private static function relatedServices(Document $page): array
    {
        $panel = self::panel($page, 'verwandteProduktBlaetter');
        $related = [];

        foreach ($panel === null ? [] : $page->all('.//a[contains(@href, "/dokumente/leistung/")]', $panel) as $link) {
            $id = Links::id($link->getAttribute('href'), 'leistung');

            if ($id !== null) {
                $related[] = [
                    'id' => (int) $id,
                    'bezeichnung' => (string) Document::clean($link->textContent),
                    'url' => Links::document('leistung', $id),
                    'direktVerwandt' => true,
                ];
            }
        }

        return $related;
    }

    private static function updatedAt(Document $page): ?string
    {
        $text = $page->text('//text()[contains(., "Stand:")][not(ancestor::*[@role="region"])]/..');

        if ($text === null || preg_match('/Stand:\s*(\d{1,2})\.(\d{1,2})\.(\d{4})/', $text, $match) !== 1) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $match[3], $match[2], $match[1]);
    }

    /**
     * "Für Sie zuständig": one accordion per responsible authority.
     *
     * @return list<array<string, mixed>>
     */
    private static function responsibilities(Document $page): array
    {
        $responsibilities = [];

        foreach ($page->all('//h2[normalize-space()="Für Sie zuständig"]/following::h3[starts-with(@id, "heading-")][following::h2[normalize-space()="Leistungsdetails"]]') as $heading) {
            $panel = $page->byId(substr($heading->getAttribute('id'), strlen('heading-')));

            if ($panel === null) {
                continue;
            }

            $authorityId = Links::id($page->first('.//a[normalize-space()="Mehr zur Behörde"]', $panel)?->getAttribute('href'), 'behoerde');
            $buildingId = $page->first('.//a[contains(@href, "/gebaeude/")]', $panel)?->getAttribute('href');

            $building = array_filter([
                'gebaeudeId' => $buildingId !== null && preg_match('#/gebaeude/(\d+)#', $buildingId, $match) === 1 ? (int) $match[1] : null,
                'hausanschrift' => AddressParser::parse(self::labelledLines($page, $panel, 'Hausanschrift')),
                'postanschrift' => AddressParser::parse(self::labelledLines($page, $panel, 'Postanschrift')),
                'telefon' => self::labelledLines($page, $panel, 'Telefon')[0] ?? null,
                'fax' => self::labelledLines($page, $panel, 'Fax')[0] ?? null,
            ], static fn (mixed $value): bool => $value !== null);

            $responsibilities[] = array_filter([
                'behoerdId' => $authorityId === null ? null : (int) $authorityId,
                'bezeichnung' => Document::clean($heading->textContent) ?? '',
                'anschriften' => $building === [] ? null : ['gebaeude' => [$building]],
                'email' => self::labelledLines($page, $panel, 'E-Mail')[0] ?? null,
                'website' => Links::absolute(self::labelled($page, $panel, 'Webseite') === null ? null : $page->first('.//a[@href]', self::labelled($page, $panel, 'Webseite'))?->getAttribute('href')),
                'bwwlink' => $authorityId === null ? null : Links::document('behoerde', $authorityId),
                'linkSicheresKontaktformular' => Links::absolute($page->first('.//a[contains(@href, "sichererKontakt")]', $panel)?->getAttribute('href')),
            ], static fn (mixed $value): bool => $value !== null);
        }

        return $responsibilities;
    }

    private static function labelled(Document $page, DOMElement $panel, string $label): ?DOMElement
    {
        $heading = $page->first(sprintf('.//h3[normalize-space()=%s]', Document::literal($label)), $panel);

        return $heading?->parentNode instanceof DOMElement ? $heading->parentNode : null;
    }

    /**
     * @return list<string>
     */
    private static function labelledLines(Document $page, DOMElement $panel, string $label): array
    {
        $container = self::labelled($page, $panel, $label);

        if ($container === null) {
            return [];
        }

        $lines = Document::lines($container);
        array_shift($lines);

        return $lines;
    }

    /**
     * Regional supplements ("Ergänzung: Stadt Haar") shown inline in a section.
     *
     * The website gives them no id, so they are exposed in the raw payload only.
     *
     * @return list<array{abschnitt: string, bezeichnung: string, text: ?string}>
     */
    private static function supplements(Document $page): array
    {
        $supplements = [];

        foreach ($page->all('//*[@role="region"]//h4[starts-with(normalize-space(), "Ergänzung")]') as $heading) {
            $panel = $page->first('ancestor::*[@role="region"][1]', $heading);
            $content = $page->first('following-sibling::*[1]', $heading);

            $supplements[] = [
                'abschnitt' => $panel?->getAttribute('id') ?? '',
                'bezeichnung' => trim((string) preg_replace('/^Ergänzung:\s*/u', '', (string) Document::clean($heading->textContent))),
                'text' => $content === null ? null : $page->innerHtml($content),
            ];
        }

        return $supplements;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, list<array<string, mixed>>>|null
     */
    private static function wrap(string $key, array $items): ?array
    {
        return $items === [] ? null : [$key => $items];
    }
}
