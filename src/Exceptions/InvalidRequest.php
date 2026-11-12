<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

/**
 * The request itself was wrong (a malformed company number, or a 4xx other
 * than 401, 404 and 429). Retrying will not help.
 */
final class InvalidRequest extends CompaniesHouseException
{
    public static function companyNumber(string $number): self
    {
        return new self(sprintf('"%s" is not a valid company number.', mb_substr($number, 0, 20)));
    }

    public static function status(int $status): self
    {
        return new self("Companies House rejected the request with status {$status}.");
    }

    public function retryable(): bool
    {
        return false;
    }
}
