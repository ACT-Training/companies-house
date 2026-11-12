<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use ACTTraining\CompaniesHouse\Support\Payload;
use ACTTraining\CompaniesHouse\Testing\CompanyFactory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * A company profile, from GET /company/{number}.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Company implements Arrayable
{
    /**
     * @param  Collection<int, SicCode>  $sicCodes
     * @param  Collection<int, PreviousName>  $previousNames
     * @param  array<string, string>  $links
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $number,
        public string $name,
        public CompanyStatus $status,
        public ?string $statusDetail,
        public ?string $type,
        public ?string $subtype,
        public ?string $jurisdiction,
        public ?CarbonImmutable $incorporatedOn,
        public ?CarbonImmutable $ceasedOn,
        public ?Address $registeredOffice,
        public Collection $sicCodes,
        public Collection $previousNames,
        public ?CarbonImmutable $accountsNextDue,
        public bool $accountsOverdue,
        public ?CarbonImmutable $confirmationStatementNextDue,
        public bool $confirmationStatementOverdue,
        public bool $hasInsolvencyHistory,
        public bool $hasCharges,
        public array $links,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $accounts = Payload::array($data, 'accounts');
        $confirmation = Payload::array($data, 'confirmation_statement');
        $office = Payload::array($data, 'registered_office_address');

        return new self(
            number: Payload::string($data, 'company_number') ?? '',
            name: Payload::string($data, 'company_name') ?? '',
            status: CompanyStatus::fromApi(Payload::string($data, 'company_status')),
            statusDetail: Payload::string($data, 'company_status_detail'),
            type: Payload::string($data, 'type'),
            subtype: Payload::string($data, 'subtype'),
            jurisdiction: Payload::string($data, 'jurisdiction'),
            incorporatedOn: Payload::date($data, 'date_of_creation'),
            ceasedOn: Payload::date($data, 'date_of_cessation'),
            registeredOffice: $office === [] ? null : Address::fromArray($office),
            sicCodes: collect(Payload::strings($data, 'sic_codes'))->map(SicCode::fromCode(...))->values(),
            previousNames: collect(Payload::items($data, 'previous_company_names'))->map(PreviousName::fromArray(...))->values(),
            accountsNextDue: Payload::date($accounts, 'next_due'),
            accountsOverdue: Payload::bool($accounts, 'overdue'),
            confirmationStatementNextDue: Payload::date($confirmation, 'next_due'),
            confirmationStatementOverdue: Payload::bool($confirmation, 'overdue'),
            hasInsolvencyHistory: Payload::bool($data, 'has_insolvency_history'),
            hasCharges: Payload::bool($data, 'has_charges'),
            links: array_filter(Payload::array($data, 'links'), is_string(...)),
            raw: $data,
        );
    }

    public static function factory(): CompanyFactory
    {
        return new CompanyFactory;
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }

    public function isDissolved(): bool
    {
        return $this->status === CompanyStatus::Dissolved;
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'name' => $this->name,
            'status' => $this->status->value,
            'status_detail' => $this->statusDetail,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'jurisdiction' => $this->jurisdiction,
            'incorporated_on' => $this->incorporatedOn?->toDateString(),
            'ceased_on' => $this->ceasedOn?->toDateString(),
            'registered_office' => $this->registeredOffice?->toArray(),
            'sic_codes' => $this->sicCodes->map(fn (SicCode $sic): array => $sic->toArray())->all(),
            'previous_names' => $this->previousNames->map(fn (PreviousName $name): array => $name->toArray())->all(),
            'accounts_next_due' => $this->accountsNextDue?->toDateString(),
            'accounts_overdue' => $this->accountsOverdue,
            'confirmation_statement_next_due' => $this->confirmationStatementNextDue?->toDateString(),
            'confirmation_statement_overdue' => $this->confirmationStatementOverdue,
            'has_insolvency_history' => $this->hasInsolvencyHistory,
            'has_charges' => $this->hasCharges,
            'links' => $this->links,
        ];
    }
}
