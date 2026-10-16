# Service descriptions

A **service description** (*Leistungsbeschreibung*) is the full editorial content of a service: prerequisites, procedure, fees, deadlines, required documents, forms, online procedures, legal bases and, for a given municipality, the responsible authorities.

Text fields contain **HTML** as maintained by the editorial office. Sanitise it before rendering, e.g. with an HTML purifier.

```php
$descriptions = BayernPortal::serviceDescriptions();   // Rathauslotse\BayernPortal\Resources\ServiceDescriptionResource
```

## `find()`

`GET leistungsbeschreibungen/{leistung-id}`

```php
use Rathauslotse\BayernPortal\Enums\ContactAssignmentMode;

$description = BayernPortal::serviceDescriptions()->find(
    1010,
    municipalityKey: '09162000',
    withRegionalSupplements: true,
    withSecureContactForm: true,
    contactAssignments: ContactAssignmentMode::PreferDirect,
    withInternalIds: false,
);
```

| Parameter | REST name | Default |
|---|---|---|
| `int $serviceId` | path | |
| `?string $municipalityKey` | `gemeindekennziffer` | scope. Required to get `responsibilities` |
| `bool $withRegionalSupplements` | `mitRegionalenErgaenzungen` | `true` |
| `bool $withSecureContactForm` | `sicheresKontaktformular` | `false` |
| `?ContactAssignmentMode $contactAssignments` | `behoerdenAnsprechpartnerZuordnungen` | `null` (API default) |
| `bool $withInternalIds` | `mitIDallgemeineDaten` | `false` |

`ContactAssignmentMode`: `PreferDirect` (`DIREKTBEVORZUGT`), `Direct` (`DIREKT`), `All` (`ALLE`), `None` (`NEIN`).

### What you get

```php
$description->status;               // ServiceStatus::Live
$description->summary;              // short description (HTML)
$description->description;          // long description (HTML)
$description->prerequisites;
$description->procedure;
$description->deadlines;
$description->processingTime;
$description->fees;
$description->notes;

foreach ($description->requiredDocuments as $document) {
    $document->title;
    $document->service?->url;       // when the document is obtained via another service
}

foreach ($description->forms as $form) {
    $form->title;
    $form->url;
    $form->writtenFormRequirement?->requiresSignature();
}

$description->onlineProcedures;     // list<OnlineProcedure>
$description->legalBases;           // list<LegalBasis>
$description->legalRemedy;          // ?LegalRemedy
$description->links;                // list<Link>
$description->relatedServices;      // list<RelatedService>

foreach ($description->responsibilities as $responsibility) {
    $responsibility->name;          // "Kreisverwaltungsreferat München"
    $responsibility->portalUrl;
    foreach ($responsibility->buildings as $building) {
        $building->streetAddress?->format();
        $building->phone;           // plain string here
        $building->contactIds;
    }
}
```

See [data model](data-model.md#servicedescription) for every field.

## `list()`

`GET leistungsbeschreibungen`

All service descriptions, **without** responsibilities. This is a very large response, so consider enabling [caching](caching.md).

```php
BayernPortal::serviceDescriptions()->list(
    municipalityKey: '09162000',
    withRegionalSupplements: false,
    withInternalIds: false,
);
```

For the descriptions of one agency, use [`agencies()->serviceDescriptions()`](agencies.md#servicedescriptions-full-descriptions-of-an-agencys-services).
