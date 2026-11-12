# Companies House for Laravel

`act-training/companies-house` is a headless Laravel package that gives ACT apps:

- a typed client for the Companies House public data API (phase 1)
- a local mirror of live Welsh companies from the monthly bulk snapshot (phase 2)
- a way to link an app's own employer records to a company number (phase 3)
- a scheduled refresh that raises events when a linked company changes (phase 3)

> **Status:** skeleton only. The service provider, config, contract, facade and exceptions exist; the resources,
> DTOs, rate limiter, cache, fake and commands are still to build.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require act-training/companies-house
php artisan vendor:publish --tag=companies-house-config
```

Set the key in the environment (Forge on servers; never commit it):

```dotenv
COMPANIES_HOUSE_API_KEY=
```

See `config/companies-house.php` for every option and its env key.

## Development

```bash
composer install
npm install          # Prettier, Husky and lint-staged; also installs the pre-commit hook
brew install gitleaks
```

| Command                | What it runs                                                     |
| ---------------------- | ---------------------------------------------------------------- |
| `composer test`        | Pest: Unit, Feature and Arch suites                              |
| `composer test:live`   | Pest Live suite against the real API; skips without a key        |
| `composer analyse`     | PHPStan level 8 via Larastan                                     |
| `composer lint`        | Duster lint: Pint (People's ruleset), PHP CS Fixer, TLint, PHPCS |
| `composer fix`         | Duster fix                                                       |
| `composer check`       | lint, analyse and test in one go                                 |
| `npm run format:check` | Prettier check of JSON, YAML, Markdown and JS                    |
| `npm run format`       | Prettier write                                                   |

The pre-commit hook blocks `.env` files, runs `duster fix` on staged PHP, Prettier on everything else, and scans the
staged changes for secrets with gitleaks.

### Live tests

Put a **dev** key in `COMPANIES_HOUSE_API_KEY` and run `composer test:live`. CI runs the same suite every Monday
using the `COMPANIES_HOUSE_API_KEY` repository secret.

### Fixtures

Recorded API responses go in `tests/Fixtures` as JSON. Prettier ignores that directory so they stay byte-for-byte as
recorded. Redact officer and PSC personal data before committing.

## CI

| Workflow              | Checks                                                              |
| --------------------- | ------------------------------------------------------------------- |
| `tests.yml`           | Pest on PHP 8.3–8.5 × Laravel 12–13, lowest and stable dependencies |
| `static-analysis.yml` | PHPStan                                                             |
| `lint.yml`            | Duster lint and Prettier check                                      |
| `gitleaks.yml`        | Secret scan of the history                                          |
| `live.yml`            | Weekly live contract tests                                          |

## Licence

MIT. See [LICENSE](LICENSE).

Company data comes from Companies House. Confirm its current reuse and attribution terms before go-live and add any
required attribution text here.
