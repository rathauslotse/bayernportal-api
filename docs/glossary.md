# Glossary: German → English

The package uses English names everywhere. German appears only in HTTP paths, query parameters and raw payloads. This table is the reference for every translation.

## Resources and entities

| German (API) | English (package) | Notes |
|---|---|---|
| Dienststelle | `Agency` | identified by `agencyKey` |
| Dienststellenschlüssel | `agencyKey` | 7 digits |
| Außenstelle | branch office | `Agency::isBranchOffice()` |
| dienststelleLfdNr | `branchNumber` | 0 = main agency, 1…n = branch offices |
| Behörde | `Authority` | |
| Behördenart | `type` | |
| Behördengruppe | `group` | |
| Gebäude | `Building` | |
| Ansprechpartner | `Contact` | |
| Leistung | `Service` | |
| Leistungsbeschreibung | `ServiceDescription` | |
| Formular | `Form` | |
| Onlineverfahren | `OnlineProcedure` | |
| Lebenslage | `LifeSituation` | |
| abgegebene Leistungen | `delegatedServices()` | district governments only |
| Zuständigkeit | `Responsibility` | |
| Gemeindekennziffer / Amtlicher Gemeindeschlüssel | `municipalityKey` | 8 digits |
| Bezirksregierung | district government | |
| Zentrale Redaktion | central editorial office | issues credentials |

## Query parameters

| REST parameter | Package parameter |
|---|---|
| `full` | `full` |
| `page` / `size` | `page` / `size` (`pageSize` for `all()`) |
| `gemeindekennziffer` | `municipalityKey` |
| `gruppiertNachLeistungen` | `forms()` vs. `formsByService()`, `onlineProcedures()` vs. `onlineProceduresByService()` |
| `mitRegionalenErgaenzungen` | `withRegionalSupplements` |
| `mitZustaendigkeiten` | `withResponsibilities` |
| `mitIDallgemeineDaten` | `withInternalIds` |
| `sicheresKontaktformular` | `withSecureContactForm` |
| `nurDirekteLeistungszuordnungen` | `onlyDirectAssignments` |
| `behoerdenAnsprechpartnerZuordnungen` | `contactAssignments` (`ContactAssignmentMode`) |
| `leistungenZugeordnet` | `onlyWithServices` |

## Fields

| German (API) | English (package) |
|---|---|
| bezeichnung | `name` (entities) / `title` (forms, links, …) |
| bezeichnungZusatz | `nameSuffix` |
| kurzbeschreibung | `summary` on top-level entities, `title` on sub-objects (forms, links, legal bases, online procedures, required documents) |
| langbeschreibung | `description` |
| sortierreihenfolge | `sortOrder` |
| email / deMail / website | `email` / `deMail` / `website` |
| logo / logoLangtext | `logo` / `logoWithText` |
| behoerdeZuordnungen | `subordinateAuthorityIds` |
| behoerdenGebaeudeZuordnungen | `buildingIds` |
| behoerdenAnsprechpartnerZuordnungen | `contactIds` |
| linkSicheresKontaktformular | `secureContactFormUrl` |
| mitgliedVonVerwaltungsgemeinschaft | `administrativeAssociation` |
| hausanschrift… | `streetAddress` (`Address`) |
| postanschrift… | `postalAddress` (`Address`) |
| Strasse / PLZ / Ort | `street` / `postalCode` / `city` |
| telefon… / fax… / mobil… | `phone` / `fax` / `mobile` (`PhoneNumber`) |
| Landvorwahl / Ortsvorwahl / Anlage, Nummer / Durchwahl | `countryCode` / `areaCode` / `number` / `extension` |
| oeffnungszeiten | `openingHours` (`OpeningHours`) |
| sprechzeiten | `consultationHours` (`OpeningHours`) |
| nachVereinbarung | `byAppointment` |
| sonderzeiten | `specialHours` |
| zeitangabe | `TimeSlot` |
| typ (MO … SO) | `day` (`Weekday`) |
| vonVormittags / bisVormittags | `morningFrom` / `morningUntil` |
| vonNachmittags / bisNachmittags | `afternoonFrom` / `afternoonUntil` |
| anrede | `salutation` |
| amtsbezeichnung | `officialTitle` |
| akademischerGrad | `academicDegree` |
| vorname / nachname | `firstName` / `lastName` |
| funktion | `role` |
| stellenbezeichnung | `jobTitle` |
| zimmer | `room` |
| behoerdeBezeichnung / gebaeudeBezeichnung | `authorityName` / `buildingName` |
| synonyme | `synonyms` |
| letzteAenderung | `lastModifiedAt` |
| stand | `updatedAt` |
| status (live/working/inactive) | `status` (`ServiceStatus`) |
| voraussetzungen | `prerequisites` |
| verfahrensablauf | `procedure` |
| hinweise | `notes` |
| fristen | `deadlines` |
| bearbeitungsdauer | `processingTime` |
| kosten | `fees` |
| unterlagen | `requiredDocuments` (`RequiredDocument`) |
| rechtsvorschriften | `legalBases` (`LegalBasis`) |
| rechtsbehelf | `legalRemedy` (`LegalRemedy`) |
| links | `links` (`Link`) |
| verwandteLeistungen / direktVerwandt | `relatedServices` (`RelatedService`) / `direct` |
| verantwortlicheBehoerde | `responsibleAuthority` |
| regionaleErgaenzungen | `regionalSupplementIds` |
| zustaendigkeiten | `responsibilities` |
| bwwlink | `portalUrl` |
| anschriften | `buildings` (`ResponsibleBuilding`) |
| schriftformErfordernis | `writtenFormRequirement` (`WrittenFormRequirement`) + `writtenFormRequirementDescription` |
| vorausfuellbar | `prefillable` |
| kategorie (buerger/unternehmer/verwaltung) | `category` (`LifeSituationCategory`: Citizen/Business/Administration) |
| vorgaengerId | `parentId` |
| quelle (Bild) | `source` |
| mimetype | `mimeType` |

## Enum values

| Enum | Cases (API value) |
|---|---|
| `Weekday` | Monday (MO), Tuesday (DI), Wednesday (MI), Thursday (DO), Friday (FR), Saturday (SA), Sunday (SO) |
| `ServiceStatus` | Live (live), Working (working), Inactive (inactive) |
| `LifeSituationCategory` | Citizen (buerger), Business (unternehmer), Administration (verwaltung) |
| `WrittenFormRequirement` | SignatureRequired (1), SignatureAndPersonalAppearanceRequired (2), NoSignatureRequired (3) |
| `ContactAssignmentMode` | PreferDirect (DIREKTBEVORZUGT), Direct (DIREKT), All (ALLE), None (NEIN) |
