<?php

declare(strict_types=1);

arch('source files use strict types')
    ->expect('ACTTraining\CompaniesHouse')
    ->toUseStrictTypes();

arch('no debugging calls are left in')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

arch('exceptions extend the package base exception')
    ->expect('ACTTraining\CompaniesHouse\Exceptions')
    ->classes()
    ->toExtend('ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException')
    ->ignoring('ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException');

arch('data objects are readonly')
    ->expect('ACTTraining\CompaniesHouse\Data')
    ->toBeReadonly();

arch('nothing logs, so officer data and the key cannot leak into logs')
    ->expect('ACTTraining\CompaniesHouse')
    ->not->toUse(['Illuminate\Support\Facades\Log', 'Psr\Log\LoggerInterface', 'logger', 'info']);

arch('resources reach Companies House only through the gateway')
    ->expect('ACTTraining\CompaniesHouse\Resources')
    ->not->toUse(['Illuminate\Support\Facades\Http', 'Illuminate\Http\Client\Factory', 'ACTTraining\CompaniesHouse\Http\HttpTransport']);

arch('host-facing code depends on the contract, not the concrete client')
    ->expect('ACTTraining\CompaniesHouse\Client')
    ->toOnlyBeUsedIn([
        'ACTTraining\CompaniesHouse\CompaniesHouseServiceProvider',
        'ACTTraining\CompaniesHouse\Testing',
    ]);
