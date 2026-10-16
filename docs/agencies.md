# Agencies

An **agency** (*Dienststelle*) is an authority identified by a 7-digit **agency key** (*Dienststellenschlüssel*). Branch offices (*Außenstellen*) share the key of their main agency and have a `branchNumber` of 1…n. The main agency has 0.

```php
$agencies = BayernPortal::agencies();   // Rathauslotse\BayernPortal\Resources\AgencyResource
```

All methods throw `InvalidParameterException` before sending anything when an agency key is not exactly 7 digits or a municipality key is not exactly 8 digits.

## `list()`: one page of agencies

`GET dienststellen`

```php
$page = BayernPortal::agencies()->list(full: false, page: 0, size: 100);

$page->items;   // Collection<int, Agency>
$page->total;   // e.g. 2431
```

| Parameter | REST name | Default | |
|---|---|---|---|
| `bool $full` | `full` | `false` | All details instead of the short form |
| `int $page` | `page` | `0` | Zero-based page number |
| `int $size` | `size` | `100` | 1–100 |

Returns a [`Page<Agency>`](pagination.md).

## `all()`: every agency, lazily

```php
BayernPortal::agencies()->all(full: false, pageSize: 100)
    ->filter(fn (Agency $agency) => $agency->type === 'Gemeinde')
    ->each(fn (Agency $agency) => /* … */);
```

Returns a `LazyCollection<int, Agency>`. Pages are only requested while you iterate.

## `find()`: one agency

`GET dienststellen/{dienststellenschluessel}`

```php
$agency = BayernPortal::agencies()->find('0916200');

$agency->name;            // "Landeshauptstadt München"
$agency->agencyKey;       // "0916200"
$agency->isBranchOffice();
$agency->fullName();      // name + " – " + nameSuffix
```

Returns an [`Agency`](data-model.md#agency). Throws `NotFoundException` when the API returns nothing.

## `services()`: services of an agency

`GET dienststellen/{dienststellenschluessel}/leistungen`

```php
BayernPortal::agencies()->services('0916200', municipalityKey: '09162000');
```

| Parameter | REST name | |
|---|---|---|
| `string $agencyKey` | path | 7 digits |
| `?string $municipalityKey` | `gemeindekennziffer` | 8 digits. Falls back to the [municipality scope](municipality-scope.md) |

Returns `Collection<int, Service>`.

## `forms()` / `formsByService()`: forms of an agency

`GET dienststellen/{dienststellenschluessel}/formulare`

The API returns either a list of forms (each with its services) or a list of services (each with its forms), depending on `gruppiertNachLeistungen`. The package offers one method per shape:

```php
// gruppiertNachLeistungen=false → Collection<int, Form>
foreach (BayernPortal::agencies()->forms('0916200') as $form) {
    $form->title;                      // "Antrag auf Ausstellung eines Personalausweises"
    $form->url;
    $form->writtenFormRequirement;     // WrittenFormRequirement enum
    $form->prefillable;
    $form->services;                   // list<ServiceReference>
}

// gruppiertNachLeistungen=true → Collection<int, ServiceWithForms>
foreach (BayernPortal::agencies()->formsByService('0916200', '09162000') as $group) {
    $group->service->name;
    $group->forms;                     // list<Form>
}
```

Both accept `?string $municipalityKey` (`gemeindekennziffer`).

## `onlineProcedures()` / `onlineProceduresByService()`: online procedures

`GET dienststellen/{dienststellenschluessel}/onlineverfahren`

Same pattern as forms:

```php
BayernPortal::agencies()->onlineProcedures('0916200');            // Collection<int, OnlineProcedure>
BayernPortal::agencies()->onlineProceduresByService('0916200');   // Collection<int, ServiceWithOnlineProcedures>
```

## `serviceDescriptions()`: full descriptions of an agency's services

`GET dienststellen/{dienststellenschluessel}/leistungsbeschreibungen`

```php
BayernPortal::agencies()->serviceDescriptions(
    '0916200',
    municipalityKey: '09162000',
    withRegionalSupplements: true,
    withResponsibilities: true,
    withInternalIds: false,
);
```

| Parameter | REST name | Default |
|---|---|---|
| `?string $municipalityKey` | `gemeindekennziffer` | scope |
| `bool $withRegionalSupplements` | `mitRegionalenErgaenzungen` | `false` |
| `bool $withResponsibilities` | `mitZustaendigkeiten` | `false` |
| `bool $withInternalIds` | `mitIDallgemeineDaten` | `false` |

Returns `Collection<int, ServiceDescription>`. See [Service descriptions](service-descriptions.md) for the fields.

## `lifeSituations()`: life situations of an agency

`GET dienststellen/{dienststellenschluessel}/lebenslagen`

```php
BayernPortal::agencies()->lifeSituations('0916200');   // Collection<int, LifeSituation>
```

If the agency does not exist or has no services, the API answers with HTTP 400. The package raises this as `RequestException`, with the German message available through `apiMessage()`.
