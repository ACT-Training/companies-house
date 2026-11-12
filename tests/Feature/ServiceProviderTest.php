<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Client;
use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;

it('merges the package config', function (): void {
    expect(config('companies-house.timeout'))->toBe(8)
        ->and(config('companies-house.rate_limit.ceiling'))->toBe(500)
        ->and(config('companies-house.rate_limit.job_budget'))->toBe(300)
        ->and(config('companies-house.mirror.countries'))->toBe(['W92000004'])
        ->and(config('companies-house.table_prefix'))->toBe('ch_');
});

it('binds the client contract as a singleton', function (): void {
    $client = app(CompaniesHouseClient::class);

    expect($client)->toBeInstanceOf(Client::class)
        ->and(app(CompaniesHouseClient::class))->toBe($client);
});

it('resolves the facade to the bound client', function (): void {
    expect(CompaniesHouse::getFacadeRoot())->toBe(app(CompaniesHouseClient::class));
});
