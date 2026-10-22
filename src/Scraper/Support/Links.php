<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper\Support;

use Rathauslotse\BayernPortal\Scraper\Html\Document;

/**
 * URL helpers for www.bayernportal.de document links.
 *
 * @internal
 */
final class Links
{
    public const string WEBSITE = 'https://www.bayernportal.de/';

    /**
     * Portal id of a document link, as string (ids may have leading zeros).
     *
     * @param  string  $type  URL segment: behoerde, leistung, lebenslage, onlineverfahren, …
     */
    public static function id(?string $href, string $type): ?string
    {
        if ($href === null) {
            return null;
        }

        return preg_match('#/dokumente/'.preg_quote($type, '#').'/(\d+)(?:[/?\#]|$)#', $href, $match) === 1 ? $match[1] : null;
    }

    /**
     * Ids of every authority, service and online procedure linked from a page.
     *
     * @return list<string>
     */
    public static function documentIds(Document $page): array
    {
        $ids = [];

        foreach ($page->all('//a[contains(@href, "/dokumente/")]') as $link) {
            if (preg_match('#/dokumente/(?:behoerde|leistung|onlineverfahren)/(\d+)#', $link->getAttribute('href'), $match) === 1) {
                $ids[$match[1]] = $match[1];
            }
        }

        return array_values($ids);
    }

    /**
     * Canonical public URL of a document, without localisation parameters.
     */
    public static function document(string $type, string $id): string
    {
        return self::WEBSITE.'dokumente/'.$type.'/'.$id;
    }

    /**
     * Absolute URL for a link found on a page.
     */
    public static function absolute(?string $href): ?string
    {
        $href = trim($href ?? '');

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
            return null;
        }

        if (preg_match('#^https?://#i', $href) === 1) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return 'https:'.$href;
        }

        return self::WEBSITE.ltrim($href, '/');
    }

    /**
     * Drops the tracking parameter "caller" that the website appends to outbound links.
     */
    public static function withoutCaller(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return rtrim((string) preg_replace('/([?&])caller=[^&#]*&?/', '$1', $url), '?&');
    }
}
