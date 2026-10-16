# Contributing

## Setup

```bash
git clone git@github.com:rathauslotse/bayernportal-api.git
cd bayernportal-api
composer install
```

## Checks

All of these must pass before a commit; CI runs them on every push:

```bash
composer lint        # Pint (composer format fixes)
composer analyse     # Larastan, level max
composer test        # Pest, unit + feature
```

`composer test:coverage` enforces at least 90 % coverage and needs pcov or xdebug. For the live suite, see [docs/testing.md](docs/testing.md).

## Conventions

- **English identifiers only.** Classes, methods, properties, parameters and enum cases are English. German names appear only in HTTP paths, query parameters and payload mapping. Add every new translation to [docs/glossary.md](docs/glossary.md).
- DTOs are `readonly`, are built through `fromPayload()` / `fromArray()`, and never throw on missing data.
- Every public method has PHPDoc that names the REST path and parameters.
- New endpoints need a fixture in `resources/fixtures`, a route in `Testing\BayernPortalFake`, a contract entry in `tests/Contract`, a feature test and a live test.
- Update `CHANGELOG.md` under *Unreleased*.

## Commit messages

We use [gitmoji](https://gitmoji.dev): start the subject with the emoji character, followed by a short imperative summary.

```
✨ Add agency endpoints
🐛 Fix Link header parsing for last page
```

Common ones: ✨ feature · 🐛 fix · ♻️ refactor · 📝 docs · 🔧 config · ⬆️ deps · ✅ tests · 👷 CI · 🔥 remove · 🚀 release. Split unrelated changes into separate commits.

## Releases

`main` is the development branch until 1.0.0. Releases are tagged `vX.Y.Z`. The `release` workflow then runs lint, static analysis and tests, notifies Packagist and creates a GitHub release.
