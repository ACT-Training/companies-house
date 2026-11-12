<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\InvalidRequest;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Exceptions\Unauthorised;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use ACTTraining\CompaniesHouse\Facades\CompaniesHouse;
use ACTTraining\CompaniesHouse\Http\RateLimiter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(fn () => Sleep::fake());

it('sends the key as the basic auth username with a blank password', function (): void {
    Http::fake(['*' => Http::response(apiFixture('company-active'), 200, rateHeaders())]);

    CompaniesHouse::find('04567890');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.company-information.service.gov.uk/company/04567890'
        && $request->hasHeader('Authorization', 'Basic ' . base64_encode('test-key:')));
});

it('refuses to send anything without a key', function (): void {
    config()->set('companies-house.api_key', null);
    Http::fake();

    expect(fn () => CompaniesHouse::find('04567890'))->toThrow(Unauthorised::class);
    Http::assertNothingSent();
});

it('normalises company numbers before asking', function (): void {
    Http::fake(['*' => Http::response(apiFixture('company-active'))]);

    CompaniesHouse::find(' 4567890 ');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/company/04567890'));
});

it('rejects a malformed company number without calling the API', function (): void {
    Http::fake();

    expect(fn () => CompaniesHouse::find('not a number'))->toThrow(InvalidRequest::class);
    Http::assertNothingSent();
});

it('returns null for a 404', function (): void {
    Http::fake(['*' => Http::response(['errors' => [['error' => 'company-profile-not-found']]], 404)]);

    expect(CompaniesHouse::find('00000000'))->toBeNull();
});

it('retries once on a server error', function (): void {
    Http::fake(['*' => Http::sequence()
        ->push('', 502)
        ->push(apiFixture('company-active'), 200)]);

    expect(CompaniesHouse::find('04567890')?->name)->toBe('EXAMPLE TRAINING LTD');
    Http::assertSentCount(2);
});

it('gives up after one retry and reports Companies House as unavailable', function (): void {
    Http::fake(['*' => Http::response('', 503)]);

    expect(fn () => CompaniesHouse::find('04567890'))->toThrow(Unavailable::class);
    Http::assertSentCount(2);
});

it('reports a connection failure as unavailable', function (): void {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

    expect(fn () => CompaniesHouse::find('04567890'))->toThrow(Unavailable::class);
});

it('treats a 401 as an unauthorised key, which is not retryable', function (): void {
    Http::fake(['*' => Http::response(['error' => 'Invalid Authorization'], 401)]);

    try {
        CompaniesHouse::find('04567890');
        $this->fail('Expected Unauthorised.');
    } catch (Unauthorised $exception) {
        expect($exception->retryable())->toBeFalse();
    }
});

it('backs off until the reset time after a 429', function (): void {
    $reset = time() + 120;
    Http::fake(['*' => Http::response('', 429, rateHeaders(0, 600, $reset))]);

    try {
        CompaniesHouse::find('04567890');
        $this->fail('Expected RateLimited.');
    } catch (RateLimited $exception) {
        expect($exception->retryable())->toBeTrue()
            ->and($exception->resetsAt?->getTimestamp())->toBe($reset);
    }

    // The next call fails at once, without reaching Companies House.
    expect(fn () => CompaniesHouse::find('05551234'))->toThrow(RateLimited::class);
    Http::assertSentCount(1);
});

it('never puts the API key in an exception message', function (int $status): void {
    config()->set('companies-house.api_key', 'super-secret-key-1234');
    Http::fake(['*' => Http::response('', $status)]);

    try {
        CompaniesHouse::find('04567890');
    } catch (Throwable $exception) {
        expect($exception->getMessage())->not->toContain('super-secret-key');
    }
})->with([400, 401, 429, 500]);

it('learns the remaining budget from the rate limit headers', function (): void {
    Http::fake(['*' => Http::response(apiFixture('company-active'), 200, rateHeaders(remain: 150))]);

    CompaniesHouse::find('04567890');

    expect(app(RateLimiter::class)->used())->toBe(450);
});

it('uses the interactive budget in tests and web requests, and can switch to background', function (): void {
    $client = app(CompaniesHouseClient::class);

    expect($client->priority())->toBe(Priority::Interactive)
        ->and($client->background()->priority())->toBe(Priority::Background)
        ->and($client->priority())->toBe(Priority::Interactive);
});
