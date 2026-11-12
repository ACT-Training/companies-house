<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\Address;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;

final class Companies
{
    public function __construct(private readonly Gateway $gateway) {}

    /**
     * The company profile, or null if Companies House has no such company.
     */
    public function find(string $number): ?Company
    {
        $body = $this->gateway->get('/company/' . CompanyNumber::normalise($number), cacheAs: 'company');

        return $body === null ? null : Company::fromArray($body);
    }

    public function registeredOffice(string $number): ?Address
    {
        $path = '/company/' . CompanyNumber::normalise($number) . '/registered-office-address';
        $body = $this->gateway->get($path, cacheAs: 'company');

        return $body === null ? null : Address::fromArray($body);
    }
}
