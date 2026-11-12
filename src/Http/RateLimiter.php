<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Http;

use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;

/**
 * Shares the API key's budget across every process on the server (web,
 * queue workers, scheduled commands) through the cache.
 *
 * Usage is the higher of our own count for the current window and what
 * Companies House last reported in X-Ratelimit-Remain, so calls made by
 * another server with the same key are accounted for.
 */
final class RateLimiter
{
    public function __construct(
        private readonly Repository $cache,
        private readonly int $ceiling,
        private readonly int $jobBudget,
        private readonly int $windowSeconds,
        private readonly string $prefix = 'companies-house',
    ) {}

    /**
     * Take one request from the budget, or throw if there is none left.
     *
     * @throws RateLimited
     */
    public function attempt(Priority $priority): void
    {
        $blockedUntil = $this->blockedUntil();

        if ($blockedUntil !== null) {
            throw RateLimited::budgetSpent($blockedUntil);
        }

        if ($this->remaining($priority) <= 0) {
            throw RateLimited::budgetSpent($this->resetsAt());
        }

        $key = $this->windowKey();
        $this->cache->add($key, 0, $this->windowSeconds * 2);
        $this->cache->increment($key);
    }

    public function used(): int
    {
        return max(
            $this->number($this->cache->get($this->windowKey())) ?? 0,
            $this->number($this->cache->get($this->key('observed'))) ?? 0,
        );
    }

    public function remaining(Priority $priority): int
    {
        $allowance = $priority === Priority::Interactive ? $this->ceiling : min($this->jobBudget, $this->ceiling);

        return max(0, $allowance - $this->used());
    }

    /**
     * Record what Companies House reported in its rate limit headers.
     */
    public function observe(?int $limit, ?int $remaining, ?int $resetEpoch): void
    {
        if ($limit === null || $remaining === null) {
            return;
        }

        $this->cache->put($this->key('observed'), max(0, $limit - $remaining), $this->ttlUntil($resetEpoch));
    }

    /**
     * Stop all calls until the reset time, after a 429.
     */
    public function blockUntil(CarbonImmutable $resetsAt): void
    {
        $this->cache->put($this->key('blocked'), $resetsAt->getTimestamp(), $this->ttlUntil($resetsAt->getTimestamp()));
    }

    public function blockedUntil(): ?CarbonImmutable
    {
        $epoch = $this->number($this->cache->get($this->key('blocked')));

        if ($epoch === null || $epoch <= CarbonImmutable::now()->getTimestamp()) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp($epoch);
    }

    public function resetsAt(): CarbonImmutable
    {
        $window = intdiv(CarbonImmutable::now()->getTimestamp(), $this->windowSeconds);

        return CarbonImmutable::createFromTimestamp(($window + 1) * $this->windowSeconds);
    }

    private function windowKey(): string
    {
        return $this->key('window:' . intdiv(CarbonImmutable::now()->getTimestamp(), $this->windowSeconds));
    }

    private function ttlUntil(?int $epoch): int
    {
        $now = CarbonImmutable::now()->getTimestamp();

        return $epoch !== null && $epoch > $now ? $epoch - $now : $this->windowSeconds;
    }

    /**
     * Redis and database cache stores hand numbers back as strings.
     */
    private function number(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : null;
    }

    private function key(string $suffix): string
    {
        return "{$this->prefix}:rate:{$suffix}";
    }
}
