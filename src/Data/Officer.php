<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\Payload;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * A company officer. This is personal data: fetch on demand only, never
 * store it in the mirror and never log it.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Officer implements Arrayable
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $name,
        public ?string $role,
        public ?CarbonImmutable $appointedOn,
        public ?CarbonImmutable $resignedOn,
        public ?string $occupation,
        public ?string $nationality,
        public ?string $countryOfResidence,
        public ?int $birthMonth,
        public ?int $birthYear,
        public ?Address $address,
        public array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $birth = Payload::array($data, 'date_of_birth');
        $address = Payload::array($data, 'address');

        return new self(
            name: Payload::string($data, 'name') ?? '',
            role: Payload::string($data, 'officer_role'),
            appointedOn: Payload::date($data, 'appointed_on'),
            resignedOn: Payload::date($data, 'resigned_on'),
            occupation: Payload::string($data, 'occupation'),
            nationality: Payload::string($data, 'nationality'),
            countryOfResidence: Payload::string($data, 'country_of_residence'),
            birthMonth: Payload::int($birth, 'month'),
            birthYear: Payload::int($birth, 'year'),
            address: $address === [] ? null : Address::fromArray($address),
            raw: $data,
        );
    }

    public function isActive(): bool
    {
        return $this->resignedOn === null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'appointed_on' => $this->appointedOn?->toDateString(),
            'resigned_on' => $this->resignedOn?->toDateString(),
            'occupation' => $this->occupation,
            'nationality' => $this->nationality,
            'country_of_residence' => $this->countryOfResidence,
            'birth_month' => $this->birthMonth,
            'birth_year' => $this->birthYear,
            'address' => $this->address?->toArray(),
        ];
    }
}
