<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Exceptions\Unauthorised;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

final class Client implements CompaniesHouseClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function request(): PendingRequest
    {
        $key = $this->config['api_key'] ?? null;

        if (! is_string($key) || $key === '') {
            throw Unauthorised::missingKey();
        }

        $baseUrl = $this->config['base_url'] ?? '';
        $timeout = $this->config['timeout'] ?? 8;

        return $this->http
            ->baseUrl(is_string($baseUrl) ? $baseUrl : '')
            ->withBasicAuth($key, '')
            ->timeout(is_int($timeout) ? $timeout : 8)
            ->acceptJson();
    }
}
