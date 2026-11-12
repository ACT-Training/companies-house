<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Http\RateLimiter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

function limiter(int $ceiling = 5, int $jobBudget = 3): RateLimiter
{
    return new RateLimiter(Cache::store('array'), $ceiling, $jobBudget, 300);
}

it('lets interactive calls use the whole ceiling', function (): void {
    $limiter = limiter();

    foreach (range(1, 5) as $_) {
        $limiter->attempt(Priority::Interactive);
    }

    expect(fn () => $limiter->attempt(Priority::Interactive))->toThrow(RateLimited::class);
});

it('stops background calls at the job budget, leaving the rest for people', function (): void {
    $limiter = limiter();

    foreach (range(1, 3) as $_) {
        $limiter->attempt(Priority::Background);
    }

    expect(fn () => $limiter->attempt(Priority::Background))->toThrow(RateLimited::class);

    $limiter->attempt(Priority::Interactive);
    expect($limiter->remaining(Priority::Interactive))->toBe(1);
});

it('shares the budget between processes through the cache', function (): void {
    limiter()->attempt(Priority::Interactive);
    limiter()->attempt(Priority::Interactive);

    expect(limiter()->used())->toBe(2);
});

it('starts a fresh budget in the next window', function (): void {
    CarbonImmutable::setTestNow('2026-11-12 10:00:00');
    $limiter = limiter();

    foreach (range(1, 5) as $_) {
        $limiter->attempt(Priority::Interactive);
    }

    CarbonImmutable::setTestNow('2026-11-12 10:05:00');

    expect($limiter->remaining(Priority::Interactive))->toBe(5);
});

it('counts calls made elsewhere with the same key, from the headers', function (): void {
    $limiter = limiter(ceiling: 500, jobBudget: 300);

    $limiter->observe(600, 200, time() + 100);

    expect($limiter->used())->toBe(400)
        ->and($limiter->remaining(Priority::Interactive))->toBe(100)
        ->and($limiter->remaining(Priority::Background))->toBe(0);
});

it('blocks every call until the reset time after a 429', function (): void {
    $limiter = limiter();
    $limiter->blockUntil(CarbonImmutable::now()->addSeconds(30));

    expect(fn () => $limiter->attempt(Priority::Interactive))->toThrow(RateLimited::class)
        ->and($limiter->blockedUntil())->not->toBeNull();
});

it('tells a queued job how long to wait', function (): void {
    CarbonImmutable::setTestNow('2026-11-12 10:00:00');

    $exception = new RateLimited(CarbonImmutable::parse('2026-11-12 10:02:00'));

    expect($exception->retryAfter())->toBe(120);
});

it('reads counts stored as strings, as the Redis and database stores return them', function (): void {
    CarbonImmutable::setTestNow('2026-11-12 10:00:00');
    $window = intdiv(CarbonImmutable::now()->getTimestamp(), 300);
    $store = Cache::store('array');

    $store->put("companies-house:rate:window:{$window}", '4', 600);
    expect(limiter()->used())->toBe(4);

    $store->put('companies-house:rate:observed', '450', 600);
    expect(limiter(ceiling: 500)->used())->toBe(450);

    $store->put('companies-house:rate:blocked', (string) (time() + 60), 600);
    expect(fn () => limiter()->attempt(Priority::Interactive))->toThrow(RateLimited::class);
});
