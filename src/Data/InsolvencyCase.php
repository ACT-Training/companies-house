<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * One insolvency case. Practitioner details stay in $raw only.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class InsolvencyCase implements Arrayable
{
    /**
     * @param  array<string, CarbonImmutable>  $dates  keyed by date type, e.g. wound-up-on
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?string $number,
        public ?string $type,
        public array $dates,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dates = [];

        foreach (Payload::items($data, 'dates') as $date) {
            $type = Payload::string($date, 'type');
            $value = Payload::date($date, 'date');

            if ($type !== null && $value !== null) {
                $dates[$type] = $value;
            }
        }

        return new self(
            number: Payload::string($data, 'number'),
            type: Payload::string($data, 'type'),
            dates: $dates,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'type' => $this->type,
            'dates' => array_map(fn (CarbonImmutable $date): string => $date->toDateString(), $this->dates),
        ];
    }
}
