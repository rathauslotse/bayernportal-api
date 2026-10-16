# Authorities, buildings and building contacts

An **authority** (*Behörde*) is any administrative unit: an agency, a branch office or an organisational unit. Authorities have **buildings** (*Gebäude*), and buildings have **contact persons** (*Ansprechpartner*).

```php
$authorities = BayernPortal::authorities();   // Rathauslotse\BayernPortal\Resources\AuthorityResource
```

IDs must be positive integers. Otherwise `InvalidParameterException` is thrown before any request is sent.

## `list()` / `all()`

`GET behoerden`

```php
$page = BayernPortal::authorities()->list(full: true, page: 0, size: 100);   // Page<Authority>
$all  = BayernPortal::authorities()->all(full: false, pageSize: 100);        // LazyCollection<int, Authority>
```

| Parameter | REST name | Default |
|---|---|---|
| `bool $full` | `full` | `false` |
| `int $page` | `page` | `0` |
| `int $size` / `$pageSize` | `size` | `100` |

## `find()`

`GET behoerden/{behoerde-id}`

```php
$authority = BayernPortal::authorities()->find(4711, withSecureContactForm: true);

$authority->buildingIds;               // [801, 802]
$authority->secureContactFormUrl;      // only with withSecureContactForm
```

| Parameter | REST name | Default |
|---|---|---|
| `bool $withSecureContactForm` | `sicheresKontaktformular` | `false` |

## `building()`

`GET behoerden/{behoerde-id}/gebaeude/{gebaeude-id}`

```php
use Rathauslotse\BayernPortal\Enums\Weekday;

$building = BayernPortal::authorities()->building(4711, 801);

$building->streetAddress?->format();   // "Ruppertstraße 19, 80337 München"
$building->postalAddress?->format();
$building->phone?->format();           // "+49 89 233-96000"
$building->phone?->toE164();           // "+498923396000" (for tel: links)
$building->openingHours?->byAppointment;
$building->openingHours?->specialHours;
foreach ($building->openingHours?->forDay(Weekday::Tuesday) ?? [] as $slot) {
    $slot->format();                   // "08:30–12:00, 14:00–18:00"
}
$building->contactIds;                 // [9001, 9002]
```

Returns a [`Building`](data-model.md#building).

## `buildingContact()`

`GET behoerden/{behoerde-id}/gebaeude/{gebaeude-id}/ansprechpartner/{ansprechpartner-id}`

```php
$contact = BayernPortal::authorities()->buildingContact(4711, 801, 9001);

$contact->fullName();                  // "Dr. Maria Huber"
$contact->consultationHours;
```

Returns a [`Contact`](data-model.md#contact). This endpoint does not include `authorityName`, `buildingName` or `services`.

## `contacts()`

`GET behoerden/{behoerde-id}/ansprechpartner`

```php
BayernPortal::authorities()->contacts(4711);   // Collection<int, Contact>
```

## `services()`

`GET behoerden/{behoerde-id}/leistungen`

```php
BayernPortal::authorities()->services(4711, onlyDirectAssignments: true);   // Collection<int, ServiceReference>
```

| Parameter | REST name | Default |
|---|---|---|
| `?bool $onlyDirectAssignments` | `nurDirekteLeistungszuordnungen` | `null` (not sent; API default applies) |

## `delegatedServices()`

`GET behoerden/{behoerde-id}/abgegebene-leistungen`

Services that a district government (*Bezirksregierung*) has delegated. Only the seven Bavarian district governments are allowed to call this. For everyone else the API answers with HTTP 400 "Sie haben keine Berechtigung …", raised as `RequestException`.

```php
BayernPortal::authorities()->delegatedServices($districtGovernmentId);   // Collection<int, ServiceReference>
```
