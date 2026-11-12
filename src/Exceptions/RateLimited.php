<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

use Carbon\CarbonImmutable;

final class RateLimited extends CompaniesHouseException
{
    public function __construct(
        public readonly ?CarbonImmutable $resetsAt = null,
        string $message = 'The Companies House rate limit has been reached.',
    ) {
        parent::__construct($message);
    }

    /**
     * Our own budget for this window is spent; Companies House was not called.
     */
    public static function budgetSpent(?CarbonImmutable $resetsAt): self
    {
        return new self($resetsAt, 'The Companies House request budget for this window is spent.');
    }

    /**
     * Seconds until the window resets, for a queued job's release().
     */
    public function retryAfter(): int
    {
        if ($this->resetsAt === null) {
            return 60;
        }

        return max(1, (int) ceil(CarbonImmutable::now()->diffInSeconds($this->resetsAt, false)));
    }

    public function retryable(): bool
    {
        return true;
    }
}
