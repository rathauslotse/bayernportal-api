<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Scraper\Html\Document;
use Rathauslotse\BayernPortal\Scraper\Parsers\AddressParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\OpeningHoursParser;
use Rathauslotse\BayernPortal\Scraper\Parsers\PhoneParser;
use Rathauslotse\BayernPortal\Scraper\Support\Links;
use Rathauslotse\BayernPortal\Scraper\Support\SyntheticId;

it('splits displayed phone numbers', function (string $display, array $parts): void {
    expect(PhoneParser::parts($display, 'telefon'))->toBe($parts);
})->with([
    'international' => ['+49 89 46002-0', ['telefonLandvorwahl' => '+49', 'telefonOrtsvorwahl' => '89', 'telefonAnlage' => '46002', 'telefonDurchwahl' => '0']],
    'national' => ['089 233 96000', ['telefonOrtsvorwahl' => '089', 'telefonAnlage' => '23396000']],
    'with slash' => ['08141/519-0', ['telefonOrtsvorwahl' => '08141', 'telefonAnlage' => '519', 'telefonDurchwahl' => '0']],
    '00 prefix' => ['0049 89 1234', ['telefonLandvorwahl' => '+49', 'telefonOrtsvorwahl' => '89', 'telefonAnlage' => '1234']],
    'empty' => ['  ', []],
]);

it('splits address lines', function (): void {
    expect(AddressParser::parse(['Bahnhofstr. 7', '85540 Haar']))->toBe(['strasse' => 'Bahnhofstr. 7', 'plz' => '85540', 'ort' => 'Haar'])
        ->and(AddressParser::parse(['Rathaus', 'Marienplatz 8', '80331 München']))->toBe(['strasse' => 'Rathaus, Marienplatz 8', 'plz' => '80331', 'ort' => 'München'])
        ->and(AddressParser::parse(['Postfach 1209']))->toBe(['strasse' => 'Postfach 1209', 'plz' => null, 'ort' => null])
        ->and(AddressParser::parse([' ', '']))->toBeNull()
        ->and(AddressParser::flat(['85540 Haar'], 'postanschrift'))->toBe(['postanschriftPLZ' => '85540', 'postanschriftOrt' => 'Haar'])
        ->and(AddressParser::flat([], 'postanschrift'))->toBe([]);
});

it('parses opening hours into morning and afternoon slots', function (): void {
    $hours = OpeningHoursParser::parse('Allgemein Mo 07:30 Uhr - 12:30 Uhr Mi 7.30 Uhr - 12:30 Uhr und 15:00 Uhr - 18:00 Uhr Sa geschlossen');

    expect($hours)->toBe([
        'oeffnungszeit' => [['zeitangabe' => [
            ['typ' => 'MO', 'vonVormittags' => '07:30', 'bisVormittags' => '12:30'],
            ['typ' => 'MI', 'vonVormittags' => '07:30', 'bisVormittags' => '12:30', 'vonNachmittags' => '15:00', 'bisNachmittags' => '18:00'],
        ]]],
        'nachVereinbarung' => false,
    ]);
});

it('handles appointments, free text and missing opening hours', function (): void {
    expect(OpeningHoursParser::parse('Termine nach Vereinbarung'))->toBe(['nachVereinbarung' => true])
        ->and(OpeningHoursParser::parse('Siehe Webseite'))->toBe(['sonderzeiten' => 'Siehe Webseite'])
        ->and(OpeningHoursParser::parse('Es wurden noch keine Öffnungszeiten erfasst.'))->toBeNull()
        ->and(OpeningHoursParser::parse(null))->toBeNull();
});

it('extracts and builds document links', function (): void {
    expect(Links::id('/dokumente/leistung/047980064430?plz=85540', 'leistung'))->toBe('047980064430')
        ->and(Links::id('/dokumente/behoerde/66552873578/adressen', 'behoerde'))->toBe('66552873578')
        ->and(Links::id('/dokumente/behoerde/66552873578', 'leistung'))->toBeNull()
        ->and(Links::id(null, 'leistung'))->toBeNull()
        ->and(Links::document('behoerde', '0123'))->toBe('https://www.bayernportal.de/dokumente/behoerde/0123')
        ->and(Links::absolute('/bilder/1'))->toBe('https://www.bayernportal.de/bilder/1')
        ->and(Links::absolute('//cdn.example/x'))->toBe('https://cdn.example/x')
        ->and(Links::absolute('https://example.org'))->toBe('https://example.org')
        ->and(Links::absolute('#top'))->toBeNull()
        ->and(Links::absolute('javascript:void(0)'))->toBeNull()
        ->and(Links::absolute(null))->toBeNull()
        ->and(Links::withoutCaller('https://x.example/a.pdf?caller=665'))->toBe('https://x.example/a.pdf')
        ->and(Links::withoutCaller('https://x.example/a?caller=665&b=1'))->toBe('https://x.example/a?b=1')
        ->and(Links::withoutCaller('https://x.example/a?b=1&caller=665'))->toBe('https://x.example/a?b=1')
        ->and(Links::withoutCaller(null))->toBeNull();
});

it('derives stable synthetic ids from content', function (): void {
    $id = SyntheticId::contact(66552873578, 'Bukowski, Andreas');

    expect($id)->toBeGreaterThan(0)
        ->and($id)->toBeLessThan(2 ** 52)
        ->and(SyntheticId::contact(66552873578, ' bukowski,   Andreas '))->toBe($id)
        ->and(SyntheticId::contact(1, 'Bukowski, Andreas'))->not->toBe($id)
        ->and(SyntheticId::building(1, 'Rathaus', 'Marienplatz 8', '80331'))->toBe(SyntheticId::building(1, 'Rathaus', 'Marienplatz 8', '80331'));
});

it('queries HTML documents', function (): void {
    $page = new Document('<html><body><h1>Grüß Gott</h1><p id="a">Eins<br>Zwei</p><div id="b"><!-- c --><b>fett</b> </div><p title=\'say "hi"\'>x</p></body></html>');

    expect($page->text('//h1'))->toBe('Grüß Gott')
        ->and($page->text('//h2'))->toBeNull()
        ->and($page->all('//p'))->toHaveCount(2)
        ->and(Document::lines($page->byId('a') ?? throw new RuntimeException))->toBe(['Eins', 'Zwei'])
        ->and($page->innerHtml($page->byId('b') ?? throw new RuntimeException))->toBe('<b>fett</b>')
        ->and($page->first('//p[@title='.Document::literal('say "hi"').']'))->not->toBeNull()
        ->and(Document::literal("it's"))->toBe('"it\'s"')
        ->and(Document::literal('it\'s "x"'))->toBe("concat('it', \"'\", 's \"x\"')")
        ->and(Document::clean("  a \u{00A0} b \n"))->toBe('a b')
        ->and(Document::clean(' '))->toBeNull()
        ->and(Document::clean(null))->toBeNull();
});

it('derives the municipality key of independent cities from their district code', function (): void {
    $payload = Rathauslotse\BayernPortal\Scraper\Pages\AuthorityPage::parse(new Document(htmlFixture('authority-independent-city')), '88887100385');

    expect($payload['gemeindekennziffer'])->toBe('09162000')
        ->and($payload['bezeichnung'])->toBe('Landeshauptstadt München');
});
