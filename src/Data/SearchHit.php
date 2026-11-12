<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Enums\CompanyStatus;
use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * One result from name search or advanced search.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class SearchHit implements Arrayable
{
    /**
     * @param  Collection<int, SicCode>  $sicCodes
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $number,
        public string $name,
        public CompanyStatus $status,
        public ?string $type,
        public ?Address $address,
        public ?string $addressSnippet,
        public ?CarbonImmutable $incorporatedOn,
        public ?CarbonImmutable $ceasedOn,
        public Collection $sicCodes,
        public array $raw,
    ) {}

    /**
     * An item from GET /search/companies.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromSearch(array $data): self
    {
        $address = Payload::array($data, 'address');

        return new self(
            number: Payload::string($data, 'company_number') ?? '',
            name: Payload::string($data, 'title') ?? '',
            status: CompanyStatus::fromApi(Payload::string($data, 'company_status')),
            type: Payload::string($data, 'company_type'),
            address: $address === [] ? null : Address::fromArray($address),
            addressSnippet: Payload::string($data, 'address_snippet'),
            incorporatedOn: Payload::date($data, 'date_of_creation'),
            ceasedOn: Payload::date($data, 'date_of_cessation'),
            sicCodes: collect(),
            raw: $data,
        );
    }

    /**
     * An item from GET /advanced-search/companies.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromAdvancedSearch(array $data): self
    {
        $address = Payload::array($data, 'registered_office_address');
        $office = $address === [] ? null : Address::fromArray($address);

        return new self(
            number: Payload::string($data, 'company_number') ?? '',
            name: Payload::string($data, 'company_name') ?? '',
            status: CompanyStatus::fromApi(Payload::string($data, 'company_status')),
            type: Payload::string($data, 'company_type'),
            address: $office,
            addressSnippet: $office?->oneLine(),
            incorporatedOn: Payload::date($data, 'date_of_creation'),
            ceasedOn: Payload::date($data, 'date_of_cessation'),
            sicCodes: collect(Payload::strings($data, 'sic_codes'))->map(SicCode::fromCode(...))->values(),
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'name' => $this->name,
            'status' => $this->status->value,
            'type' => $this->type,
            'address' => $this->address?->toArray(),
            'address_snippet' => $this->addressSnippet,
            'incorporated_on' => $this->incorporatedOn?->toDateString(),
            'ceased_on' => $this->ceasedOn?->toDateString(),
            'sic_codes' => $this->sicCodes->map(fn (SicCode $sic): array => $sic->toArray())->all(),
        ];
    }
}
