<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Data;

use ACTTraining\CompaniesHouse\Support\SicLookup;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, string|null>
 */
final readonly class SicCode implements Arrayable
{
    /**
     * SIC 2007 sections by two-digit division.
     */
    private const array SECTIONS = [
        'A' => [1, 3], 'B' => [5, 9], 'C' => [10, 33], 'D' => [35, 35], 'E' => [36, 39],
        'F' => [41, 43], 'G' => [45, 47], 'H' => [49, 53], 'I' => [55, 56], 'J' => [58, 63],
        'K' => [64, 66], 'L' => [68, 68], 'M' => [69, 75], 'N' => [77, 82], 'O' => [84, 84],
        'P' => [85, 85], 'Q' => [86, 88], 'R' => [90, 93], 'S' => [94, 96], 'T' => [97, 98],
        'U' => [99, 99],
    ];

    public function __construct(
        public string $code,
        public ?string $description,
        public ?string $section,
    ) {}

    public static function fromCode(string $code): self
    {
        return new self($code, self::describe($code), self::sectionFor($code));
    }

    public static function describe(string $code): ?string
    {
        return SicLookup::describe($code);
    }

    public static function sectionFor(string $code): ?string
    {
        // 99999 is Companies House's own "Dormant Company" code, not section U.
        if ($code === '99999' || ! ctype_digit($code) || strlen($code) < 2) {
            return null;
        }

        $division = (int) substr($code, 0, 2);

        foreach (self::SECTIONS as $section => [$from, $to]) {
            if ($division >= $from && $division <= $to) {
                return $section;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
            'section' => $this->section,
        ];
    }
}
