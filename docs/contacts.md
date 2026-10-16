# Contacts

Contact persons (*Ansprechpartner*) of authorities.

```php
$contacts = BayernPortal::contacts();   // Rathauslotse\BayernPortal\Resources\ContactResource
```

The API only paginates the contact list in its detailed form (`full=true`). That is why there are three list methods:

| Method | Request | Returns |
|---|---|---|
| `list()` | `GET ansprechpartner?full=false` | `Collection<int, Contact>`, all contacts in short form, **unpaginated** (large) |
| `paginate(int $page = 0, int $size = 100)` | `GET ansprechpartner?full=true&page=&size=` | `Page<Contact>` |
| `all(int $pageSize = 100)` | same as `paginate()`, page by page | `LazyCollection<int, Contact>` |

```php
foreach (BayernPortal::contacts()->all() as $contact) {
    $contact->fullName();
    $contact->authorityName;
    $contact->email;
}
```

## `find()`

`GET ansprechpartner/{ansprechpartner-id}`

Returns the contact with logo, communication data, consultation hours and directly assigned services.

```php
$contact = BayernPortal::contacts()->find(9001);

$contact->phone?->format();
$contact->mobile?->format();
$contact->consultationHours?->byAppointment;
$contact->services;                       // list<ServiceReference>
```

## `services()`

`GET ansprechpartner/{ansprechpartner-id}/leistungen`

```php
BayernPortal::contacts()->services(9001, onlyDirectAssignments: true);   // Collection<int, ServiceReference>
```

| Parameter | REST name | Default |
|---|---|---|
| `?bool $onlyDirectAssignments` | `nurDirekteLeistungszuordnungen` | `null` (API default) |
