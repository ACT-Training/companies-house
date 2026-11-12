<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\Officer;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Officers of one company. Personal data: on demand only, cached for an
 * hour at most, never stored or logged.
 */
final class Officers
{
    private readonly string $number;

    public function __construct(private readonly Gateway $gateway, string $number)
    {
        $this->number = CompanyNumber::normalise($number);
    }

    /**
     * @return LazyCollection<int, Officer>
     */
    public function all(): LazyCollection
    {
        return $this->gateway->paginate(
            "/company/{$this->number}/officers",
            [],
            Officer::fromArray(...),
            cacheAs: 'officers',
        );
    }

    /**
     * @return Collection<int, Officer>
     */
    public function active(): Collection
    {
        return $this->all()->filter(fn (Officer $officer): bool => $officer->isActive())->values()->collect();
    }
}
