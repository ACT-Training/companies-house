<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(fn () => Sleep::fake());

it('prints a company profile', function (): void {
    CompaniesHouse::fake([apiFixture('company-active')]);

    $this->artisan('companies-house:lookup', ['number' => '04567890'])
        ->expectsOutputToContain('EXAMPLE TRAINING LTD')
        ->expectsOutputToContain('85590 Other education n.e.c.')
        ->assertSuccessful();
});

it('prints a company profile as JSON', function (): void {
    CompaniesHouse::fake([apiFixture('company-active')]);

    $this->artisan('companies-house:lookup', ['number' => '04567890', '--json' => true])
        ->expectsOutputToContain('"number": "04567890"')
        ->assertSuccessful();
});

it('says when there is no such company', function (): void {
    CompaniesHouse::fake();

    $this->artisan('companies-house:lookup', ['number' => '09999999'])
        ->expectsOutputToContain('no company 09999999')
        ->assertFailed();
});

it('searches by name', function (): void {
    CompaniesHouse::fake([apiFixture('company-active'), apiFixture('company-dissolved')]);

    $this->artisan('companies-house:search', ['term' => 'example'])
        ->expectsOutputToContain('EXAMPLE TRAINING LTD')
        ->assertSuccessful();
});

it('runs an advanced search from the shell', function (): void {
    $fake = CompaniesHouse::fake([apiFixture('company-active'), apiFixture('company-liquidation')]);

    $this->artisan('companies-house:search', ['--advanced' => true, '--sic' => ['43210'], '--location' => 'Bridgend'])
        ->expectsOutputToContain('BRIDGEND BUILDERS LIMITED')
        ->assertSuccessful();

    $fake->assertSearched();
});

it('doctor passes with a working key, showing only its last four characters', function (): void {
    config()->set('companies-house.api_key', 'abcdefgh-1234'); // gitleaks:allow (fake key for the masking test)
    app()->forgetInstance(Transport::class);
    Http::fake(['*' => Http::response(apiFixture('search'), 200, rateHeaders(remain: 590))]);

    $this->artisan('companies-house:doctor')
        ->expectsOutputToContain('set, ending 1234')
        ->doesntExpectOutputToContain('abcdefgh')
        ->expectsOutputToContain('Companies House answered')
        ->assertSuccessful();
});

it('doctor fails without a key', function (): void {
    config()->set('companies-house.api_key', null);
    Http::fake();

    $this->artisan('companies-house:doctor')
        ->expectsOutputToContain('missing')
        ->assertFailed();

    Http::assertNothingSent();
});

it('doctor fails when Companies House rejects the key', function (): void {
    Http::fake(['*' => Http::response('', 401)]);

    $this->artisan('companies-house:doctor')
        ->expectsOutputToContain('rejected the API key')
        ->assertFailed();
});
