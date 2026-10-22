<?php

declare(strict_types=1);

namespace Rathauslotse\BayernPortal\Scraper;

use Illuminate\Contracts\Cache\Repository;

/**
 * Facts the scraper learns while crawling and needs later:
 *
 * - Portal ids may carry leading zeros ("047980064430") that the integer
 *   ids of the DTOs lose. Every id seen on a page is remembered so that
 *   it can be turned back into a URL.
 * - Localised pages need the website's own postal code and municipality
 *   id ("gemeinde"), which only appear in links on other pages.
 *
 * @internal
 */
final class ScraperState
{
    private const string PREFIX = 'bayernportal:scraper:';

    public function __construct(private readonly Repository $store) {}

    /**
     * @param  iterable<string|null>  $portalIds
     */
    public function rememberIds(iterable $portalIds): void
    {
        foreach ($portalIds as $portalId) {
            if ($portalId !== null && str_starts_with($portalId, '0')) {
                $this->store->forever(self::PREFIX.'id:'.(int) $portalId, $portalId);
            }
        }
    }

    /**
     * Portal id candidates for an integer id, the most likely first.
     *
     * @return list<string>
     */
    public function portalIds(int $id): array
    {
        $known = $this->store->get(self::PREFIX.'id:'.$id);

        if (is_string($known) && $known !== '') {
            return [$known];
        }

        return [(string) $id, '0'.$id];
    }

    /**
     * @param  array{plz: string, behoerde: string, gemeinde: string}  $context
     */
    public function rememberLocalisation(string $authorityId, array $context): void
    {
        $this->store->forever(self::PREFIX.'localisation:'.$authorityId, $context);
    }

    /**
     * @return array{plz: string, behoerde: string, gemeinde: string}|null
     */
    public function localisation(string $authorityId): ?array
    {
        $context = $this->store->get(self::PREFIX.'localisation:'.$authorityId);

        if (! is_array($context) || ! is_string($context['plz'] ?? null) || ! is_string($context['behoerde'] ?? null) || ! is_string($context['gemeinde'] ?? null)) {
            return null;
        }

        return ['plz' => $context['plz'], 'behoerde' => $context['behoerde'], 'gemeinde' => $context['gemeinde']];
    }
}
