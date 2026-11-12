<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Http;

use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\InvalidRequest;
use ACTTraining\CompaniesHouse\Exceptions\RateLimited;
use ACTTraining\CompaniesHouse\Exceptions\Unauthorised;
use ACTTraining\CompaniesHouse\Exceptions\Unavailable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Talks to the Companies House REST API with Laravel's HTTP client.
 *
 * The key is sent as the basic auth username. It is never put in an
 * exception message: every failure is rethrown as a package exception
 * with a message written here.
 */
final class HttpTransport implements Transport
{
    public function __construct(
        private readonly Factory $http,
        private readonly RateLimiter $limiter,
        private readonly ?string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout = 8,
    ) {}

    public function get(string $path, array $query, Priority $priority): ?array
    {
        $request = $this->request();

        $this->limiter->attempt($priority);

        try {
            $response = $request->get($path, $query);
        } catch (ConnectionException $exception) {
            throw Unavailable::because('the connection failed or timed out', $exception);
        } catch (Throwable $exception) {
            throw Unavailable::because('the request could not be sent', $exception);
        }

        $this->observe($response);

        return $this->interpret($response);
    }

    public function request(): PendingRequest
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw Unauthorised::missingKey();
        }

        return $this->http
            ->baseUrl($this->baseUrl)
            ->withBasicAuth($this->apiKey, '')
            ->timeout($this->timeout)
            ->acceptJson()
            ->retry(2, 250, function (Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            }, throw: false);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function interpret(Response $response): ?array
    {
        $status = $response->status();

        if ($status === 404) {
            return null;
        }

        if ($status === 401 || $status === 403) {
            throw Unauthorised::rejected();
        }

        if ($status === 429) {
            $resetsAt = $this->resetTime($response) ?? $this->limiter->resetsAt();
            $this->limiter->blockUntil($resetsAt);

            throw new RateLimited($resetsAt);
        }

        if ($response->serverError()) {
            throw Unavailable::because("Companies House returned status {$status}");
        }

        if (! $response->successful()) {
            throw InvalidRequest::status($status);
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw Unavailable::because('the response was not valid JSON');
        }

        return $body;
    }

    private function observe(Response $response): void
    {
        $this->limiter->observe(
            $this->intHeader($response, 'X-Ratelimit-Limit'),
            $this->intHeader($response, 'X-Ratelimit-Remain'),
            $this->intHeader($response, 'X-Ratelimit-Reset'),
        );
    }

    private function resetTime(Response $response): ?CarbonImmutable
    {
        $epoch = $this->intHeader($response, 'X-Ratelimit-Reset');

        return $epoch === null ? null : CarbonImmutable::createFromTimestamp($epoch);
    }

    private function intHeader(Response $response, string $name): ?int
    {
        $value = $response->header($name);

        return is_numeric($value) ? (int) $value : null;
    }
}
