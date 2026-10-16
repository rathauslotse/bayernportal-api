<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Pagination;

/**
 * Parser for RFC 8288 "Link" headers, e.g.
 * `<https://…?page=1&size=100>; rel="next", <https://…?page=9&size=100>; rel="last"`.
 *
 * @internal
 */
final class LinkHeader
{
    /**
     * @return array<string, string> URL per relation type (first, prev, next, last, …).
     */
    public static function parse(?string $header): array
    {
        if ($header === null || trim($header) === '') {
            return [];
        }

        $links = [];

        preg_match_all('/<([^>]*)>\s*((?:;\s*[^,;]+)*)/', $header, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $url, $params]) {
            if (preg_match('/;\s*rel\s*=\s*"?([^";]+)"?/i', $params, $rel) !== 1) {
                continue;
            }

            foreach (preg_split('/\s+/', strtolower(trim($rel[1]))) ?: [] as $type) {
                $links[$type] ??= $url;
            }
        }

        return $links;
    }

    /**
     * The "page" query parameter of a link, if present.
     */
    public static function pageOf(?string $url): ?int
    {
        if ($url === null) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return isset($query['page']) && is_numeric($query['page']) ? (int) $query['page'] : null;
    }
}
