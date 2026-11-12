<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Commands;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Data\SearchHit;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class SearchCommand extends Command
{
    protected $signature = 'companies-house:search
        {term? : Name to search for}
        {--advanced : Use advanced search with the filters below}
        {--status=* : Company status, e.g. active (advanced)}
        {--sic=* : SIC code (advanced)}
        {--location= : Town or postcode area (advanced)}
        {--incorporated-after= : YYYY-MM-DD (advanced)}
        {--incorporated-before= : YYYY-MM-DD (advanced)}
        {--limit=20 : Maximum results}';

    protected $description = 'Search Companies House by name, or with advanced filters';

    public function handle(CompaniesHouseClient $client): int
    {
        $client = $client->interactive();
        $term = $this->argument('term');
        $term = is_string($term) ? $term : '';
        $limit = max(1, (int) $this->option('limit'));

        try {
            $hits = $this->option('advanced')
                ? $this->advanced($client, $term, $limit)
                : $client->search($term, $limit);
        } catch (CompaniesHouseException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($hits->isEmpty()) {
            $this->components->warn('No companies found.');

            return self::SUCCESS;
        }

        $this->table(['Number', 'Name', 'Status', 'Incorporated', 'Address'], $hits->map(fn (SearchHit $hit): array => [
            $hit->number,
            $hit->name,
            $hit->status->label(),
            $hit->incorporatedOn?->toDateString() ?? '-',
            $hit->addressSnippet ?? '-',
        ])->all());

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, SearchHit>
     */
    private function advanced(CompaniesHouseClient $client, string $term, int $limit): Collection
    {
        $search = $client->advancedSearch();

        if ($term !== '') {
            $search->nameIncludes($term);
        }

        $statuses = array_filter((array) $this->option('status'), is_string(...));
        $sics = array_filter((array) $this->option('sic'), is_string(...));

        if ($statuses !== []) {
            $search->status(array_values($statuses));
        }

        if ($sics !== []) {
            $search->sic(array_values($sics));
        }

        foreach (['location' => 'location', 'incorporated-after' => 'incorporatedAfter', 'incorporated-before' => 'incorporatedBefore'] as $option => $method) {
            $value = $this->option($option);

            if (is_string($value) && $value !== '') {
                $search->{$method}($value);
            }
        }

        return $search->get($limit);
    }
}
