<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class CompaniesHouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/companies-house.php', 'companies-house');

        $this->app->singleton(CompaniesHouseClient::class, function (Application $app): Client {
            /** @var array<string, mixed> $config */
            $config = $app->make(Repository::class)->get('companies-house', []);

            return new Client($app->make(Factory::class), $config);
        });

        $this->app->alias(CompaniesHouseClient::class, 'companies-house');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/companies-house.php' => config_path('companies-house.php'),
            ], 'companies-house-config');
        }
    }
}
