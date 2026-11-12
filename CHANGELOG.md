# Changelog

All notable changes to this package are recorded here. It follows [Semantic Versioning](https://semver.org).

## Unreleased

### Phase 1: the client

- `find()`, `tryFind()`, `search()` and `advancedSearch()`, plus officers, filings, insolvency, charges and PSC
  resources, mapped to readonly data objects that keep the raw JSON.
- A shared, cache-backed rate limiter that reads Companies House's headers, with a separate budget for background work.
- One retry on connection failures and server errors; back-off until the reset time on a 429.
- Caching of definitive answers only: profiles for 24 hours, searches for 1 hour, officer and PSC data for 1 hour at most.
- `CompaniesHouse::fake()` and `Company::factory()` for host app tests.
- `companies-house:doctor`, `companies-house:lookup` and `companies-house:search` commands.
- The Companies House condensed SIC 2007 list, bundled for descriptions.

### Scaffold

- MIT licence; public repository listed on Packagist.
- Package skeleton: service provider, config, client contract, facade and exception hierarchy.
- Tooling: Pest, PHPStan level 8, Duster (Pint with People's ruleset), Prettier, Husky, lint-staged and gitleaks.
