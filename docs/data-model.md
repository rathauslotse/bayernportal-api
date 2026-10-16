# Data model

All DTOs live in `Rathauslotse\BayernPortal\Data`. They are `readonly`, use English names, and keep the unmodified API payload in `raw` (top-level entities only). Each one can be built from an array with `fromArray()`, which is handy in tests and when reading cached payloads.

Text from the editorial office (descriptions, procedures, fees, …) is **HTML**.

The original field is the field name in the BayernPortal XML schema (WSDL v3). The JSON rendering of the REST API uses the same names.

## Agency

`Agency extends Authority`. An agency (*Dienststelle*).

| Property | Type | Original field |
|---|---|---|
| *all `Authority` properties* | | |
| `agencyKey` | `string` | `dienststellenschluessel` |
| `branchNumber` | `int` | `dienststelleLfdNr` |
| `nameSuffix` | `?string` | `bezeichnungZusatz` |
| `administrativeAssociation` | `?string` | `mitgliedVonVerwaltungsgemeinschaft` |

Methods: `isBranchOffice()`, `fullName(string $separator = ' – ')`.

## Authority

| Property | Type | Original field |
|---|---|---|
| `id` | `int` | `id` |
| `name` | `string` | `bezeichnung` |
| `type` | `string` | `behoerdenart` |
| `group` | `?string` | `behoerdengruppe` |
| `sortOrder` | `?int` | `sortierreihenfolge` |
| `summary` | `?string` | `kurzbeschreibung` |
| `description` | `?string` | `langbeschreibung` |
| `email` | `?string` | `email` |
| `deMail` | `?string` | `deMail` |
| `website` | `?string` | `website` |
| `logo` | `?Image` | `logo` |
| `logoWithText` | `?Image` | `logoLangtext` |
| `subordinateAuthorityIds` | `list<int>` | `behoerdeZuordnungen` |
| `buildingIds` | `list<int>` | `behoerdenGebaeudeZuordnungen` |
| `secureContactFormUrl` | `?string` | `linkSicheresKontaktformular` |
| `raw` | `array` | |

## Building

| Property | Type | Original field |
|---|---|---|
| `id` | `int` | `gebaeudeId` |
| `authorityId` | `int` | `behoerdeId` |
| `name` | `string` | `bezeichnung` |
| `streetAddress` | `?Address` | `hausanschriftStrasse`, `…PLZ`, `…Ort` |
| `postalAddress` | `?Address` | `postanschriftStrasse`, `…PLZ`, `…Ort` |
| `phone` | `?PhoneNumber` | `telefonLandvorwahl`, `…Ortsvorwahl`, `…Anlage`, `…Durchwahl` |
| `fax` | `?PhoneNumber` | `fax…` |
| `openingHours` | `?OpeningHours` | `oeffnungszeiten` |
| `logo` | `?Image` | `logo` |
| `sortOrder` | `?int` | `sortierreihenfolge` |
| `contactIds` | `list<int>` | `behoerdenAnsprechpartnerZuordnungen` |
| `raw` | `array` | |

## Contact

Used for `contacts()->…`, `authorities()->contacts()` and `authorities()->buildingContact()`.

| Property | Type | Original field |
|---|---|---|
| `id` | `int` | `ansprechpartnerId` |
| `lastName` | `string` | `nachname` |
| `firstName` | `?string` | `vorname` |
| `salutation` | `?string` | `anrede` |
| `officialTitle` | `?string` | `amtsbezeichnung` |
| `academicDegree` | `?string` | `akademischerGrad` |
| `role` | `?string` | `funktion` |
| `jobTitle` | `?string` | `stellenbezeichnung` |
| `email` | `?string` | `email` |
| `website` | `?string` | `website` |
| `room` | `?string` | `zimmer` |
| `sortOrder` | `?int` | `sortierreihenfolge` |
| `authorityId` | `?int` | `behoerdeId` |
| `authorityName` | `?string` | `behoerdeBezeichnung` |
| `buildingId` | `?int` | `gebaeudeId` |
| `buildingName` | `?string` | `gebaeudeBezeichnung` |
| `phone` / `fax` / `mobile` | `?PhoneNumber` | `telefon…` / `fax…` / `mobil…` |
| `consultationHours` | `?OpeningHours` | `sprechzeiten` |
| `logo` | `?Image` | `logo` |
| `services` | `list<ServiceReference>` | `leistungen` |
| `raw` | `array` | |

Method: `fullName()` returns, e.g., "Dr. Maria Huber".

## Service

| Property | Type | Original field |
|---|---|---|
| `id` | `int` | `id` |
| `name` | `string` | `bezeichnung` |
| `url` | `?string` | `url` |
| `synonyms` | `list<string>` | `synonyme` |
| `lifeSituations` | `list<LifeSituationReference>` | `lebenslagen` |
| `lastModifiedAt` | `?CarbonImmutable` | `letzteAenderung` |
| `raw` | `array` | |

## ServiceDescription

`ServiceDescription extends Service`.

