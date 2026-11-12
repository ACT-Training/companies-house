<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Contracts;

use Illuminate\Http\Client\PendingRequest;

/**
 * The only surface host apps should depend on, alongside the facade.
 *
 * Phase 1 adds find(), tryFind(), search(), advancedSearch(), officers()
 * and filings() here, each returning the readonly DTOs in Data\.
 */
interface CompaniesHouseClient
{
    /**
     * A pending request with the base URL, basic auth and timeout applied.
     */
    public function request(): PendingRequest;
}
