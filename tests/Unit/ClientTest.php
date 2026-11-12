<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Exceptions\Unauthorised;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends the key as the basic auth username with a blank password', function (): void {
    Http::fake();

    app(CompaniesHouseClient::class)->request()->get('/company/04567890');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.company-information.service.gov.uk/company/04567890'
        && $request->hasHeader('Authorization', 'Basic ' . base64_encode('test-key:')));
});

it('refuses to build a request without a key', function (): void {
    config()->set('companies-house.api_key', null);
    app()->forgetInstance(CompaniesHouseClient::class);

    app(CompaniesHouseClient::class)->request();
})->throws(Unauthorised::class);
