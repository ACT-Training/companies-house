<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Facades;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Data\LookupResult;
use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Resources\AdvancedSearch;
use ACTTraining\CompaniesHouse\Resources\Charges;
use ACTTraining\CompaniesHouse\Resources\Companies;
use ACTTraining\CompaniesHouse\Resources\Filings;
use ACTTraining\CompaniesHouse\Resources\Insolvency;
use ACTTraining\CompaniesHouse\Resources\Officers;
use ACTTraining\CompaniesHouse\Resources\Psc;
use ACTTraining\CompaniesHouse\Testing\CompaniesHouseFake;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Company|null find(string $number)
 * @method static LookupResult tryFind(string $number)
 * @method static Collection<int, SearchHit> search(string $term, int $limit = 20)
 * @method static AdvancedSearch advancedSearch()
 * @method static Companies companies()
 * @method static Officers officers(string $number)
 * @method static Filings filings(string $number)
 * @method static Insolvency insolvency(string $number)
 * @method static Charges charges(string $number)
 * @method static Psc psc(string $number)
 * @method static CompaniesHouseClient background()
 * @method static CompaniesHouseClient interactive()
 *
 * @see CompaniesHouseClient
 */
class CompaniesHouse extends Facade
{
    /**
     * Swap the client for an in-memory fake. Nothing reaches Companies House.
     *
     * @param  array<int, Company|array<array-key, mixed>>  $companies  companies, or raw profile arrays
     */
    public static function fake(array $companies = []): CompaniesHouseFake
    {
        $fake = new CompaniesHouseFake($companies);

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return CompaniesHouseClient::class;
    }
}
