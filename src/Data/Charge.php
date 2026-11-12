<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * A charge (mortgage or security) registered against a company.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Charge implements Arrayable
{
    /**
     * @param  list<string>  $personsEntitled
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?string $code,
        public ?string $status,
        public ?string $classification,
        public ?CarbonImmutable $createdOn,
        public ?CarbonImmutable $deliveredOn,
        public ?CarbonImmutable $satisfiedOn,
        public array $personsEntitled,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $persons = array_values(array_filter(array_map(
            fn (array $person): ?string => Payload::string($person, 'name'),
            Payload::items($data, 'persons_entitled'),
        )));

        return new self(
            code: Payload::string($data, 'charge_code') ?? Payload::string($data, 'charge_number'),
            status: Payload::string($data, 'status'),
            classification: Payload::string(Payload::array($data, 'classification'), 'description'),
            createdOn: Payload::date($data, 'created_on'),
            deliveredOn: Payload::date($data, 'delivered_on'),
            satisfiedOn: Payload::date($data, 'satisfied_on'),
            personsEntitled: $persons,
            raw: $data,
        );
    }

    public function isOutstanding(): bool
    {
        return $this->status === 'outstanding' || $this->status === 'part-satisfied';
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'status' => $this->status,
            'classification' => $this->classification,
            'created_on' => $this->createdOn?->toDateString(),
            'delivered_on' => $this->deliveredOn?->toDateString(),
            'satisfied_on' => $this->satisfiedOn?->toDateString(),
            'persons_entitled' => $this->personsEntitled,
        ];
    }
}
