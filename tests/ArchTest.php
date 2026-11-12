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

arch('host-facing code depends on the contract, not the concrete client')
    ->expect('ACTTraining\CompaniesHouse\Client')
    ->toOnlyBeUsedIn([
        'ACTTraining\CompaniesHouse\CompaniesHouseServiceProvider',
    ]);
