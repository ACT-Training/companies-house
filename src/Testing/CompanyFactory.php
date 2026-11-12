<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Testing;

use ACTTraining\CompaniesHouse\Data\Company;
use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use DateTimeInterface;

/**
 * Builds Company objects (and the raw profile arrays behind them) for tests.
 *
 *     Company::factory()->name('ACME LTD')->postcode('CF10 1AA')->sic('85590')->make();
 *     CompaniesHouse::fake([Company::factory()->dissolved()->make()]);
 */
final class CompanyFactory
{
    private static int $sequence = 0;

    /** @var array<string, mixed> */
    private array $attributes;

    public function __construct()
    {
        $n = ++self::$sequence;
        $number = sprintf('%08d', 10000000 + $n);

        $this->attributes = [
            'company_number' => $number,
            'company_name' => "EXAMPLE COMPANY {$n} LTD",
            'company_status' => 'active',
            'type' => 'ltd',
            'jurisdiction' => 'england-wales',
            'date_of_creation' => '2015-04-01',
            'registered_office_address' => [
                'address_line_1' => "{$n} Example Street",
                'locality' => 'Cardiff',
                'postal_code' => 'CF10 1AA',
                'country' => 'Wales',
            ],
            'sic_codes' => ['85590'],
            'accounts' => ['next_due' => '2027-12-31', 'overdue' => false],
            'confirmation_statement' => ['next_due' => '2027-04-15', 'overdue' => false],
            'has_insolvency_history' => false,
            'has_charges' => false,
            'links' => ['self' => "/company/{$number}"],
        ];
    }

    public function number(string $number): self
    {
        return $this->state(['company_number' => $number, 'links' => ['self' => "/company/{$number}"]]);
    }

    public function name(string $name): self
    {
        return $this->state(['company_name' => $name]);
    }

    public function status(CompanyStatus|string $status): self
    {
        return $this->state(['company_status' => $status instanceof CompanyStatus ? $status->value : $status]);
    }

    public function dissolved(DateTimeInterface|string $on = '2025-01-31'): self
    {
        return $this->state(['company_status' => 'dissolved', 'date_of_cessation' => $this->date($on)]);
    }

    public function inLiquidation(): self
    {
        return $this->state(['company_status' => 'liquidation', 'has_insolvency_history' => true]);
    }

    public function renamedFrom(string $previousName, DateTimeInterface|string $until = '2024-06-30'): self
    {
        return $this->state(['previous_company_names' => [[
            'name' => $previousName,
            'effective_from' => '2015-04-01',
            'ceased_on' => $this->date($until),
        ]]]);
    }

    public function postcode(string $postcode): self
    {
        return $this->address(['postal_code' => $postcode]);
    }

    public function locality(string $locality): self
    {
        return $this->address(['locality' => $locality]);
    }

    /**
     * @param  array<string, string>  $address  raw registered_office_address fields
     */
    public function address(array $address): self
    {
        /** @var array<string, string> $current */
        $current = $this->attributes['registered_office_address'];

        return $this->state(['registered_office_address' => array_merge($current, $address)]);
    }

    public function sic(string ...$codes): self
    {
        return $this->state(['sic_codes' => array_values($codes)]);
    }

    public function incorporatedOn(DateTimeInterface|string $date): self
    {
        return $this->state(['date_of_creation' => $this->date($date)]);
    }

    public function accountsOverdue(): self
    {
        return $this->state(['accounts' => ['next_due' => '2024-12-31', 'overdue' => true]]);
    }

    /**
     * @param  array<string, mixed>  $attributes  raw API fields to override
     */
    public function state(array $attributes): self
    {
        $clone = clone $this;
        $clone->attributes = array_merge($this->attributes, $attributes);

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->attributes;
    }

    public function make(): Company
    {
        return Company::fromArray($this->attributes);
    }

    private function date(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : $date;
    }
}
