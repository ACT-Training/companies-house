<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\InsolvencyCase;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use ACTTraining\CompaniesHouse\Support\Payload;
use Illuminate\Support\Collection;

final class Insolvency
{
    private readonly string $number;

    public function __construct(private readonly Gateway $gateway, string $number)
    {
        $this->number = CompanyNumber::normalise($number);
    }

    /**
     * Insolvency cases; empty when the company has none (Companies House returns a 404).
     *
     * @return Collection<int, InsolvencyCase>
     */
    public function cases(): Collection
    {
        $body = $this->gateway->get("/company/{$this->number}/insolvency", cacheAs: 'insolvency');

        return collect($body === null ? [] : Payload::items($body, 'cases'))
            ->map(InsolvencyCase::fromArray(...))
            ->values();
    }
}
