<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Data\Charge;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Data\FilingItem;
use ACTTraining\CompaniesHouse\Data\InsolvencyCase;
use ACTTraining\CompaniesHouse\Data\Officer;
use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Data\SicCode;
use ACTTraining\CompaniesHouse\Data\SignificantControl;
use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;

it('maps a company profile', function (): void {
    $company = Company::fromArray(apiFixture('company-active'));

    expect($company->number)->toBe('04567890')
        ->and($company->name)->toBe('EXAMPLE TRAINING LTD')
        ->and($company->status)->toBe(CompanyStatus::Active)
        ->and($company->isActive())->toBeTrue()
        ->and($company->type)->toBe('ltd')
        ->and($company->jurisdiction)->toBe('england-wales')
        ->and($company->incorporatedOn?->toDateString())->toBe('2003-09-15')
        ->and($company->registeredOffice?->postcode)->toBe('CF10 1AA')
        ->and($company->registeredOffice?->oneLine())->toBe('Unit 4 1 Example Street, Cardiff, CF10 1AA')
        ->and($company->sicCodes->pluck('code')->all())->toBe(['85590', '85600'])
        ->and($company->sicCodes->first()?->description)->toBe('Other education n.e.c.')
        ->and($company->accountsNextDue?->toDateString())->toBe('2027-06-30')
        ->and($company->accountsOverdue)->toBeFalse()
        ->and($company->links['officers'])->toBe('/company/04567890/officers')
        ->and($company->raw)->toBe(apiFixture('company-active'));
});

it('maps dissolved, insolvent, renamed and Scottish companies', function (): void {
    $dissolved = Company::fromArray(apiFixture('company-dissolved'));
    $liquidation = Company::fromArray(apiFixture('company-liquidation'));
    $renamed = Company::fromArray(apiFixture('company-renamed'));
    $scottish = Company::fromArray(apiFixture('company-scottish'));

    expect($dissolved->isDissolved())->toBeTrue()
        ->and($dissolved->ceasedOn?->toDateString())->toBe('2024-11-19')
        ->and($liquidation->status->isInsolvent())->toBeTrue()
        ->and($liquidation->hasInsolvencyHistory)->toBeTrue()
        ->and($liquidation->hasCharges)->toBeTrue()
        ->and($renamed->previousNames->first()?->name)->toBe('OLD NAME SERVICES LTD')
        ->and($renamed->previousNames->first()?->ceasedOn?->toDateString())->toBe('2024-03-01')
        ->and($scottish->number)->toBe('SC123456')
        ->and($scottish->jurisdiction)->toBe('scotland');
});

it('survives missing and unexpected fields', function (): void {
    $company = Company::fromArray(['company_number' => '04567890', 'company_status' => 'something-new', 'sic_codes' => 'oops']);

    expect($company->status)->toBe(CompanyStatus::Unknown)
        ->and($company->name)->toBe('')
        ->and($company->registeredOffice)->toBeNull()
        ->and($company->sicCodes)->toBeEmpty()
        ->and($company->incorporatedOn)->toBeNull();
});

it('converts to an array for Livewire', function (): void {
    $array = Company::fromArray(apiFixture('company-active'))->toArray();

    expect($array['status'])->toBe('active')
        ->and($array['registered_office']['postcode'])->toBe('CF10 1AA')
        ->and($array['sic_codes'][0])->toBe(['code' => '85590', 'description' => 'Other education n.e.c.', 'section' => 'P'])
        ->and($array)->not->toHaveKey('raw');
});

it('maps search hits from both search endpoints', function (): void {
    $hit = SearchHit::fromSearch(apiFixture('search')['items'][0]);
    $advanced = SearchHit::fromAdvancedSearch(apiFixture('advanced-search')['items'][1]);

    expect($hit->name)->toBe('EXAMPLE TRAINING LTD')
        ->and($hit->addressSnippet)->toBe('Unit 4 1 Example Street, Cardiff, CF10 1AA')
        ->and($advanced->name)->toBe('BRIDGEND BUILDERS LIMITED')
        ->and($advanced->status)->toBe(CompanyStatus::Liquidation)
        ->and($advanced->sicCodes->pluck('code')->all())->toBe(['41201', '43210'])
        ->and($advanced->addressSnippet)->toBe('3 Market Way, Bridgend, CF31 1AA');
});

it('maps officers, filings, charges, insolvency and PSCs', function (): void {
    $officer = Officer::fromArray(apiFixture('officers')['items'][0]);
    $resigned = Officer::fromArray(apiFixture('officers')['items'][1]);
    $filing = FilingItem::fromArray(apiFixture('filing-history')['items'][0]);
    $charge = Charge::fromArray(apiFixture('charges')['items'][0]);
    $case = InsolvencyCase::fromArray(apiFixture('insolvency')['cases'][0]);
    $psc = SignificantControl::fromArray(apiFixture('psc')['items'][0]);

    expect($officer->role)->toBe('director')
        ->and($officer->birthYear)->toBe(1970)
        ->and($officer->isActive())->toBeTrue()
        ->and($resigned->isActive())->toBeFalse()
        ->and($filing->type)->toBe('CS01')
        ->and($filing->date?->toDateString())->toBe('2026-03-02')
        ->and($charge->isOutstanding())->toBeTrue()
        ->and($charge->personsEntitled)->toBe(['EXAMPLE BANK PLC'])
        ->and($case->type)->toBe('creditors-voluntary-liquidation')
        ->and($case->dates['wound-up-on']->toDateString())->toBe('2026-07-14')
        ->and($psc->naturesOfControl)->toBe(['ownership-of-shares-75-to-100-percent'])
        ->and($psc->isActive())->toBeTrue();
});

it('describes SIC codes from the bundled list', function (): void {
    expect(SicCode::fromCode('85590')->toArray())->toBe(['code' => '85590', 'description' => 'Other education n.e.c.', 'section' => 'P'])
        ->and(SicCode::fromCode('43210')->section)->toBe('F')
        ->and(SicCode::fromCode('99999')->description)->toBe('Dormant Company')
        ->and(SicCode::fromCode('99999')->section)->toBeNull()
        ->and(SicCode::fromCode('12345')->description)->toBeNull();
});

it('normalises company numbers', function (string $input, string $expected): void {
    expect(CompanyNumber::normalise($input))->toBe($expected);
})->with([
    ['04567890', '04567890'],
    ['4567890', '04567890'],
    ['sc123456', 'SC123456'],
    [' SC 123456 ', 'SC123456'],
]);

it('rejects malformed company numbers', function (string $input): void {
    expect(CompanyNumber::isValid($input))->toBeFalse();
})->with(['', 'ABC', '123456789', 'SC12345!', '../etc/pw']);
