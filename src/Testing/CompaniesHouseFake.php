<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Testing;

use ACTTraining\CompaniesHouse\Client;
use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Support\CompanyNumber;
use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Assert;

/**
 * An in-memory Companies House for host app tests. Swapped in by
 * CompaniesHouse::fake(); nothing it does reaches the network.
 */
class CompaniesHouseFake extends Client
{
    private readonly FakeTransport $transport;

    /**
     * @param  array<int, Company|array<array-key, mixed>>  $companies
     */
    public function __construct(array $companies = [])
    {
        $this->transport = new FakeTransport;

        parent::__construct(new Gateway($this->transport));

        $this->withCompanies($companies);
    }

    /**
     * @param  array<int, Company|array<array-key, mixed>>  $companies  companies, or raw profile arrays (e.g. fixtures)
     */
    public function withCompanies(array $companies): static
    {
        foreach ($companies as $company) {
            $raw = $company instanceof Company ? $company->raw : $company;
            $number = Payload::string($raw, 'company_number');

            if ($number !== null) {
                $this->transport->companies[CompanyNumber::normalise($number)] = $raw;
            }
        }

        return $this;
    }

    /**
     * @param  array<int, array<array-key, mixed>>  $officers  raw officer items
     */
    public function withOfficers(string $number, array $officers): static
    {
        return $this->withList('/company/' . CompanyNumber::normalise($number) . '/officers', $officers, 'total_results');
    }

    /**
     * @param  array<int, array<array-key, mixed>>  $filings  raw filing history items
     */
    public function withFilings(string $number, array $filings): static
    {
        return $this->withList('/company/' . CompanyNumber::normalise($number) . '/filing-history', $filings, 'total_count');
    }

    /**
     * Answer a path with this exact body, or with a 404 when it is null.
     *
     * @param  array<array-key, mixed>|null  $body
     */
    public function respondTo(string $path, ?array $body): static
    {
        $this->transport->responses[$path] = $body;

        return $this;
    }

    /**
     * Make every request about this company fail.
     *
     * @param  CompaniesHouseException|'rate-limited'|'unavailable'  $failure
     */
    public function failFor(string $number, CompaniesHouseException|string $failure = 'unavailable'): static
    {
        $this->transport->failures[CompanyNumber::normalise($number)] = $this->failure($failure);

        return $this;
    }

    /**
     * Make every request fail, as if Companies House were down.
     *
     * @param  CompaniesHouseException|'rate-limited'|'unavailable'  $failure
     */
    public function failAll(CompaniesHouseException|string $failure = 'unavailable'): static
    {
        $this->transport->outage = $this->failure($failure);

        return $this;
    }

    public function assertLookedUp(string $number, ?int $times = null): void
    {
        $count = $this->lookups(CompanyNumber::normalise($number));

        if ($times === null) {
            Assert::assertGreaterThan(0, $count, "Company [{$number}] was not looked up.");

            return;
        }

        Assert::assertSame($times, $count, "Company [{$number}] was looked up {$count} times, not {$times}.");
    }

    public function assertNotLookedUp(string $number): void
    {
        Assert::assertSame(0, $this->lookups(CompanyNumber::normalise($number)), "Company [{$number}] was looked up.");
    }

    public function assertSearched(?string $term = null): void
    {
        $searches = array_filter(
            $this->transport->requests,
            fn (array $request): bool => in_array($request['path'], ['/search/companies', '/advanced-search/companies'], true)
                && ($term === null || ($request['query']['q'] ?? $request['query']['company_name_includes'] ?? null) === $term),
        );

        Assert::assertNotEmpty($searches, $term === null ? 'No search was made.' : "No search was made for [{$term}].");
    }

    public function assertNothingSent(): void
    {
        $count = count($this->transport->requests);

        Assert::assertSame(0, $count, "{$count} unexpected Companies House requests were made.");
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount($count, $this->transport->requests);
    }

    /**
     * @return list<array{path: string, query: array<string, scalar>, priority: Priority}>
     */
    public function requests(): array
    {
        return $this->transport->requests;
    }

    private function lookups(string $number): int
    {
        return count(array_filter(
            $this->transport->requests,
            fn (array $request): bool => $request['path'] === "/company/{$number}",
        ));
    }

    /**
     * @param  array<int, array<array-key, mixed>>  $items
     */
    private function withList(string $path, array $items, string $totalKey): static
    {
        return $this->respondTo($path, ['items' => array_values($items), $totalKey => count($items)]);
    }

    private function failure(CompaniesHouseException|string $failure): CompaniesHouseException
    {
        return match (true) {
            $failure instanceof CompaniesHouseException => $failure,
            $failure === 'rate-limited' => new RateLimited(CarbonImmutable::now()->addMinutes(5)),
            default => Unavailable::because('simulated outage'),
        };
    }
}
