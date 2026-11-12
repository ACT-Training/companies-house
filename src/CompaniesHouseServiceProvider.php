<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse;

use ACTTraining\CompaniesHouse\Commands\DoctorCommand;
use ACTTraining\CompaniesHouse\Commands\LookupCommand;
use ACTTraining\CompaniesHouse\Commands\SearchCommand;
use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Http\Gateway;
use ACTTraining\CompaniesHouse\Http\HttpTransport;
use ACTTraining\CompaniesHouse\Http\RateLimiter;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class CompaniesHouseServiceProvider extends ServiceProvider
{
    /**
     * Officer and PSC data is personal data: never cached for longer than this.
     */
    public const int PERSONAL_DATA_MAX_TTL = 3600;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/companies-house.php', 'companies-house');

        $this->app->singleton(RateLimiter::class, function (Application $app): RateLimiter {
            $config = $app->make(Config::class);

            return new RateLimiter(
                $this->cacheStore($app),
                $this->int($config->get('companies-house.rate_limit.ceiling'), 500),
                $this->int($config->get('companies-house.rate_limit.job_budget'), 300),
                $this->int($config->get('companies-house.rate_limit.window_seconds'), 300),
                $this->string($config->get('companies-house.cache.prefix'), 'companies-house'),
            );
        });

        $this->app->singleton(Transport::class, function (Application $app): HttpTransport {
            $config = $app->make(Config::class);
            $key = $config->get('companies-house.api_key');

            return new HttpTransport(
                $app->make(Factory::class),
                $app->make(RateLimiter::class),
                is_string($key) ? $key : null,
                $this->string($config->get('companies-house.base_url'), 'https://api.company-information.service.gov.uk'),
                $this->int($config->get('companies-house.timeout'), 8),
            );
        });

        $this->app->singleton(Gateway::class, function (Application $app): Gateway {
            $config = $app->make(Config::class);

            return new Gateway(
                $app->make(Transport::class),
                $this->cacheStore($app),
                $this->ttls($config),
                $this->string($config->get('companies-house.cache.prefix'), 'companies-house'),
                $app->runningInConsole() && ! $app->runningUnitTests() ? Priority::Background : Priority::Interactive,
            );
        });

        $this->app->singleton(CompaniesHouseClient::class, fn (Application $app): Client => new Client($app->make(Gateway::class)));

        $this->app->alias(CompaniesHouseClient::class, 'companies-house');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/companies-house.php' => config_path('companies-house.php'),
            ], 'companies-house-config');

            $this->commands([
                DoctorCommand::class,
                LookupCommand::class,
                SearchCommand::class,
            ]);
        }
    }

    private function cacheStore(Application $app): Repository
    {
        $store = $app->make(Config::class)->get('companies-house.cache.store');

        return $app->make(CacheManager::class)->store(is_string($store) && $store !== '' ? $store : null);
    }

    /**
     * @return array<string, int>
     */
    private function ttls(Config $config): array
    {
        $ttls = [];
        $configured = $config->get('companies-house.cache.ttl', []);

        foreach (is_array($configured) ? $configured : [] as $resource => $seconds) {
            $ttls[(string) $resource] = $this->int($seconds, 0);
        }

        foreach (['officers', 'psc'] as $personal) {
            $ttls[$personal] = min($ttls[$personal] ?? self::PERSONAL_DATA_MAX_TTL, self::PERSONAL_DATA_MAX_TTL);
        }

        return $ttls;
    }

    private function int(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private function string(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }
}
