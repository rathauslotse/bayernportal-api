# Municipality scope

Many endpoints accept an 8-digit **official municipality key** (*Amtlicher Gemeindeschlüssel*, API parameter `gemeindekennziffer`), e.g. `09162000` for Munich. It localises the response: postal codes and places, the responsible authorities and the regional supplements.

Rathauslotse usually works on one municipality at a time, so the package lets you scope a client:

```php
$freising = BayernPortal::forMunicipality('09178124');

$freising->services()->list();                    // ?gemeindekennziffer=09178124
$freising->serviceDescriptions()->find(1010);     // ?gemeindekennziffer=09178124&…
$freising->agencies()->formsByService('0917812'); // ?gemeindekennziffer=09178124&…
```

## Rules

- `forMunicipality()` returns a **new** client. The shared singleton is not changed.
- An explicit `municipalityKey` argument on a call **wins** over the scope:
  ```php
  $freising->services()->list('09162000');   // Munich, not Freising
  ```
- Endpoints without localisation (life situations, authorities, contacts, …) never receive the parameter.
- `forMunicipality(null)` removes the scope.
- Invalid keys (anything other than 8 digits) throw `InvalidParameterException` immediately.

## Default from config

```dotenv
BAYERNPORTAL_MUNICIPALITY_KEY=09162000
```

With this set, `BayernPortal::services()->list()` is already scoped to Munich.

## Endpoints that use the municipality key

| Method | |
|---|---|
| `agencies()->services()` | |
| `agencies()->forms()` / `formsByService()` | |
| `agencies()->onlineProcedures()` / `onlineProceduresByService()` | |
| `agencies()->serviceDescriptions()` | |
| `services()->list()` / `find()` | |
| `serviceDescriptions()->list()` | |
| `serviceDescriptions()->find()` | also needed to get `responsibilities` |

## Finding municipality keys

The key is the 8-digit *Amtlicher Gemeindeschlüssel* (AGS): state (2 digits, Bavaria = `09`), administrative district (1), district (2) and municipality (3). It is published by the statistical offices, e.g. the [Bayerisches Landesamt für Statistik](https://www.statistik.bayern.de/).
