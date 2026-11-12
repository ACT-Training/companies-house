<?php

declare(strict_types=1);

use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Exceptions\InvalidRequest;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Exceptions\Unauthorised;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;

it('flags which failures are worth retrying', function (CompaniesHouseException $exception, bool $retryable): void {
    expect($exception->retryable())->toBe($retryable);
})->with([
    'unauthorised' => [Unauthorised::rejected(), false],
    'invalid request' => [InvalidRequest::status(400), false],
    'rate limited' => [new RateLimited, true],
    'unavailable' => [Unavailable::because('timeout'), true],
]);
