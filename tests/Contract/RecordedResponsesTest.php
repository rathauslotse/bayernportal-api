<?php

declare(strict_types=1);

use Rathauslotse\BayernPortal\Support\Payload;

/*
|--------------------------------------------------------------------------
| Contract check for recorded live responses
|--------------------------------------------------------------------------
|
| `BAYERNPORTAL_RECORD_FIXTURES=1 composer test:integration` writes real
| responses to tests/recorded. This test verifies, offline, that the
| wrapper paths used by the resources find the items in those responses
| and reports every top-level field the mappers do not know yet.
|
*/

const AUTHORITY_KEYS = ['id', 'behoerdeId', 'bezeichnung', 'behoerdenart', 'logo', 'logoLangtext', 'kurzbeschreibung', 'langbeschreibung', 'email', 'deMail', 'website', 'behoerdengruppe', 'sortierreihenfolge', 'behoerdeZuordnungen', 'behoerdenGebaeudeZuordnungen', 'linkSicheresKontaktformular'];
const AGENCY_KEYS = [...AUTHORITY_KEYS, 'bezeichnungZusatz', 'dienststellenschluessel', 'dienststelleLfdNr', 'mitgliedVonVerwaltungsgemeinschaft'];
const PHONE_KEYS = ['telefonLandvorwahl', 'telefonOrtsvorwahl', 'telefonAnlage', 'telefonDurchwahl', 'faxLandvorwahl', 'faxOrtsvorwahl', 'faxAnlage', 'faxDurchwahl'];
const BUILDING_KEYS = ['id', 'gebaeudeId', 'behoerdeId', 'bezeichnung', 'hausanschriftPLZ', 'hausanschriftOrt', 'hausanschriftStrasse', 'postanschriftPLZ', 'postanschriftOrt', 'postanschriftStrasse', 'logo', 'sortierreihenfolge', ...PHONE_KEYS, 'oeffnungszeiten', 'behoerdenAnsprechpartnerZuordnungen'];
const CONTACT_KEYS = ['id', 'ansprechpartnerId', 'behoerdeId', 'gebaeudeId', 'behoerdeBezeichnung', 'gebaeudeBezeichnung', 'anrede', 'logo', 'amtsbezeichnung', 'akademischerGrad', 'vorname', 'nachname', 'funktion', 'stellenbezeichnung', 'email', 'website', 'zimmer', 'sortierreihenfolge', ...PHONE_KEYS, 'mobilLandvorwahl', 'mobilOrtsvorwahl', 'mobilNummer', 'sprechzeiten', 'leistungen'];
const SERVICE_REFERENCE_KEYS = ['id', 'bezeichnung', 'url', 'leistungId', 'leistungBezeichnung', 'leistungUrl'];
const SERVICE_KEYS = ['id', 'letzteAenderung', 'bezeichnung', 'url', 'synonyme', 'lebenslagen'];
const SERVICE_DESCRIPTION_KEYS = [...SERVICE_KEYS, 'status', 'kurzbeschreibung', 'langbeschreibung', 'voraussetzungen', 'verfahrensablauf', 'hinweise', 'fristen', 'bearbeitungsdauer', 'unterlagen', 'kosten', 'formulare', 'onlineVerfahren', 'onlineverfahren', 'rechtsvorschriften', 'rechtsbehelf', 'links', 'verwandteLeistungen', 'stand', 'verantwortlicheBehoerde', 'regionaleErgaenzungen', 'zustaendigkeiten'];
const FORM_KEYS = ['id', 'kurzbeschreibung', 'url', 'langbeschreibung', 'schriftformErfordernis', 'vorausfuellbar', 'leistungen'];
const ONLINE_PROCEDURE_KEYS = ['id', 'kurzbeschreibung', 'url', 'langbeschreibung', 'leistungen'];
const LIFE_SITUATION_KEYS = ['id', 'bezeichnung', 'kurzbeschreibung', 'langbeschreibung', 'kategorie', 'vorgaengerId', 'stand', 'synonyme', 'leistungen'];

/**
 * Wrapper path and known fields per endpoint, mirroring the resources.
 *
 * @return array{0: list<string>, 1: list<string>}|null
 */
