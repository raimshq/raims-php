# Installation

The SDK requires 64-bit PHP 8.2 or later, Composer 2, and the `curl`, `json`, and
`hash` extensions. Check the environment with `php -v`, `php -m`, and
`composer check-platform-reqs` after installing dependencies.

## Install with Composer

In the directory of the application that will consume the SDK:

```text
composer require raims/raims-php:^0.1.0
```

The `raims/raims-php` package is distributed through
[Packagist](https://packagist.org/packages/raims/raims-php). The `^0.1.0`
constraint accepts updates in the `0.1` series. Keep the application's
`composer.lock` to reproduce installation of the same version.

Before updating, read the
[releases](https://github.com/raimshq/raims-php/releases) and test your integration
flows. To obtain a version allowed by the application's constraint:

```text
composer update raims/raims-php
```

## Follow the development branch

To contribute to the SDK or evaluate changes before a release, add the public
repository as a Composer VCS source.

If the project does not have a `composer.json` yet, create it with
`composer init` before running the commands below.

```text
composer config repositories.raims-php vcs https://github.com/raimshq/raims-php
composer require raims/raims-php:dev-main
```

`dev-main` follows `main` and may include changes made after the latest release.
The application's `composer.lock` pins the installed revision.

## Use a local checkout

For development with a local copy, use a Composer `path` repository. Adjust
`../Raims-PHP` to the checkout location:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../Raims-PHP",
            "options": {
                "symlink": true,
                "versions": {"raims/raims-php": "0.1.x-dev"}
            }
        }
    ],
    "require": {
        "raims/raims-php": "0.1.x-dev"
    }
}
```

Run `composer update raims/raims-php` in the consuming project.

## Load the client

After installing through any of these methods, use Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

The repository uses the package name `raims/raims-php` and the `Raims` namespace.
The SDK requires no third-party libraries in production.

## Credentials

Obtain an account key as described in the
[authentication guide](https://docs.raims.com/br/integracoes/autenticacao).
Keep it in an environment variable or the application's secret store. The API
continues to validate the key's permissions and restrictions.

Sandbox and Production keys use the same base URL. The SDK has no environment
option. Simulations require a Sandbox key; email and webhook deliveries can
still reach external destinations in that environment.

## Versions

The package version is independent of HTTP version `/v1` and the pinned
contract version. New versions published as repository releases become
available on Packagist through the SDK distribution process. The application
decides when to update its dependency.
