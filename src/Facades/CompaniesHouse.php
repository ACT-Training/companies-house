<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Facades;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingRequest request()
 *
 * @see CompaniesHouseClient
 */
class CompaniesHouse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CompaniesHouseClient::class;
    }
}
