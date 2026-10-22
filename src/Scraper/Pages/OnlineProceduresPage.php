<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Pages;

use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Support\Links;

/**
 * /dokumente/behoerde/{id}/onlineverfahren: online procedures grouped by
 * service. The links point to the website's own procedure pages, which
 * forward to the actual procedure.
 *
 * @internal
 */
final class OnlineProceduresPage
{
    /**
     * @return list<array{id: int, portalId: string, bezeichnung: string, url: string, onlineVerfahren: list<array{id: int, kurzbeschreibung: string, url: string}>}>
     */
    public static function parse(Document $page): array
    {
        $groups = [];

        foreach ($page->all('//li[a[contains(@href, "/dokumente/leistung/")]][ul//a[contains(@href, "/dokumente/onlineverfahren/")]]') as $item) {
            $service = $page->first('a[contains(@href, "/dokumente/leistung/")]', $item);
            $serviceId = Links::id($service?->getAttribute('href'), 'leistung');

            if ($service === null || $serviceId === null) {
                continue;
            }

            $procedures = [];

            foreach ($page->all('ul//a[contains(@href, "/dokumente/onlineverfahren/")]', $item) as $link) {
                $id = Links::id($link->getAttribute('href'), 'onlineverfahren');

                if ($id !== null) {
                    $procedures[] = [
                        'id' => (int) $id,
                        'kurzbeschreibung' => Document::clean($link->getAttribute('aria-label')) ?? Document::clean($link->textContent) ?? '',
                        'url' => Links::document('onlineverfahren', $id),
                    ];
                }
            }

            $groups[] = [
                'id' => (int) $serviceId,
                'portalId' => $serviceId,
                'bezeichnung' => Document::clean($service->getAttribute('aria-label')) ?? (string) Document::clean($service->textContent),
                'url' => Links::document('leistung', $serviceId),
                'onlineVerfahren' => $procedures,
            ];
        }

        return $groups;
    }
}
