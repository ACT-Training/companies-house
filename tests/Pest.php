<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature', 'Live');

/**
 * A recorded (or synthetic) Companies House response from tests/Fixtures.
 *
 * @return array<array-key, mixed>
 */
function apiFixture(string $name): array
{
    $json = file_get_contents(__DIR__ . "/Fixtures/{$name}.json");

    return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Companies House's rate limit headers.
 *
 * @return array<string, string>
 */
function rateHeaders(int $remain = 599, int $limit = 600, ?int $reset = null): array
{
    return [
        'X-Ratelimit-Limit' => (string) $limit,
        'X-Ratelimit-Remain' => (string) $remain,
        'X-Ratelimit-Reset' => (string) ($reset ?? time() + 300),
        'X-Ratelimit-Window' => '5m',
    ];
}
