<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use DOMElement;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Parsers\OpeningHoursParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\PhoneParser;
use Rathauslotse\BayernPortal\Scraper\Support\Links;
use Rathauslotse\BayernPortal\Scraper\Support\SyntheticId;

/**
 * /dokumente/behoerde/{id}/mitarbeiter/alle: the contacts of an authority.
 *
 * Each contact is an accordion headed "Last, First - Role". Ids are
 * synthetic, see {@see SyntheticId}.
 *
 * @internal
 */
final class ContactsPage
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function parse(Document $page, int $authorityId): array
    {
        $contacts = [];

        foreach ($page->all('//section[h3[starts-with(@id, "heading-")]]') as $position => $section) {
            $heading = (string) $page->text('h3', $section);
            $parts = explode(' - ', $heading, 2);
            $name = $parts[0];
            $role = $parts[1] ?? null;
            $panel = $page->first('.//*[@role="region"]', $section);
            $worksFor = $panel === null ? null : $page->first('.//a[contains(@href, "/dokumente/behoerde/")]', $panel);
            $worksForId = Links::id($worksFor?->getAttribute('href'), 'behoerde');
            $ownerId = $worksForId === null ? $authorityId : (int) $worksForId;
            [$lastName, $firstName] = self::name($name);

            $contacts[] = array_filter([
                'ansprechpartnerId' => SyntheticId::contact($ownerId, $name),
                'nachname' => $lastName,
                'vorname' => $firstName,
                'funktion' => Document::clean($role),
                'akademischerGrad' => self::value($page, $panel, 'Akademischer Grad'),
                'amtsbezeichnung' => self::value($page, $panel, 'Amtsbezeichnung'),
                'stellenbezeichnung' => self::value($page, $panel, 'Stellenbezeichnung'),
                'zimmer' => self::value($page, $panel, 'Zimmer'),
                'email' => self::mail($page, $panel),
                'website' => Links::absolute(self::valueElement($page, $panel, 'Webseite')?->getAttribute('href')),
                'sortierreihenfolge' => $position + 1,
                'behoerdeId' => $ownerId,
                'behoerdeBezeichnung' => $worksFor === null ? null : Document::clean($worksFor->textContent),
                ...PhoneParser::parts(self::value($page, $panel, 'Telefon'), 'telefon'),
                ...PhoneParser::parts(self::value($page, $panel, 'Telefax') ?? self::value($page, $panel, 'Fax'), 'fax'),
                ...PhoneParser::parts(self::value($page, $panel, 'Mobil'), 'mobil'),
                'sprechzeiten' => OpeningHoursParser::parse(self::value($page, $panel, 'Sprechzeiten')),
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
        }

        return $contacts;
    }

    /**
     * "Bukowski, Andreas" → [Bukowski, Andreas]; organisations keep their full name.
     *
     * @return array{0: string, 1: ?string}
     */
    private static function name(string $name): array
    {
        $parts = array_map('trim', explode(',', $name, 2));

        return count($parts) === 2 && $parts[1] !== '' ? [$parts[0], $parts[1]] : [trim($name), null];
    }

    /**
     * The element after a label, e.g. <div>Telefon</div><a href="tel:…">…</a>.
     */
    private static function valueElement(Document $page, ?DOMElement $panel, string $label): ?DOMElement
    {
        return $panel === null ? null : $page->first(sprintf('.//*[*[1][normalize-space()=%s]]/*[2]', Document::literal($label)), $panel);
    }

    private static function value(Document $page, ?DOMElement $panel, string $label): ?string
    {
        $element = self::valueElement($page, $panel, $label);

        return $element === null ? null : Document::clean($element->textContent);
    }

    private static function mail(Document $page, ?DOMElement $panel): ?string
    {
        $href = $panel === null ? null : $page->first('.//a[starts-with(@href, "mailto:")]', $panel)?->getAttribute('href');

        return $href === null ? null : Document::clean(substr($href, strlen('mailto:')));
    }
}
