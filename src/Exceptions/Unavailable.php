<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

use Throwable;

final class Unavailable extends CompaniesHouseException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self("Companies House is unavailable: {$reason}", 0, $previous);
    }

    public function retryable(): bool
    {
        return true;
    }
}
