<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\Charge;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

final class Charges
{
    private readonly string $number;

    public function __construct(private readonly Gateway $gateway, string $number)
    {
        $this->number = CompanyNumber::normalise($number);
    }

    /**
     * @return LazyCollection<int, Charge>
     */
    public function all(): LazyCollection
    {
        return $this->gateway->paginate(
            "/company/{$this->number}/charges",
            [],
            Charge::fromArray(...),
            totalKey: 'total_count',
            cacheAs: 'charges',
        );
    }

    /**
     * @return Collection<int, Charge>
     */
    public function outstanding(): Collection
    {
        return $this->all()->filter(fn (Charge $charge): bool => $charge->isOutstanding())->values()->collect();
    }
}
