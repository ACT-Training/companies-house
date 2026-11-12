<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\Payload;
use Illuminate\Support\Collection;

final class Search
{
    public function __construct(private readonly Gateway $gateway) {}

    /**
     * Name search for typeahead: the first page only.
     *
     * @return Collection<int, SearchHit>
     */
    public function companies(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return collect();
        }

        $body = $this->gateway->get('/search/companies', [
            'q' => $term,
            'items_per_page' => max(1, min($limit, 100)),
        ], cacheAs: 'search');

        return collect($body === null ? [] : Payload::items($body))
            ->map(SearchHit::fromSearch(...))
            ->values();
    }

    public function advanced(): AdvancedSearch
    {
        return new AdvancedSearch($this->gateway);
    }
}
