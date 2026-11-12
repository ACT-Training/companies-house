<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|null>
 */
final readonly class Address implements Arrayable
{
    public function __construct(
        public ?string $premises = null,
        public ?string $line1 = null,
        public ?string $line2 = null,
        public ?string $locality = null,
        public ?string $region = null,
        public ?string $postcode = null,
        public ?string $country = null,
        public ?string $careOf = null,
        public ?string $poBox = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            premises: Payload::string($data, 'premises'),
            line1: Payload::string($data, 'address_line_1'),
            line2: Payload::string($data, 'address_line_2'),
            locality: Payload::string($data, 'locality'),
            region: Payload::string($data, 'region'),
            postcode: Payload::string($data, 'postal_code'),
            country: Payload::string($data, 'country'),
            careOf: Payload::string($data, 'care_of'),
            poBox: Payload::string($data, 'po_box'),
        );
    }

    /**
     * The address on one line, for display.
     */
    public function oneLine(): string
    {
        $first = trim(implode(' ', array_filter([$this->premises, $this->line1])));

        return implode(', ', array_filter([$first, $this->line2, $this->locality, $this->region, $this->postcode]));
    }

    public function toArray(): array
    {
        return [
            'premises' => $this->premises,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'locality' => $this->locality,
            'region' => $this->region,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'care_of' => $this->careOf,
            'po_box' => $this->poBox,
        ];
    }
}
