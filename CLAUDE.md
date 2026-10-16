# Commit conventions

Use [gitmoji](https://gitmoji.dev) for every commit: start the subject with the emoji character (not the `:code:`), followed by a short imperative summary.

```
✨ Add agency endpoints
🐛 Fix Link header parsing for last page
```

Common ones: ✨ feature · 🐛 fix · ♻️ refactor · 📝 docs · 🔧 config · ⬆️ deps · ✅ tests · 👷 CI · 🏷️ types · 🔥 remove code/files · 🚀 release · 🎉 initial commit. Split unrelated changes into separate commits, each with its own fitting gitmoji.

# Project conventions

- Package `rathauslotse/bayernportal-api`, namespace `Rathauslotse\BayernPortal`. PHP ^8.3, Laravel 11–13.
- **No German identifiers.** Classes, methods, properties, parameters and enum cases are English. The German REST names appear only in the HTTP layer (paths, query parameters) and in payload mapping. `docs/glossary.md` is the single source of truth for German → English names; extend it whenever a new term is mapped.
- Development happens on `main` until 1.0.0. The first published version is **1.0.0**.
- Before committing, run `composer lint`, `composer analyse` and `composer test`. All must pass.
- Live tests live in `tests/Integration` (Pest group `integration`, `composer test:integration`). They skip automatically when `BAYERNPORTAL_USERNAME` / `BAYERNPORTAL_PASSWORD` are not set.
- CI (`.github/workflows`): `ci.yml` runs lint → analyse → tests on every branch push; `release.yml` runs the same chain on `v*` tags and then publishes to Packagist and creates a GitHub release.
- Work is tracked in Linear (team Product & Development, project Go-To-Market, milestone "M2 · Erste Demo", label `rathauslotse/bayernportal-api`).
