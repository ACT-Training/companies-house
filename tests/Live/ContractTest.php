<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;

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
    app()->forgetInstance(CompaniesHouseClient::class);
});

it('authenticates against the live API', function (): void {
    $response = app(CompaniesHouseClient::class)->request()->get('/search/companies', ['q' => 'ACT', 'items_per_page' => 1]);

    expect($response->status())->toBe(200)
        ->and($response->header('X-Ratelimit-Limit'))->not->toBe('');
});
