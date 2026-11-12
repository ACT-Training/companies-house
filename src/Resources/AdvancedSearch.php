<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Resources;

use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use ACTTraining\CompaniesHouse\Http\Gateway;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Builds a GET /advanced-search/companies query. Companies House caps each
 * search at 5,000 results; for larger segments use the local mirror.
 */
final class AdvancedSearch
{
    public const int MAX_RESULTS = 5000;

    /** @var array<string, string> */
    private array $filters = [];

    public function __construct(private readonly Gateway $gateway) {}

    public function nameIncludes(string $text): self
    {
        return $this->with('company_name_includes', $text);
    }

    public function nameExcludes(string $text): self
    {
        return $this->with('company_name_excludes', $text);
    }

    /**
     * @param  CompanyStatus|string|array<int, CompanyStatus|string>  $status
     */
    public function status(CompanyStatus|string|array $status): self
    {
        $values = array_map(
            fn (CompanyStatus|string $value): string => $value instanceof CompanyStatus ? $value->value : $value,
            is_array($status) ? $status : [$status],
        );

        return $this->with('company_status', implode(',', $values));
    }

    /**
     * @param  string|array<int, string>  $type  e.g. ltd, plc, llp
     */
    public function type(string|array $type): self
    {
        return $this->with('company_type', implode(',', (array) $type));
    }

    /**
     * @param  string|array<int, string>  $codes
     */
    public function sic(string|array $codes): self
    {
        return $this->with('sic_codes', implode(',', (array) $codes));
    }

    public function location(string $location): self
    {
        return $this->with('location', $location);
    }

    public function incorporatedAfter(DateTimeInterface|string $date): self
    {
        return $this->with('incorporated_from', $this->date($date));
    }

    public function incorporatedBefore(DateTimeInterface|string $date): self
    {
        return $this->with('incorporated_to', $this->date($date));
    }

    public function dissolvedAfter(DateTimeInterface|string $date): self
    {
        return $this->with('dissolved_from', $this->date($date));
    }

    public function dissolvedBefore(DateTimeInterface|string $date): self
    {
        return $this->with('dissolved_to', $this->date($date));
    }

    /**
     * Every match, fetched a page at a time as it is iterated.
     *
     * @return LazyCollection<int, SearchHit>
     */
    public function cursor(int $pageSize = 500): LazyCollection
    {
        return $this->gateway->paginate(
            '/advanced-search/companies',
            $this->filters,
            SearchHit::fromAdvancedSearch(...),
            sizeParameter: 'size',
            pageSize: max(1, min($pageSize, self::MAX_RESULTS)),
            totalKey: 'hits',
            cacheAs: 'search',
            max: self::MAX_RESULTS,
        );
    }

    /**
     * @return Collection<int, SearchHit>
     */
    public function get(int $limit = 100): Collection
    {
        return $this->cursor(min($limit, self::MAX_RESULTS))->take($limit)->collect()->values();
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return $this->filters;
    }

    private function with(string $key, string $value): self
    {
        $this->filters[$key] = $value;

        return $this;
    }

    private function date(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;
    }
}
