<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|null>
 */
final readonly class PreviousName implements Arrayable
{
    public function __construct(
        public string $name,
        public ?CarbonImmutable $effectiveFrom,
        public ?CarbonImmutable $ceasedOn,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Payload::string($data, 'name') ?? '',
            effectiveFrom: Payload::date($data, 'effective_from'),
            ceasedOn: Payload::date($data, 'ceased_on'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'effective_from' => $this->effectiveFrom?->toDateString(),
            'ceased_on' => $this->ceasedOn?->toDateString(),
        ];
    }
}
