<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * One entry in a company's filing history.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class FilingItem implements Arrayable
{
    /**
     * @param  array<array-key, mixed>  $descriptionValues
     * @param  array<string, string>  $links
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?string $transactionId,
        public ?string $category,
        public ?string $type,
        public ?CarbonImmutable $date,
        public ?string $description,
        public array $descriptionValues,
        public ?int $pages,
        public array $links,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: Payload::string($data, 'transaction_id'),
            category: Payload::string($data, 'category'),
            type: Payload::string($data, 'type'),
            date: Payload::date($data, 'date'),
            description: Payload::string($data, 'description'),
            descriptionValues: Payload::array($data, 'description_values'),
            pages: Payload::int($data, 'pages'),
            links: array_filter(Payload::array($data, 'links'), is_string(...)),
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'category' => $this->category,
            'type' => $this->type,
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'description_values' => $this->descriptionValues,
            'pages' => $this->pages,
            'links' => $this->links,
        ];
    }
}
