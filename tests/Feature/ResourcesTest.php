<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Data\Officer;
use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Sleep;

beforeEach(fn () => Sleep::fake());

it('searches by name for typeahead', function (): void {
    Http::fake(['*/search/companies*' => Http::response(apiFixture('search'))]);

    $hits = CompaniesHouse::search('example', limit: 10);

    expect($hits)->toHaveCount(2)
        ->and($hits->first())->toBeInstanceOf(SearchHit::class)
        ->and($hits->first()?->number)->toBe('04567890');

    Http::assertSent(fn (Request $request): bool => $request['q'] === 'example' && $request['items_per_page'] === 10);
});

it('does not call the API for a blank search', function (): void {
    Http::fake();

    expect(CompaniesHouse::search('  '))->toBeEmpty();
    Http::assertNothingSent();
});

it('builds an advanced search query', function (): void {
    Http::fake(['*/advanced-search/companies*' => Http::response(apiFixture('advanced-search'))]);

    $hits = CompaniesHouse::advancedSearch()
        ->status('active')
        ->sic(['43210', '43220'])
        ->location('Cardiff')
        ->incorporatedAfter('2024-01-01')
        ->get(50);

    expect($hits)->toHaveCount(2);

    Http::assertSent(fn (Request $request): bool => $request['company_status'] === 'active'
        && $request['sic_codes'] === '43210,43220'
        && $request['location'] === 'Cardiff'
        && $request['incorporated_from'] === '2024-01-01'
        && $request['size'] === 50
        && $request['start_index'] === 0);
});

it('pages advanced search lazily, stopping at the reported hit count', function (): void {
    $item = apiFixture('advanced-search')['items'][0];

    Http::fake(['*/advanced-search/companies*' => Http::sequence()
        ->push(['hits' => 3, 'items' => [$item, $item]])
        ->push(['hits' => 3, 'items' => [$item]])]);

    $cursor = CompaniesHouse::advancedSearch()->status('active')->cursor(pageSize: 2);

    expect($cursor)->toBeInstanceOf(LazyCollection::class);
    Http::assertNothingSent();

    expect($cursor->count())->toBe(3);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request['start_index'] === 2);
});

it('treats an advanced search 404 as no results', function (): void {
    Http::fake(['*' => Http::response('', 404)]);

    expect(CompaniesHouse::advancedSearch()->sic('01110')->location('Nowhere')->get())->toBeEmpty();
});

it('lists active officers', function (): void {
    Http::fake(['*/company/04567890/officers*' => Http::response(apiFixture('officers'))]);

    $active = CompaniesHouse::officers('04567890')->active();

    expect($active)->toHaveCount(1)
        ->and($active->first())->toBeInstanceOf(Officer::class)
        ->and($active->first()?->name)->toBe('EXAMPLE, Jane Officer');
});

it('returns the latest filings', function (): void {
    Http::fake(['*/company/04567890/filing-history*' => Http::response(apiFixture('filing-history'))]);

    $filings = CompaniesHouse::filings('04567890')->latest(2);

    expect($filings)->toHaveCount(2)
        ->and($filings->first()?->type)->toBe('CS01');
    Http::assertSent(fn (Request $request): bool => $request['items_per_page'] === 2);
});

it('reads insolvency, charges and PSCs', function (): void {
    Http::fake([
        '*/company/05551234/insolvency' => Http::response(apiFixture('insolvency')),
        '*/company/05551234/charges*' => Http::response(apiFixture('charges')),
        '*/company/04567890/persons-with-significant-control*' => Http::response(apiFixture('psc')),
        '*/company/04567890/insolvency' => Http::response('', 404),
    ]);

    expect(CompaniesHouse::insolvency('05551234')->cases())->toHaveCount(1)
        ->and(CompaniesHouse::insolvency('04567890')->cases())->toBeEmpty()
        ->and(CompaniesHouse::charges('05551234')->outstanding())->toHaveCount(1)
        ->and(CompaniesHouse::psc('04567890')->active()->first()?->name)->toBe('Ms Jane Officer EXAMPLE');
});

it('reads the registered office on its own', function (): void {
    Http::fake(['*/registered-office-address' => Http::response(apiFixture('company-active')['registered_office_address'])]);

    expect(CompaniesHouse::companies()->registeredOffice('04567890')?->postcode)->toBe('CF10 1AA');
});

it('tryFind never throws, and says why it failed', function (): void {
    Http::fake([
        '*/company/04567890' => Http::response(apiFixture('company-active')),
        '*/company/00000000' => Http::response('', 404),
        '*/company/07654321' => Http::response('', 503),
    ]);

    $found = CompaniesHouse::tryFind('04567890');
    $missing = CompaniesHouse::tryFind('00000000');
    $down = CompaniesHouse::tryFind('07654321');
    $malformed = CompaniesHouse::tryFind('nonsense');

    expect($found->found())->toBeTrue()
        ->and($found->company?->name)->toBe('EXAMPLE TRAINING LTD')
        ->and($missing->notFound())->toBeTrue()
        ->and($down->failed())->toBeTrue()
        ->and($down->error)->toBeInstanceOf(Unavailable::class)
        ->and($down->reason())->toContain('unavailable')
        ->and($malformed->failed())->toBeTrue();
});
