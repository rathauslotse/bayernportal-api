<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use DOMElement;
use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Parsers\AddressParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\OpeningHoursParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\PhoneParser;
use Rathauslotse\BayernPortal\Scraper\Support\Links;
use Rathauslotse\BayernPortal\Scraper\Support\SyntheticId;

/**
 * /dokumente/behoerde/{id}/adressen: buildings and opening hours.
 *
 * Every building is a group of accordions whose ids share a random token
 * that changes on each request ("XWMW9AAC0AnschriftenAcc"). Building ids
 * are therefore synthetic, see {@see SyntheticId}.
 *
 * @internal
 */
final class BuildingsPage
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function parse(Document $page, int $authorityId): array
    {
        $buildings = [];

        foreach ($page->all('//h3[substring(@id, string-length(@id) - 13) = "AnschriftenAcc"]') as $position => $heading) {
            $token = substr(substr($heading->getAttribute('id'), strlen('heading-')), 0, -strlen('AnschriftenAcc'));
            $name = $page->text('preceding::h3[not(@id)][1]', $heading) ?? '';
            $addresses = $page->byId($token.'AnschriftenAcc');
            $street = $addresses === null ? [] : self::address($page, $addresses, 'Hausanschrift', 'hausanschrift');

            $buildings[] = array_filter([
                'gebaeudeId' => SyntheticId::building($authorityId, $name, $street['hausanschriftStrasse'] ?? null, $street['hausanschriftPLZ'] ?? null),
                'behoerdeId' => $authorityId,
                'bezeichnung' => $name,
                'sortierreihenfolge' => $position + 1,
                ...$street,
                ...($addresses === null ? [] : self::address($page, $addresses, 'Postanschrift', 'postanschrift')),
                ...($addresses === null ? [] : PhoneParser::parts(self::value($page, $addresses, 'Telefon'), 'telefon')),
                ...($addresses === null ? [] : PhoneParser::parts(self::value($page, $addresses, 'Fax'), 'fax')),
                'oeffnungszeiten' => OpeningHoursParser::parse($page->byId($token.'OeffnungszeitenAcc')?->textContent),
                'behoerdenAnsprechpartnerZuordnungen' => self::contacts($page, $token),
            ], static fn (mixed $value): bool => $value !== null && $value !== []);
        }

        return $buildings;
    }

    /**
     * @return array<string, string>
     */
    private static function address(Document $page, DOMElement $panel, string $label, string $prefix): array
    {
        $paragraph = $page->first(sprintf('.//h4[normalize-space()=%s]/following-sibling::p[1]', Document::literal($label)), $panel);

        return $paragraph === null ? [] : AddressParser::flat(Document::lines($paragraph), $prefix);
    }

    private static function value(Document $page, DOMElement $panel, string $label): ?string
    {
        return $page->text(sprintf('.//h4[normalize-space()=%s]/following-sibling::*[1]', Document::literal($label)), $panel);
    }

    /**
     * Contacts linked from the building, with the same synthetic ids as {@see ContactsPage}.
     *
     * @return array{ansprechpartner: list<array{id: int}>}|null
     */
    private static function contacts(Document $page, string $token): ?array
    {
        $panel = $page->byId($token.'MitarbeiterAcc');
        $contacts = [];

        foreach ($panel === null ? [] : $page->all('.//a[contains(@href, "/mitarbeiter")]', $panel) as $link) {
            $authorityId = Links::id($link->getAttribute('href'), 'behoerde');
            $name = Document::clean($link->textContent);

            if ($authorityId !== null && $name !== null) {
                $contacts[] = ['id' => SyntheticId::contact((int) $authorityId, $name)];
            }
        }

        return $contacts === [] ? null : ['ansprechpartner' => $contacts];
    }
}
