<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API access
    |--------------------------------------------------------------------------
    |
    | The key is sent as the HTTP basic auth username with a blank password.
    | It must never appear in logs or exception messages.
    |
    */

    'api_key' => env('COMPANIES_HOUSE_API_KEY'),

    'base_url' => env('COMPANIES_HOUSE_BASE_URL', 'https://api.company-information.service.gov.uk'),

    'timeout' => (int) env('COMPANIES_HOUSE_TIMEOUT', 8),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Companies House allows 600 requests per 5 minutes per key. The ceiling
    | leaves headroom; the job budget is the share bulk jobs may use, so that
    | interactive lookups always have room.
    |
    */

    'rate_limit' => [
        'ceiling' => (int) env('COMPANIES_HOUSE_RATE_CEILING', 500),
        'job_budget' => (int) env('COMPANIES_HOUSE_JOB_BUDGET', 300),
        'window_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Only definitive answers (found, or a 404) are cached. Failures never are.
    | Times are in seconds. Officer and PSC times are capped at one hour.
    |
    */

    'cache' => [
        'store' => env('COMPANIES_HOUSE_CACHE_STORE'),
        'prefix' => 'companies-house',
        'ttl' => [
            'company' => (int) env('COMPANIES_HOUSE_CACHE_TTL_COMPANY', 60 * 60 * 24),
            'search' => (int) env('COMPANIES_HOUSE_CACHE_TTL_SEARCH', 60 * 60),
            'officers' => (int) env('COMPANIES_HOUSE_CACHE_TTL_OFFICERS', 60 * 60),
            'filings' => (int) env('COMPANIES_HOUSE_CACHE_TTL_FILINGS', 60 * 60),
            'insolvency' => (int) env('COMPANIES_HOUSE_CACHE_TTL_INSOLVENCY', 60 * 60 * 24),
            'charges' => (int) env('COMPANIES_HOUSE_CACHE_TTL_CHARGES', 60 * 60 * 24),
            'psc' => (int) env('COMPANIES_HOUSE_CACHE_TTL_PSC', 60 * 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Local mirror (phase 2)
    |--------------------------------------------------------------------------
    |
    | Countries are ONSPD country codes, comma-separated. W92000004 is Wales.
    |
    */

    'mirror' => [
        'enabled' => (bool) env('COMPANIES_HOUSE_MIRROR_ENABLED', false),
        'countries' => array_filter(explode(',', (string) env('COMPANIES_HOUSE_MIRROR_COUNTRIES', 'W92000004'))),
        'min_row_ratio' => 0.9,
    ],

    'table_prefix' => env('COMPANIES_HOUSE_TABLE_PREFIX', 'ch_'),

    /*
    |--------------------------------------------------------------------------
    | Streaming API (phase 4, reserved)
    |--------------------------------------------------------------------------
    */

    'stream_key' => env('COMPANIES_HOUSE_STREAM_KEY'),

];
