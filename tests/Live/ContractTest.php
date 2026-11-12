<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use ACTTraining\CompaniesHouse\Http\Gateway;

/*
 * Contract tests against the real Companies House API. They run weekly in CI
 * (and via `composer test:live`) so a change to a response shape is caught.
 * They skip when no key is set, so they never break a normal test run.
 */

beforeEach(function (): void {
    $key = getenv('COMPANIES_HOUSE_API_KEY');

    if (! is_string($key) || $key === '') {
        $this->markTestSkipped('COMPANIES_HOUSE_API_KEY is not set.');
    }

    config()->set('companies-house.api_key', $key);

    foreach ([Transport::class, Gateway::class, CompaniesHouseClient::class] as $binding) {
        app()->forgetInstance($binding);
    }

    CompaniesHouse::clearResolvedInstances();
});

it('finds companies by name and maps a full profile', function (): void {
    $hits = CompaniesHouse::search('training', limit: 5);

    expect($hits)->not->toBeEmpty();

    $company = CompaniesHouse::find($hits->first()->number);

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->number)->toBe($hits->first()->number)
        ->and($company->name)->not->toBe('')
        ->and($company->raw)->toHaveKeys(['company_number', 'company_name', 'company_status', 'date_of_creation']);
});

it('runs an advanced search', function (): void {
    $hits = CompaniesHouse::advancedSearch()->status('active')->sic('85590')->location('Cardiff')->get(5);

    expect($hits)->not->toBeEmpty()
        ->and($hits->first()->sicCodes->pluck('code'))->toContain('85590');
});

it('answers a missing company with null', function (): void {
    expect(CompaniesHouse::find('00000000'))->toBeNull();
});
