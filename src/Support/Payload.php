<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Typed reads from decoded Companies House JSON, which is untyped.
 *
 * @internal
 */
final class Payload
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return is_int($value) ? (string) $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function bool(array $data, string $key): bool
    {
        return ($data[$key] ?? false) === true;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function date(array $data, string $key): ?CarbonImmutable
    {
        $value = self::string($data, $key);

        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (Throwable) {
            // Deliberate: an unparseable date from Companies House is treated
            // as missing rather than failing the whole profile.
            return null;
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * A list of arrays, dropping anything that is not an array.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<array<array-key, mixed>>
     */
    public static function items(array $data, string $key = 'items'): array
    {
        return array_values(array_filter(self::array($data, $key), is_array(...)));
    }

    /**
     * A list of strings, dropping anything that is not a non-empty string.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        return array_values(array_filter(
            self::array($data, $key),
            fn (mixed $value): bool => is_string($value) && $value !== '',
        ));
    }
}
