<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Support;

use ACTTraining\CompaniesHouse\Exceptions\InvalidRequest;

final class CompanyNumber
{
    /**
     * Upper-case, strip spaces and pad numeric numbers to eight digits,
     * so "4567890" and "04567890" are the same company.
     *
     * @throws InvalidRequest
     */
    public static function normalise(string $number): string
    {
        $clean = self::clean($number);

        if (! self::matches($clean)) {
            throw InvalidRequest::companyNumber($number);
        }

        return $clean;
    }

    public static function isValid(string $number): bool
    {
        return self::matches(self::clean($number));
    }

    private static function clean(string $number): string
    {
        $clean = strtoupper((string) preg_replace('/\s+/', '', $number));

        return ctype_digit($clean) && strlen($clean) < 8 ? str_pad($clean, 8, '0', STR_PAD_LEFT) : $clean;
    }

    private static function matches(string $clean): bool
    {
        return preg_match('/^[A-Z0-9]{8}$/', $clean) === 1;
    }
}
