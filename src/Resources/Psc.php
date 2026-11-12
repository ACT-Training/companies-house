<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\SignificantControl;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Persons with significant control. Personal data: on demand only, cached
 * for an hour at most, never stored or logged.
 */
final class Psc
{
    private readonly string $number;

    public function __construct(private readonly Gateway $gateway, string $number)
    {
        $this->number = CompanyNumber::normalise($number);
    }

    /**
     * @return LazyCollection<int, SignificantControl>
     */
    public function all(): LazyCollection
    {
        return $this->gateway->paginate(
            "/company/{$this->number}/persons-with-significant-control",
            [],
            SignificantControl::fromArray(...),
            cacheAs: 'psc',
        );
    }

    /**
     * @return Collection<int, SignificantControl>
     */
    public function active(): Collection
    {
        return $this->all()->filter(fn (SignificantControl $psc): bool => $psc->isActive())->values()->collect();
    }
}
