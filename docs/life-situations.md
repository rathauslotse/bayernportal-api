# Life situations

**Life situations** (*Lebenslagen*) group services by situations in life, e.g. "Umzug" (moving) or "Geburt" (birth). They form a two-level tree:

- top-level categories (no parent),
- sub-categories, which point to their parent through `parentId`. Services are only assigned to sub-categories.

Each life situation belongs to a target group (`LifeSituationCategory`): `Citizen` (`buerger`), `Business` (`unternehmer`) or `Administration` (`verwaltung`).

```php
$lifeSituations = BayernPortal::lifeSituations();   // Rathauslotse\BayernPortal\Resources\LifeSituationResource
```

## `list()`

`GET lebenslagen`

```php
BayernPortal::lifeSituations()->list(onlyWithServices: true);   // Collection<int, LifeSituation>, flat
```

| Parameter | REST name | Default |
|---|---|---|
| `?bool $onlyWithServices` | `leistungenZugeordnet` | `null` (API default) |

## `tree()`

Same request as `list()`, returned as a tree:

```php
foreach (BayernPortal::lifeSituations()->tree() as $node) {
    echo $node->lifeSituation->name, PHP_EOL;          // "Persönliche Dokumente"

    foreach ($node->children as $child) {
        echo ' - ', $child->name, PHP_EOL;              // "Umzug"
    }
}
```

Returns `Collection<int, LifeSituationNode>`. Sub-categories whose parent is not in the response are returned as top-level nodes. You can build a tree from any collection with `LifeSituationResource::buildTree($collection)`.

## `find()`

`GET lebenslagen/{lebenslage-id}`

```php
$moving = BayernPortal::lifeSituations()->find('10000300');

$moving->name;           // "Umzug"
$moving->category;       // LifeSituationCategory::Citizen
$moving->parentId;       // "10000000"
$moving->services;       // list<ServiceReference>
$moving->synonyms;
```

Life situation IDs are numeric strings.

For the life situations of one agency, see [`agencies()->lifeSituations()`](agencies.md#lifesituations-life-situations-of-an-agency).
