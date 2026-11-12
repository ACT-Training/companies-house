<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Http;

use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Support\Payload;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\LazyCollection;

/**
 * Caching and paging over a transport. Resources call this, never the
 * transport directly.
 *
 * Only definitive answers are cached: a body, or a 404 (stored as a marker).
 * Exceptions pass straight through and are never cached.
 *
 * @internal
 */
final class Gateway
{
    private const string NOT_FOUND = '__companies_house_not_found';

    /**
     * @param  array<string, int>  $ttls  seconds, keyed by resource (company, search, officers, ...)
     */
    public function __construct(
        private readonly Transport $transport,
        private readonly ?Repository $cache = null,
        private readonly array $ttls = [],
        private readonly string $prefix = 'companies-house',
        private readonly Priority $priority = Priority::Interactive,
    ) {}

    public function withPriority(Priority $priority): self
    {
        return new self($this->transport, $this->cache, $this->ttls, $this->prefix, $priority);
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>|null
     */
    public function get(string $path, array $query = [], ?string $cacheAs = null): ?array
    {
        $ttl = $cacheAs === null ? 0 : ($this->ttls[$cacheAs] ?? 0);

        if ($this->cache === null || $ttl <= 0) {
            return $this->transport->get($path, $query, $this->priority);
        }

        ksort($query);
        $key = "{$this->prefix}:{$cacheAs}:" . sha1($path . '?' . http_build_query($query));

        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            return isset($cached[self::NOT_FOUND]) ? null : $cached;
        }

        $body = $this->transport->get($path, $query, $this->priority);

        $this->cache->put($key, $body ?? [self::NOT_FOUND => true], $ttl);

        return $body;
    }

    /**
     * Walk a paged endpoint lazily, one request per page, via start_index.
     *
     * @template T
     *
     * @param  array<string, scalar>  $query
     * @param  callable(array<array-key, mixed>): T  $map
     * @return LazyCollection<int, T>
     */
    public function paginate(
        string $path,
        array $query,
        callable $map,
        string $sizeParameter = 'items_per_page',
        int $pageSize = 100,
        string $totalKey = 'total_results',
        ?string $cacheAs = null,
        ?int $max = null,
    ): LazyCollection {
        return LazyCollection::make(function () use ($path, $query, $map, $sizeParameter, $pageSize, $totalKey, $cacheAs, $max) {
            $start = 0;

            while ($max === null || $start < $max) {
                $size = $max === null ? $pageSize : min($pageSize, $max - $start);
                $page = $this->get($path, $query + [$sizeParameter => $size, 'start_index' => $start], $cacheAs);
                $items = $page === null ? [] : Payload::items($page);

                foreach ($items as $item) {
                    yield $map($item);
                }

                $start += count($items);
                $total = $page === null ? null : Payload::int($page, $totalKey);

                if ($items === [] || ($total !== null && $start >= $total)) {
                    return;
                }
            }
        });
    }
}