| Property | Type | Original field |
|---|---|---|
| *all `Service` properties* | | |
| `status` | `?ServiceStatus` | `status` |
| `summary` | `?string` | `kurzbeschreibung` |
| `description` | `?string` | `langbeschreibung` |
| `prerequisites` | `?string` | `voraussetzungen` |
| `procedure` | `?string` | `verfahrensablauf` |
| `notes` | `?string` | `hinweise` |
| `deadlines` | `?string` | `fristen` |
| `processingTime` | `?string` | `bearbeitungsdauer` |
| `fees` | `?string` | `kosten` |
| `requiredDocuments` | `list<RequiredDocument>` | `unterlagen` |
| `forms` | `list<Form>` | `formulare` |
| `onlineProcedures` | `list<OnlineProcedure>` | `onlineVerfahren` |
| `legalBases` | `list<LegalBasis>` | `rechtsvorschriften` |
| `legalRemedy` | `?LegalRemedy` | `rechtsbehelf` |
| `links` | `list<Link>` | `links` |
| `relatedServices` | `list<RelatedService>` | `verwandteLeistungen` |
| `updatedAt` | `?CarbonImmutable` | `stand` |
| `responsibleAuthority` | `?string` | `verantwortlicheBehoerde` |
| `regionalSupplementIds` | `list<int>` | `regionaleErgaenzungen` |
| `responsibilities` | `list<Responsibility>` | `zustaendigkeiten` |

Method: `isLive()`.

### Sub-objects

| Class | Properties (original field) |
|---|---|
| `RequiredDocument` | `title` (kurzbeschreibung), `description` (langbeschreibung), `service: ?ServiceReference` (leistungId, leistungUrl), `id` |
| `Form` | `title` (kurzbeschreibung), `url`, `description` (langbeschreibung), `writtenFormRequirement: ?WrittenFormRequirement` (schriftformErfordernis.id), `writtenFormRequirementDescription` (schriftformErfordernis.beschreibung), `prefillable` (vorausfuellbar), `services: list<ServiceReference>` (leistungen), `id` |
| `OnlineProcedure` | `title` (kurzbeschreibung), `url`, `description` (langbeschreibung), `services: list<ServiceReference>`, `id` |
| `LegalBasis` | `title` (kurzbeschreibung), `url`, `description` (langbeschreibung), `id` |
| `Link` | `title` (kurzbeschreibung), `url`, `description` (langbeschreibung), `id` |
| `LegalRemedy` | `summary` (kurzbeschreibung), `service: ?ServiceReference` (leistungId, leistungBezeichnung, leistungUrl) |
| `RelatedService` | `id`, `name` (bezeichnung), `url`, `direct` (direktVerwandt) |
| `Responsibility` | `authorityId` (behoerdId), `name` (bezeichnung), `buildings: list<ResponsibleBuilding>` (anschriften), `email`, `website`, `portalUrl` (bwwlink), `secureContactFormUrl` (linkSicheresKontaktformular) |
| `ResponsibleBuilding` | `id` (gebaeudeId), `streetAddress` (hausanschrift), `postalAddress` (postanschrift), `phone` (telefon, plain string), `fax`, `contactIds` |

The `id` of sub-objects is only filled with `withInternalIds: true` (*mitIDallgemeineDaten*), except for agency forms.

## Grouped forms and online procedures

| Class | Properties |
|---|---|
| `ServiceWithForms` | `service: ServiceReference`, `forms: list<Form>`, `raw` |
| `ServiceWithOnlineProcedures` | `service: ServiceReference`, `onlineProcedures: list<OnlineProcedure>`, `raw` |

## LifeSituation

| Property | Type | Original field |
|---|---|---|
| `id` | `string` | `id` |
| `name` | `string` | `bezeichnung` |
| `summary` | `?string` | `kurzbeschreibung` |
| `description` | `?string` | `langbeschreibung` |
| `category` | `?LifeSituationCategory` | `kategorie` |
| `parentId` | `?string` | `vorgaengerId` ("0" or empty → `null`) |
| `updatedAt` | `?CarbonImmutable` | `stand` |
| `synonyms` | `list<string>` | `synonyme` |
| `services` | `list<ServiceReference>` | `leistungen` |
| `raw` | `array` | |

Method: `isTopLevel()`. `LifeSituationNode` (from `tree()`) has `lifeSituation` and `children: list<LifeSituation>`.

## References

| Class | Properties |
|---|---|
| `ServiceReference` | `id`, `name`, `url` (from `id`/`leistungId`, `bezeichnung`/`leistungBezeichnung`, `url`/`leistungUrl`) |
| `LifeSituationReference` | `id`, `name` |

## Value objects

| Class | Properties | Methods |
|---|---|---|
| `Address` | `street`, `postalCode`, `city` | `format(string $separator = ', ')`, `__toString()` |
| `PhoneNumber` | `countryCode`, `areaCode`, `number`, `extension` | `format()` → "+49 89 233-96000", `toE164()` → "+498923396000" or `null` without country code, `__toString()` |
| `OpeningHours` | `byAppointment`, `specialHours`, `timeSlots: list<TimeSlot>` | `forDay(Weekday)`, `isEmpty()` |
| `TimeSlot` | `day: Weekday`, `morningFrom`, `morningUntil`, `afternoonFrom`, `afternoonUntil` | `ranges()`, `format()` → "08:00–12:00, 14:00–16:00" |
| `Image` | `base64`, `mimeType`, `alt`, `title`, `source` | `contents()` (binary), `toDataUri()` |

## Enums

See the [glossary](glossary.md#enum-values) for all cases. `Weekday` has `isoNumber()` and `fromIsoNumber()`. `WrittenFormRequirement` has `requiresSignature()` and `requiresPersonalAppearance()`.

## Unknown values

Unknown enum values (e.g. a new status) become `null` instead of throwing. Missing required strings become `''`, missing IDs become `0`. The original data is always available in `raw`.
