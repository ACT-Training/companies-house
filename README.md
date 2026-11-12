# Companies House for Laravel

`act-training/companies-house` is a headless Laravel package that gives ACT apps:

- a typed client for the Companies House public data API (phase 1)
- a local mirror of live Welsh companies from the monthly bulk snapshot (phase 2)
- a way to link an app's own employer records to a company number (phase 3)
- a scheduled refresh that raises events when a linked company changes (phase 3)

> **Status:** phase 1 (the client) is built. The mirror (phase 2) and record linking (phase 3) are not.

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

## Usage

```php
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;

// Look up by number. Null when there is no such company; cached for 24 hours.
$company = CompaniesHouse::find('04567890');
$company->name;                          // "EXAMPLE TRAINING LTD"
$company->status;                        // CompanyStatus::Active
$company->registeredOffice->postcode;    // "CF10 1AA"
$company->sicCodes->pluck('code');       // ['85590']

// In UI code, never let a Companies House outage become a 500.
$result = CompaniesHouse::tryFind($number);
$result->found();      // a company came back
$result->notFound();   // Companies House has no such company
$result->failed();     // could not ask: $result->reason() says why

// Name search for typeahead: first page only, cached for 1 hour.
CompaniesHouse::search('acme engineering', limit: 10);

// Advanced search, paged lazily up to Companies House's 5,000 cap.
CompaniesHouse::advancedSearch()
    ->status('active')
    ->sic(['43210', '43220'])
    ->location('Cardiff')
    ->incorporatedAfter('2024-01-01')
    ->cursor();

// Related data, fetched on demand. Officer and PSC data is personal data:
// it is cached for an hour at most and must not be stored.
CompaniesHouse::officers('04567890')->active();
CompaniesHouse::filings('04567890')->latest(10);
CompaniesHouse::insolvency('05551234')->cases();
CompaniesHouse::charges('05551234')->outstanding();
CompaniesHouse::psc('04567890')->active();
```

Inject `ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient` instead of the facade if you prefer.

### Errors

`find()` returns `null` for a 404. Anything else throws a `CompaniesHouseException` with a `retryable()` flag:

| Exception        | When                                                 | Retryable |
| ---------------- | ---------------------------------------------------- | --------- |
| `Unauthorised`   | No key configured, or Companies House rejected it    | No        |
| `InvalidRequest` | A malformed company number, or another 4xx response  | No        |
| `RateLimited`    | Out of budget; `retryAfter()` gives seconds to wait  | Yes       |
| `Unavailable`    | Timeout, connection failure or a 5xx after one retry | Yes       |

### Rate limit

Companies House allows 600 requests per 5 minutes per key. The package shares a budget across every process through
the cache: 500 by default (`COMPANIES_HOUSE_RATE_CEILING`). It also reads Companies House's own rate limit headers, so
calls from other servers using the same key are counted.

Web requests may use the whole budget. Console processes (queue workers, scheduled commands) stop at the job budget,
300 by default (`COMPANIES_HOUSE_JOB_BUDGET`), so people searching are never starved by bulk work. Use
`CompaniesHouse::background()` or `->interactive()` to choose explicitly. In a queued job, release on a rate limit:

```php
try {
    $company = CompaniesHouse::background()->find($number);
} catch (RateLimited $e) {
    return $this->release($e->retryAfter());
}
```

### Commands

| Command                                  | Purpose                                                                                                               |
| ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `companies-house:doctor`                 | Checks the key, reachability and remaining rate budget                                                                |
| `companies-house:lookup {number} --json` | Prints a profile as a table, or JSON                                                                                  |
| `companies-house:search {term}`          | Name search; add `--advanced` with `--status`, `--sic`, `--location`, `--incorporated-after`, `--incorporated-before` |

### Testing your app

```php
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;

$fake = CompaniesHouse::fake([
    Company::factory()->name('ACME TRAINING LTD')->postcode('CF10 1AA')->sic('85590')->make(),
    Company::factory()->number('01234567')->dissolved()->make(),
    $rawProfileArray, // e.g. a recorded fixture
]);

$fake->withOfficers('01234567', [...])->withFilings('01234567', [...]);
$fake->failFor('01234567', 'rate-limited');   // or 'unavailable', or an exception
$fake->failAll();                              // Companies House is down

$fake->assertLookedUp('01234567');
$fake->assertSearched('acme');
$fake->assertNothingSent();
```

The fake replaces the container binding, so injected clients are faked too. It answers name and advanced searches from
the seeded companies.

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
