<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Contracts;

use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Data\LookupResult;
use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Resources\AdvancedSearch;
use ACTTraining\CompaniesHouse\Resources\Charges;
use ACTTraining\CompaniesHouse\Resources\Companies;
use ACTTraining\CompaniesHouse\Resources\Filings;
use ACTTraining\CompaniesHouse\Resources\Insolvency;
use ACTTraining\CompaniesHouse\Resources\Officers;
use ACTTraining\CompaniesHouse\Resources\Psc;
use Illuminate\Support\Collection;

/**
 * The only surface host apps should depend on, alongside the facade.
 */
interface CompaniesHouseClient
{
    /**
     * The company profile, or null if Companies House has no such company.
     *
     * @throws CompaniesHouseException when Companies House cannot answer
     */
    public function find(string $number): ?Company;

    /**
     * Like find(), but never throws: a failure is reported on the result.
     */
    public function tryFind(string $number): LookupResult;

    /**
     * Name search for typeahead: the first page only, cached for an hour.
     *
     * @return Collection<int, SearchHit>
     */
    public function search(string $term, int $limit = 20): Collection;

    public function advancedSearch(): AdvancedSearch;

    public function companies(): Companies;

    public function officers(string $number): Officers;

    public function filings(string $number): Filings;

    public function insolvency(string $number): Insolvency;

    public function charges(string $number): Charges;

    public function psc(string $number): Psc;

    /**
     * A client that stays inside the job budget, for queued jobs and bulk work.
     */
    public function background(): static;

    /**
     * A client that may use the whole rate ceiling, for a person waiting on a page.
     */
    public function interactive(): static;
}
