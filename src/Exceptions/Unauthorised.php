<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

final class Unauthorised extends CompaniesHouseException
{
    public static function missingKey(): self
    {
        return new self('No Companies House API key is configured. Set COMPANIES_HOUSE_API_KEY.');
    }

    public static function rejected(): self
    {
        return new self('Companies House rejected the API key.');
    }

    public function retryable(): bool
    {
        return false;
    }
}
