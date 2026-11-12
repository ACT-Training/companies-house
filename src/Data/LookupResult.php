<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;

/**
 * What tryFind() returns: a company, a definite "not found", or the reason
 * the lookup could not be made. It never throws.
 */
final readonly class LookupResult
{
    public function __construct(
        public ?Company $company,
        public ?CompaniesHouseException $error = null,
    ) {}

    public function found(): bool
    {
        return $this->company !== null;
    }

    /**
     * Companies House answered, and has no such company.
     */
    public function notFound(): bool
    {
        return $this->company === null && $this->error === null;
    }

    /**
     * The lookup could not be made, for example because Companies House is down.
     */
    public function failed(): bool
    {
        return $this->error !== null;
    }

    public function reason(): ?string
    {
        return $this->error?->getMessage();
    }
}
