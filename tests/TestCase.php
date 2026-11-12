<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Tests;

use ACTTraining\CompaniesHouse\CompaniesHouseServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [CompaniesHouseServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('companies-house.api_key', 'test-key');
    }
}
