<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Commands;

use ACTTraining\CompaniesHouse\Contracts\Transport;
use ACTTraining\CompaniesHouse\Enums\Priority;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use ACTTraining\CompaniesHouse\Http\RateLimiter;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Checks the package can reach Companies House. Safe to run in deploy checks:
 * it shows only the last four characters of the key.
 */
final class DoctorCommand extends Command
{
    protected $signature = 'companies-house:doctor';

    protected $description = 'Check the Companies House key, reachability and rate budget';

    public function handle(Config $config, Transport $transport, RateLimiter $limiter): int
    {
        $key = $config->get('companies-house.api_key');
        $hasKey = is_string($key) && $key !== '';
        $rows = [['API key', $hasKey ? 'set, ending ' . substr($key, -4) : 'missing (set COMPANIES_HOUSE_API_KEY)', $hasKey]];

        $reachable = false;

        if ($hasKey) {
            try {
                $transport->get('/search/companies', ['q' => 'companies house', 'items_per_page' => 1], Priority::Interactive);
                $reachable = true;
                $rows[] = ['Reachability', 'Companies House answered', true];
            } catch (CompaniesHouseException $exception) {
                $rows[] = ['Reachability', $exception->getMessage(), false];
            }
        } else {
            $rows[] = ['Reachability', 'skipped: no key', false];
        }

        $ceiling = $config->get('companies-house.rate_limit.ceiling');
        $rows[] = [
            'Rate budget',
            sprintf(
                '%d of %s used this window; %d left for web, %d for jobs',
                $limiter->used(),
                is_numeric($ceiling) ? (string) $ceiling : '?',
                $limiter->remaining(Priority::Interactive),
                $limiter->remaining(Priority::Background),
            ),
            $limiter->remaining(Priority::Interactive) > 0,
        ];

        $rows[] = ['Mirror', $config->get('companies-house.mirror.enabled') ? 'enabled, but not built yet (phase 2)' : 'not enabled', null];
        $rows[] = ['Postcode table', 'not built yet (phase 2)', null];

        $this->table(['Check', 'Result', ''], array_map(
            fn (array $row): array => [$row[0], $row[1], match ($row[2]) {
                true => '<info>OK</info>',
                false => '<error>FAIL</error>',
                null => '-',
            }],
            $rows,
        ));

        return $hasKey && $reachable ? self::SUCCESS : self::FAILURE;
    }
}
