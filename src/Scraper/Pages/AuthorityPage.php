<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use DOMElement;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Parsers\AddressParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\PhoneParser;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * /dokumente/behoerde/{id}: the authority detail page.
 *
 * Produces the REST "behoerde" payload. Fields the website does not show
 * (sort order, logos as image data, subordinate authorities) are missing;
 * extra facts the page shows are added under keys the DTOs ignore, so
 * they remain available via the DTO's raw payload.
 *
 * @internal
 */
final class AuthorityPage
{
    /**
     * @return array<string, mixed>
     */
    public static function parse(Document $page, string $portalId): array
    {
        $breadcrumb = self::breadcrumb($page);
        $group = $breadcrumb === [] ? null : $breadcrumb[count($breadcrumb) - 1];
        $name = $page->text('//*[@id="pageTitle"]//h1') ?? $page->text('//h1') ?? '';

        $payload = [
            'id' => (int) $portalId,
            'portalId' => $portalId,
            'bezeichnung' => $name,
            'behoerdenart' => $group['name'] ?? '',
            'behoerdengruppe' => isset($breadcrumb[count($breadcrumb) - 2]) && count($breadcrumb) > 1 ? $breadcrumb[count($breadcrumb) - 2]['name'] : null,
            'kurzbeschreibung' => self::summary($page),
            'email' => self::mail($page, self::labelled($page, 'E-Mail')),
            'website' => self::href($page, self::labelled($page, 'Webseite')),
            'linkSicheresKontaktformular' => Links::absolute($page->first('//a[contains(@href, "sichererKontakt")]')?->getAttribute('href')),
            'url' => Links::document('behoerde', $portalId),
            'ordnerId' => $group['id'] ?? null,
            ...self::address($page, 'Hausanschrift', 'hausanschrift'),
            ...self::address($page, 'Postanschrift', 'postanschrift'),
            ...PhoneParser::parts(self::linkText($page, self::labelled($page, 'Telefon')), 'telefon'),
            ...PhoneParser::parts(self::linkText($page, self::labelled($page, 'Fax')), 'fax'),
            'logoUrl' => Links::absolute($page->first('//img[@id="behoerdeLogo"]')?->getAttribute('src')),
            'gemeindekennziffer' => self::municipalityKey($page),
            'uebergeordneteBehoerdeId' => Links::id($page->first('//a[normalize-space()="Zum Landratsamt"]')?->getAttribute('href'), 'behoerde'),
            'mitgliedVonVerwaltungsgemeinschaft' => self::administrativeAssociation($page),
            'mitgliedVonVerwaltungsgemeinschaftId' => Links::id($page->first('//a[normalize-space()="Zur Verwaltungsgemeinschaft"]')?->getAttribute('href'), 'behoerde'),
            'mitgliedschaften' => self::memberships($page),
        ];

        return array_filter($payload, static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * Breadcrumb entries after "Start", e.g. Behörden > Gemeinden.
     *
     * @return list<array{name: string, id: ?string}>
     */
    private static function breadcrumb(Document $page): array
    {
        $entries = [];

        foreach ($page->all('//a[@href="/" and normalize-space()="Start"]/following-sibling::a') as $link) {
            $entries[] = [
                'name' => (string) Document::clean($link->textContent),
                'id' => Links::id($link->getAttribute('href'), 'behoerde') ?? Links::id($link->getAttribute('href'), 'behoerdeordner'),
            ];
        }

        return $entries;
    }

    private static function summary(Document $page): ?string
    {
        // Folder-like authorities ("Amtsgerichte") describe themselves right below the title.
        $paragraph = $page->first('//*[@id="pageTitle"]/following-sibling::*[1][self::p or .//p][not(.//h2)]');

        return $paragraph === null ? null : Document::clean($paragraph->textContent);
    }

    /**
     * The container of a labelled block, e.g. <div><h2>E-Mail</h2>…</div>.
     */
    private static function labelled(Document $page, string $label): ?DOMElement
    {
        $heading = $page->first(sprintf('//*[@id="pageTitle"]/following::h2[normalize-space()=%s][1]', Document::literal($label)));

        return $heading?->parentNode instanceof DOMElement ? $heading->parentNode : null;
    }

    /**
     * @return array<string, string>
     */
    private static function address(Document $page, string $label, string $prefix): array
    {
        $container = self::labelled($page, $label);

        if ($container === null) {
            return [];
        }

        $lines = Document::lines($container);
        array_shift($lines); // the label itself

        return AddressParser::flat($lines, $prefix);
    }

    private static function mail(Document $page, ?DOMElement $container): ?string
    {
        $href = $container === null ? null : $page->first('.//a[starts-with(@href, "mailto:")]', $container)?->getAttribute('href');

        return $href === null ? null : Document::clean(substr($href, strlen('mailto:')));
    }

    private static function href(Document $page, ?DOMElement $container): ?string
    {
        return $container === null ? null : Links::absolute($page->first('.//a[@href]', $container)?->getAttribute('href'));
    }

    private static function linkText(Document $page, ?DOMElement $container): ?string
    {
        return $container === null ? null : $page->text('.//a', $container);
    }

    /**
     * The official municipality key, taken from the "Statistik kommunal" link.
     * Independent cities link their 5-digit district code instead ("09162"),
     * their municipality key is that code followed by "000".
     */
    private static function municipalityKey(Document $page): ?string
    {
        $href = $page->first('//a[contains(@href, "statistik_kommunal")]')?->getAttribute('href');

        if ($href === null || preg_match('#/(\d{8}|\d{5})\.pdf#', $href, $match) !== 1) {
            return null;
        }

        return strlen($match[1]) === 5 ? $match[1].'000' : $match[1];
    }

    /**
     * The administrative association (Verwaltungsgemeinschaft) a member
     * municipality belongs to. The page only shows the place name, e.g.
     * "Zolling", so the REST spelling is rebuilt from it.
     */
    private static function administrativeAssociation(Document $page): ?string
    {
        $name = $page->text('//h2[normalize-space()="Mitglied der Verwaltungsgemeinschaft"]/following-sibling::div[1]');

        return $name === null ? null : 'Verwaltungsgemeinschaft '.$name;
    }

    /**
     * Special-purpose associations (Zweckverbände) the authority belongs to.
     *
     * @return list<array{id: string, bezeichnung: string}>
     */
    private static function memberships(Document $page): array
    {
        $memberships = [];

        foreach ($page->all('//*[starts-with(@id, "zweckVerbandMitgliederAcc")][@role="region"]//a[@href]') as $link) {
            $id = Links::id($link->getAttribute('href'), 'behoerde');

            if ($id !== null) {
                $memberships[] = ['id' => $id, 'bezeichnung' => (string) Document::clean($link->textContent)];
            }
        }

        return $memberships;
    }
}
