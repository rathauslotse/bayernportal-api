# Installation

## Requirements

| | Version |
|---|---|
| PHP | 8.3, 8.4, 8.5 |
| Laravel | 12, 13 |

Laravel 11 is not supported. It is end of life, and Composer blocks all of its releases because of open security advisories.

## Install the package

```bash
composer require rathauslotse/bayernportal-api
```

Laravel's package discovery registers:

- `Rathauslotse\BayernPortal\BayernPortalServiceProvider`
- the facade alias `BayernPortal` → `Rathauslotse\BayernPortal\Facades\BayernPortal`

## Add credentials

```dotenv
BAYERNPORTAL_USERNAME=your-user
BAYERNPORTAL_PASSWORD=your-password
```

See [Authentication](authentication.md) for how to obtain them, and [Configuration](configuration.md) for all options.

## Publish the config (optional)

```bash
php artisan vendor:publish --tag=bayernportal-config
```

This copies `config/bayernportal.php` into your application. Every option can also be set through environment variables, so publishing is only needed if you want to change the defaults in code.

## Use it

```php
use Rathauslotse\BayernPortal\Facades\BayernPortal;

$agency = BayernPortal::agencies()->find('0916200');
```

You can also use dependency injection:

```php
use Rathauslotse\BayernPortal\BayernPortal;

final class SyncServices
{
    public function __construct(private BayernPortal $bayernPortal) {}

    public function __invoke(): void
    {
        foreach ($this->bayernPortal->forMunicipality('09162000')->services()->list() as $service) {
            // …
        }
    }
}
```

`BayernPortal` is bound as a singleton and is immutable. Methods such as `forMunicipality()` and `withoutCache()` return new instances and never change the shared one.
