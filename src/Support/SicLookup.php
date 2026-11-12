<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Support;

/**
 * Descriptions from the bundled Companies House condensed SIC 2007 list.
 */
final class SicLookup
{
    /** @var array<string, string>|null */
    private static ?array $descriptions = null;

    public static function describe(string $code): ?string
    {
        return self::all()[$code] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$descriptions === null) {
            /** @var array<string, string> $descriptions */
            $descriptions = require __DIR__ . '/../../resources/sic-codes.php';
            self::$descriptions = $descriptions;
        }

        return self::$descriptions;
    }
}
