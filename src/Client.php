<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Data\LookupResult;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Resources\AdvancedSearch;
use ACTTraining\CompaniesHouse\Resources\Charges;
use ACTTraining\CompaniesHouse\Resources\Companies;
use ACTTraining\CompaniesHouse\Resources\Filings;
use ACTTraining\CompaniesHouse\Resources\Insolvency;
use ACTTraining\CompaniesHouse\Resources\Officers;
use ACTTraining\CompaniesHouse\Resources\Psc;
use ACTTraining\CompaniesHouse\Resources\Search;
use Illuminate\Support\Collection;

class Client implements CompaniesHouseClient
{
    public function __construct(protected Gateway $gateway) {}

    public function find(string $number): ?Company
    {
        return $this->companies()->find($number);
    }

    public function tryFind(string $number): LookupResult
    {
        try {
            return new LookupResult($this->find($number));
        } catch (CompaniesHouseException $exception) {
            // Deliberate: tryFind() promises never to throw. The exception is
            // returned on the result, so callers can tell failure from "not found".
            return new LookupResult(null, $exception);
        }
    }

    public function search(string $term, int $limit = 20): Collection
    {
        return (new Search($this->gateway))->companies($term, $limit);
    }

    public function advancedSearch(): AdvancedSearch
    {
        return (new Search($this->gateway))->advanced();
    }

    public function companies(): Companies
    {
        return new Companies($this->gateway);
    }

    public function officers(string $number): Officers
    {
        return new Officers($this->gateway, $number);
    }

    public function filings(string $number): Filings
    {
        return new Filings($this->gateway, $number);
    }

    public function insolvency(string $number): Insolvency
    {
        return new Insolvency($this->gateway, $number);
    }

    public function charges(string $number): Charges
    {
        return new Charges($this->gateway, $number);
    }

    public function psc(string $number): Psc
    {
        return new Psc($this->gateway, $number);
    }

    public function background(): static
    {
        return $this->withPriority(Priority::Background);
    }

    public function interactive(): static
    {
        return $this->withPriority(Priority::Interactive);
    }

    public function priority(): Priority
    {
        return $this->gateway->priority();
    }

    protected function withPriority(Priority $priority): static
    {
        $clone = clone $this;
        $clone->gateway = $this->gateway->withPriority($priority);

        return $clone;
    }
}
