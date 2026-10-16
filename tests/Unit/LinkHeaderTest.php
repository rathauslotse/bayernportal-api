<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Pagination\LinkHeader;

it('parses link headers', function (): void {
    $header = '<https://www.baybw-services.bayern.de/rest/allgemein/v3/behoerden?page=0&size=100>; rel="first", '
        .'<https://www.baybw-services.bayern.de/rest/allgemein/v3/behoerden?page=2&size=100>; rel="next", '
        .'<https://www.baybw-services.bayern.de/rest/allgemein/v3/behoerden?page=0&size=100>; rel="prev", '
        .'<https://www.baybw-services.bayern.de/rest/allgemein/v3/behoerden?page=80&size=100>; rel="last"';

    $links = LinkHeader::parse($header);

    expect(array_keys($links))->toBe(['first', 'next', 'prev', 'last'])
        ->and(LinkHeader::pageOf($links['next']))->toBe(2)
        ->and(LinkHeader::pageOf($links['last']))->toBe(80);
});

it('handles unquoted, multi-valued and parameterised relations', function (): void {
    $links = LinkHeader::parse('<a?page=1>;rel=next; title="Next", <b?page=3>; rel="last end"; type="text/html", <c>; title="x"');

    expect($links)->toBe(['next' => 'a?page=1', 'last' => 'b?page=3', 'end' => 'b?page=3']);
});

it('returns nothing for empty headers or urls without page', function (): void {
    expect(LinkHeader::parse(null))->toBe([])
        ->and(LinkHeader::parse('  '))->toBe([])
        ->and(LinkHeader::pageOf(null))->toBeNull()
        ->and(LinkHeader::pageOf('https://x/behoerden?size=1'))->toBeNull();
});
