<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\FilingItem;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Filing history of one company, newest first.
 */
final class Filings
{
    private readonly string $number;

    public function __construct(private readonly Gateway $gateway, string $number)
    {
        $this->number = CompanyNumber::normalise($number);
    }

    /**
     * @param  string|array<int, string>|null  $category  e.g. accounts, confirmation-statement, officers
     * @return LazyCollection<int, FilingItem>
     */
    public function cursor(string|array|null $category = null, int $pageSize = 100): LazyCollection
    {
        $query = $category === null ? [] : ['category' => implode(',', (array) $category)];

        return $this->gateway->paginate(
            "/company/{$this->number}/filing-history",
            $query,
            FilingItem::fromArray(...),
            pageSize: $pageSize,
            totalKey: 'total_count',
            cacheAs: 'filings',
        );
    }

    /**
     * @return Collection<int, FilingItem>
     */
    public function latest(int $count = 10): Collection
    {
        return $this->cursor(pageSize: max(1, min($count, 100)))->take($count)->collect()->values();
    }
}
