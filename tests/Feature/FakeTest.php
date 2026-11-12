<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(fn () => Http::preventStrayRequests());

it('answers from seeded fixtures and factories without touching the network', function (): void {
    $fake = CompaniesHouse::fake([
        apiFixture('company-active'),
        Company::factory()->number('01234567')->name('ACME TRAINING LTD')->dissolved()->make(),
    ]);

    expect(CompaniesHouse::find('04567890')?->name)->toBe('EXAMPLE TRAINING LTD')
        ->and(CompaniesHouse::find('01234567')?->status)->toBe(CompanyStatus::Dissolved)
        ->and(CompaniesHouse::find('09999999'))->toBeNull();

    $fake->assertLookedUp('04567890');
    $fake->assertLookedUp('4567890', times: 1);
    $fake->assertNotLookedUp('07654321');
    Http::assertNothingSent();
});

it('is what the container hands out, so injected clients are faked too', function (): void {
    $fake = CompaniesHouse::fake();

    expect(app(CompaniesHouseClient::class))->toBe($fake);
});

it('searches seeded companies by name', function (): void {
    $fake = CompaniesHouse::fake([
        Company::factory()->name('CARDIFF PLUMBING LTD')->make(),
        Company::factory()->name('CARDIFF PLUMBING SERVICES LTD')->make(),
        Company::factory()->name('SWANSEA BAKERY LTD')->make(),
    ]);

    expect(CompaniesHouse::search('cardiff plumbing'))->toHaveCount(2);
    $fake->assertSearched('cardiff plumbing');
});

it('filters seeded companies with advanced search', function (): void {
    CompaniesHouse::fake([
        Company::factory()->sic('43210')->locality('Cardiff')->make(),
        Company::factory()->sic('43210')->locality('Newport')->make(),
        Company::factory()->sic('85590')->locality('Cardiff')->make(),
        Company::factory()->sic('43210')->locality('Cardiff')->dissolved()->make(),
    ]);

    $hits = CompaniesHouse::advancedSearch()->status('active')->sic('43210')->location('Cardiff')->get();

    expect($hits)->toHaveCount(1);
});

it('returns seeded officers and filings', function (): void {
    CompaniesHouse::fake([apiFixture('company-active')])
        ->withOfficers('04567890', apiFixture('officers')['items'])
        ->withFilings('04567890', apiFixture('filing-history')['items']);

    expect(CompaniesHouse::officers('04567890')->active())->toHaveCount(1)
        ->and(CompaniesHouse::filings('04567890')->latest(2))->toHaveCount(2)
        ->and(CompaniesHouse::charges('04567890')->all()->all())->toBe([]);
});

it('simulates failures for one company or for everything', function (): void {
    $fake = CompaniesHouse::fake([apiFixture('company-active'), apiFixture('company-dissolved')])
        ->failFor('04567890', 'rate-limited');

    expect(fn () => CompaniesHouse::find('04567890'))->toThrow(RateLimited::class)
        ->and(CompaniesHouse::find('07654321'))->not->toBeNull();

    $fake->failAll();

    expect(fn () => CompaniesHouse::find('07654321'))->toThrow(Unavailable::class)
        ->and(CompaniesHouse::tryFind('07654321')->failed())->toBeTrue();
});

it('asserts nothing was sent', function (): void {
    $fake = CompaniesHouse::fake();

    $fake->assertNothingSent();

    CompaniesHouse::search('anything');

    expect(fn () => $fake->assertNothingSent())->toThrow(AssertionFailedError::class);
});

it('fails assertLookedUp when the company was not looked up', function (): void {
    $fake = CompaniesHouse::fake();

    expect(fn () => $fake->assertLookedUp('04567890'))->toThrow(AssertionFailedError::class);
});

it('keeps recording when switched to the background budget', function (): void {
    $fake = CompaniesHouse::fake([apiFixture('company-active')]);

    CompaniesHouse::background()->find('04567890');

    $fake->assertLookedUp('04567890');
});