function contractFor(string $path, array $query): ?array
{
    $grouped = ($query['gruppiertNachLeistungen'] ?? 'true') !== 'false';

    return match (true) {
        (bool) preg_match('#^dienststellen(/\d+)?$#', $path) => [['dienststellen', 'dienststelle'], AGENCY_KEYS],
        (bool) preg_match('#^dienststellen/\d+/leistungen$#', $path) => [['leistungen', 'leistung'], SERVICE_KEYS],
        (bool) preg_match('#^dienststellen/\d+/formulare$#', $path) => $grouped
            ? [['formulare', 'leistungMitFormularen|leistungenMitFormularen'], ['leistungId', 'leistungBezeichnung', 'leistungUrl', 'formulare']]
            : [['formulare', 'formular'], FORM_KEYS],
        (bool) preg_match('#^dienststellen/\d+/onlineverfahren$#', $path) => $grouped
            ? [['onlineverfahren|onlineVerfahren', 'leistungMitOnlineverfahren|leistungMitOnlineVerfahren'], ['leistungId', 'leistungBezeichnung', 'leistungUrl', 'onlineVerfahren', 'onlineverfahren']]
            : [['onlineverfahren|onlineVerfahren', 'onlineVerfahren|onlineverfahren'], ONLINE_PROCEDURE_KEYS],
        (bool) preg_match('#^(dienststellen/\d+/)?leistungsbeschreibungen(/\d+)?$#', $path) => [['leistungsbeschreibungen', 'leistungsbeschreibung'], SERVICE_DESCRIPTION_KEYS],
        (bool) preg_match('#^(dienststellen/\d+/)?lebenslagen(/\d+)?$#', $path) => [['lebenslagen', 'lebenslage'], LIFE_SITUATION_KEYS],
        (bool) preg_match('#^behoerden(/\d+)?$#', $path) => [['behoerden', 'behoerde'], AUTHORITY_KEYS],
        (bool) preg_match('#^behoerden/\d+/gebaeude/\d+$#', $path) => [['BehoerdenGebaeude|behoerdenGebaeude|gebaeude'], BUILDING_KEYS],
        (bool) preg_match('#^(behoerden/\d+/(gebaeude/\d+/)?)?ansprechpartner(/\d+)?$#', $path) => [['ansprechpartner', 'ap'], CONTACT_KEYS],
        (bool) preg_match('#/(abgegebene-)?leistungen$#', $path) => [['leistungen', 'leistung'], SERVICE_REFERENCE_KEYS],
        (bool) preg_match('#^leistungen(/\d+)?$#', $path) => [['leistungen', 'leistung'], SERVICE_KEYS],
        default => null,
    };
}

$recordings = glob(dirname(__DIR__).'/recorded/*.json') ?: [];

if ($recordings === []) {
    it('checks recorded live responses against the mappers', function (): void {
        $this->markTestSkipped('No recordings in tests/recorded. Run BAYERNPORTAL_RECORD_FIXTURES=1 composer test:integration first.');
    });

    return;
}

it('maps the recorded response', function (string $file): void {
    $recording = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $contract = contractFor($recording['path'], $recording['query']);

    expect($contract)->not->toBeNull('No contract defined for ['.$recording['path'].'].');

    [$wrappers, $knownKeys] = $contract;
    $body = $recording['body'];

    if ($body === [] || $body === '' || $body === null) {
        $this->markTestSkipped('Empty response for ['.$recording['path'].'].');
    }

    $items = Payload::items($body, ...$wrappers);

    expect($items)->not->toBeEmpty('The wrapper path ['.implode(' > ', $wrappers).'] found no items in ['.$recording['path'].'].');

    $unknown = [];

    foreach ($items as $item) {
        foreach (array_keys($item->raw()) as $key) {
            $plain = ltrim((string) $key, '@_-');

            if (! in_array($plain, $knownKeys, true)) {
                $unknown[$plain] = true;
            }
        }
    }

    expect(array_keys($unknown))->toBe([], sprintf(
        'Unmapped fields in [%s]: %s. Either the wrapper path is wrong or the mapper needs to learn these fields.',
        $recording['path'],
        implode(', ', array_keys($unknown)),
    ));
})->with(array_combine(array_map('basename', $recordings), $recordings));
