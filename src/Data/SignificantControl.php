<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * A person or entity with significant control. Often personal data: fetch
 * on demand only, never store it in the mirror and never log it.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class SignificantControl implements Arrayable
{
    /**
     * @param  list<string>  $naturesOfControl
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public ?string $kind,
        public array $naturesOfControl,
        public ?CarbonImmutable $notifiedOn,
        public ?CarbonImmutable $ceasedOn,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Payload::string($data, 'name') ?? '',
            kind: Payload::string($data, 'kind'),
            naturesOfControl: Payload::strings($data, 'natures_of_control'),
            notifiedOn: Payload::date($data, 'notified_on'),
            ceasedOn: Payload::date($data, 'ceased_on'),
            raw: $data,
        );
    }

    public function isActive(): bool
    {
        return $this->ceasedOn === null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'kind' => $this->kind,
            'natures_of_control' => $this->naturesOfControl,
            'notified_on' => $this->notifiedOn?->toDateString(),
            'ceased_on' => $this->ceasedOn?->toDateString(),
        ];
    }
}
