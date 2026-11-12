# companies-house

Headless Laravel package, `act-training/companies-house`, namespace `ACTTraining\CompaniesHouse`.

The spec is the Claude doc at https://claude.ai/artifact/UPBGVULGqjnJ9WzEyfwn72. Read it before building a phase.
Phase 1 (client) is approved. Phases 2 (mirror) and 3 (linking) wait for the build/buy decision; don't start them
without being asked.

## Layout

- `src/Contracts/CompaniesHouseClient.php`: the only surface host apps depend on, with the facade.
- `src/Client.php`: transport. Host-facing code must not reference it directly (an arch test enforces this).
- `src/Resources/`: one class per endpoint group (Companies, Search, Officers, Filings, Insolvency, Charges, Psc).
- `src/Data/`: readonly DTOs (Company, Address, SicCode, Officer, FilingItem, SearchHit), each keeping `raw` JSON.
- `src/Exceptions/`: everything extends `CompaniesHouseException` and implements `retryable()`. A 404 is `null`,
  not an exception.
- `src/Testing/`: the `CompaniesHouse::fake()` implementation.
- `src/Commands/`: `companies-house:*` artisan commands.
- `tests/Fixtures/`: recorded API responses. Redact officer and PSC personal data.

## Rules

- The API key must never reach logs, exception messages or command output (doctor shows the last four characters).
- Officer and PSC data: fetch on demand, cache for an hour at most, never store, never log.
- Never cache failures, only found or 404.
- `declare(strict_types=1);` in every PHP file.

## Checks (all must pass before a commit)

```bash
composer check            # duster lint, phpstan (level 8), pest
npx prettier --check .
```

The pre-commit hook runs `duster fix`, Prettier and gitleaks on staged files.
