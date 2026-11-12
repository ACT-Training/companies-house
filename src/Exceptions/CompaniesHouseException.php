<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Exceptions;

use RuntimeException;

/**
 * Base for every failure the client throws. A 404 is not a failure:
 * find() returns null instead. Messages must never contain the API key.
 */
abstract class CompaniesHouseException extends RuntimeException
{
    abstract public function retryable(): bool;
}
