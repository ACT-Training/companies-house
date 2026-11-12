<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Contracts;

use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;

/**
 * Sends one GET to Companies House. Swapped for an in-memory transport by
 * CompaniesHouse::fake().
 */
interface Transport
{
    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>|null the decoded body, or null on a 404
     *
     * @throws CompaniesHouseException
     */
    public function get(string $path, array $query, Priority $priority): ?array;
}
