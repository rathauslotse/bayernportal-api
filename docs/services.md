# Services

A **service** (*Leistung*) is something citizens or businesses can apply for, e.g. "Personalausweis beantragen". This resource returns the short form. For full texts, fees, forms and responsible authorities, use [service descriptions](service-descriptions.md).

```php
$services = BayernPortal::services();   // Rathauslotse\BayernPortal\Resources\ServiceResource
```

## `list()`

`GET leistungen`

```php
BayernPortal::services()->list(municipalityKey: '09162000');   // Collection<int, Service>
```

| Parameter | REST name | |
|---|---|---|
| `?string $municipalityKey` | `gemeindekennziffer` | Localises URLs and names. Falls back to the [municipality scope](municipality-scope.md) |

## `find()`

`GET leistungen/{leistung-id}`

```php
$service = BayernPortal::services()->find(1010, '09162000');

$service->name;             // "Personalausweis beantragen"
$service->url;              // BayernPortal page
$service->synonyms;         // ["Perso", "Ausweis"]
$service->lifeSituations;   // list<LifeSituationReference>
$service->lastModifiedAt;   // CarbonImmutable
```
