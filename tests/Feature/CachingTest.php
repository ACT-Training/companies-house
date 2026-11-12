<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\CompaniesHouseServiceProvider;
use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use ACTTraining\CompaniesHouse\Http\Gateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(fn () => Sleep::fake());

it('caches a company profile for a day', function (): void {
    Http::fake(['*' => Http::response(apiFixture('company-active'))]);

    CompaniesHouse::find('04567890');
    CompaniesHouse::find('04567890');
    Http::assertSentCount(1);

    $this->travel(25)->hours();
    CompaniesHouse::find('04567890');
    Http::assertSentCount(2);
});

it('caches a 404, because it is a definitive answer', function (): void {
    Http::fake(['*' => Http::response('', 404)]);

    expect(CompaniesHouse::find('00000000'))->toBeNull()
        ->and(CompaniesHouse::find('00000000'))->toBeNull();
    Http::assertSentCount(1);
});

it('never caches a failure', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push('', 503)->push('', 503)
        ->push(apiFixture('company-active'))]);

    expect(fn () => CompaniesHouse::find('04567890'))->toThrow(Unavailable::class)
        ->and(CompaniesHouse::find('04567890')?->name)->toBe('EXAMPLE TRAINING LTD');
});

it('caches searches for an hour', function (): void {
    Http::fake(['*' => Http::response(apiFixture('search'))]);

    CompaniesHouse::search('example');
    CompaniesHouse::search('example');
    Http::assertSentCount(1);

    $this->travel(61)->minutes();
    CompaniesHouse::search('example');
    Http::assertSentCount(2);
});

it('never caches officer data for more than an hour, whatever the config says', function (): void {
    config()->set('companies-house.cache.ttl.officers', 86400);
    config()->set('companies-house.cache.ttl.psc', 86400);
    app()->forgetInstance(Gateway::class);
    app()->forgetInstance(CompaniesHouseClient::class);
    CompaniesHouse::clearResolvedInstances();
    Http::fake(['*' => Http::response(apiFixture('officers'))]);

    CompaniesHouse::officers('04567890')->active();
    $this->travel(CompaniesHouseServiceProvider::PERSONAL_DATA_MAX_TTL + 60)->seconds();
    CompaniesHouse::officers('04567890')->active();

    Http::assertSentCount(2);
});
