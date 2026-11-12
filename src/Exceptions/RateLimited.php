<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

use Carbon\CarbonImmutable;

final class RateLimited extends CompaniesHouseException
{
    public function __construct(
        public readonly ?CarbonImmutable $resetsAt = null,
    ) {
        parent::__construct('The Companies House rate limit has been reached.');
    }

    public function retryable(): bool
    {
        return true;
    }
}
