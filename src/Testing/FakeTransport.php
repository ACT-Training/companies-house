<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Testing;

use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Support\Payload;
use Illuminate\Support\Str;

/**
 * Answers Companies House requests from memory, the way the real API would,
 * and records every request for assertions.
 *
 * @internal Use CompaniesHouse::fake().
 */
final class FakeTransport implements Transport
{
    /** @var array<string, array<array-key, mixed>> raw company profiles by number */
    public array $companies = [];

    /** @var array<string, array<array-key, mixed>|null> exact responses by path */
    public array $responses = [];

    /** @var array<string, CompaniesHouseException> failures by company number */
    public array $failures = [];

    public ?CompaniesHouseException $outage = null;

    /** @var list<array{path: string, query: array<string, scalar>, priority: Priority}> */
    public array $requests = [];

    public function get(string $path, array $query, Priority $priority): ?array
    {
        $this->requests[] = ['path' => $path, 'query' => $query, 'priority' => $priority];

        if ($this->outage !== null) {
            throw $this->outage;
        }

        if (preg_match('#^/company/([A-Z0-9]{8})#', $path, $matches) === 1 && isset($this->failures[$matches[1]])) {
            throw $this->failures[$matches[1]];
        }

        if (array_key_exists($path, $this->responses)) {
            return $this->page($this->responses[$path], $query);
        }

        return match (true) {
            $path === '/search/companies' => $this->search($query),
            $path === '/advanced-search/companies' => $this->advancedSearch($query),
            preg_match('#^/company/([A-Z0-9]{8})$#', $path, $m) === 1 => $this->companies[$m[1]] ?? null,
            preg_match('#^/company/([A-Z0-9]{8})/registered-office-address$#', $path, $m) === 1 => isset($this->companies[$m[1]])
                ? Payload::array($this->companies[$m[1]], 'registered_office_address')
                : null,
            preg_match('#^/company/([A-Z0-9]{8})/insolvency$#', $path) === 1 => null,
            preg_match('#^/company/([A-Z0-9]{8})/(officers|filing-history|charges|persons-with-significant-control)$#', $path, $m) === 1 => isset($this->companies[$m[1]])
                ? ['items' => [], 'total_results' => 0, 'total_count' => 0]
                : null,
            default => null,
        };
    }

    /**
     * Apply start_index and page size to a seeded list response.
     *
     * @param  array<array-key, mixed>|null  $body
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>|null
     */
    private function page(?array $body, array $query): ?array
    {
        if ($body === null || ! isset($body['items']) || ! isset($query['start_index'])) {
            return $body;
        }

        $size = (int) ($query['items_per_page'] ?? $query['size'] ?? 100);
        $body['items'] = array_slice(Payload::items($body), (int) $query['start_index'], $size);

        return $body;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>
     */
    private function search(array $query): array
    {
        $term = Str::lower((string) ($query['q'] ?? ''));
        $limit = (int) ($query['items_per_page'] ?? 20);

        $matches = array_values(array_filter(
            $this->companies,
            fn (array $company): bool => $term !== '' && Str::contains(Str::lower(Payload::string($company, 'company_name') ?? ''), $term),
        ));

        $items = array_map(function (array $company): array {
            $address = Payload::array($company, 'registered_office_address');

            return [
                'company_number' => $company['company_number'] ?? null,
                'title' => $company['company_name'] ?? null,
                'company_status' => $company['company_status'] ?? null,
                'company_type' => $company['type'] ?? null,
                'date_of_creation' => $company['date_of_creation'] ?? null,
                'date_of_cessation' => $company['date_of_cessation'] ?? null,
                'address' => $address,
                'address_snippet' => implode(', ', array_filter(array_map(
                    fn (string $key): ?string => Payload::string($address, $key),
                    ['premises', 'address_line_1', 'locality', 'postal_code'],
                ))),
                'kind' => 'searchresults#company',
            ];
        }, $matches);

        return [
            'items' => array_slice($items, (int) ($query['start_index'] ?? 0), $limit),
            'total_results' => count($items),
            'items_per_page' => $limit,
            'start_index' => (int) ($query['start_index'] ?? 0),
        ];
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>|null
     */
    private function advancedSearch(array $query): ?array
    {
        $matches = array_values(array_filter(
            $this->companies,
            fn (array $company): bool => $this->matchesAdvancedSearch($company, $query),
        ));

        if ($matches === []) {
            return null; // Companies House answers 404 when nothing matches
        }

        $items = array_map(fn (array $company): array => [
            'company_number' => $company['company_number'] ?? null,
            'company_name' => $company['company_name'] ?? null,
            'company_status' => $company['company_status'] ?? null,
            'company_type' => $company['type'] ?? null,
            'date_of_creation' => $company['date_of_creation'] ?? null,
            'date_of_cessation' => $company['date_of_cessation'] ?? null,
            'registered_office_address' => Payload::array($company, 'registered_office_address'),
            'sic_codes' => Payload::strings($company, 'sic_codes'),
            'kind' => 'search-results#company',
        ], $matches);

        $start = (int) ($query['start_index'] ?? 0);

        return [
            'items' => array_slice($items, $start, (int) ($query['size'] ?? 20)),
            'hits' => count($items),
            'kind' => 'search#advanced-search',
        ];
    }

    /**
     * @param  array<array-key, mixed>  $company
     * @param  array<string, scalar>  $query
     */
    private function matchesAdvancedSearch(array $company, array $query): bool
    {
        $name = Str::lower(Payload::string($company, 'company_name') ?? '');
        $address = Str::lower(implode(' ', array_filter(Payload::array($company, 'registered_office_address'), is_string(...))));

        return $this->contains($name, $query['company_name_includes'] ?? null, true)
            && $this->contains($name, $query['company_name_excludes'] ?? null, false)
            && $this->contains($address, $query['location'] ?? null, true)
            && $this->oneOf([Payload::string($company, 'company_status')], $query['company_status'] ?? null)
            && $this->oneOf([Payload::string($company, 'type')], $query['company_type'] ?? null)
            && $this->oneOf(Payload::strings($company, 'sic_codes'), $query['sic_codes'] ?? null)
            && $this->between(Payload::string($company, 'date_of_creation'), $query['incorporated_from'] ?? null, $query['incorporated_to'] ?? null);
    }

    private function contains(string $haystack, mixed $needle, bool $expected): bool
    {
        return ! is_string($needle) || Str::contains($haystack, Str::lower($needle)) === $expected;
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function oneOf(array $values, mixed $commaList): bool
    {
        return ! is_string($commaList) || array_intersect($values, explode(',', $commaList)) !== [];
    }

    private function between(?string $date, mixed $from, mixed $to): bool
    {
        return (! is_string($from) || ($date !== null && $date >= $from))
            && (! is_string($to) || ($date !== null && $date <= $to));
    }
}
