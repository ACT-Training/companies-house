<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Commands;

use ACTTraining\CompaniesHouse\Contracts\CompaniesHouseClient;
use ACTTraining\CompaniesHouse\Data\SicCode;
use ACTTraining\CompaniesHouse\Exceptions\CompaniesHouseException;
use Illuminate\Console\Command;

final class LookupCommand extends Command
{
    protected $signature = 'companies-house:lookup
        {number : The company number, e.g. 04567890 or SC123456}
        {--json : Print the profile as JSON}';

    protected $description = 'Print a Companies House company profile';

    public function handle(CompaniesHouseClient $client): int
    {
        $number = $this->argument('number');

        try {
            $company = $client->interactive()->find(is_string($number) ? $number : '');
        } catch (CompaniesHouseException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($company === null) {
            $this->components->warn("Companies House has no company {$number}.");

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($company->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['Number', $company->number],
            ['Name', $company->name],
            ['Status', $company->status->label() . ($company->statusDetail ? " ({$company->statusDetail})" : '')],
            ['Type', $company->type ?? '-'],
            ['Jurisdiction', $company->jurisdiction ?? '-'],
            ['Incorporated', $company->incorporatedOn?->toDateString() ?? '-'],
            ['Ceased', $company->ceasedOn?->toDateString() ?? '-'],
            ['Registered office', $company->registeredOffice?->oneLine() ?? '-'],
            ['SIC codes', $company->sicCodes->map(fn (SicCode $sic): string => trim("{$sic->code} {$sic->description}"))->implode("\n") ?: '-'],
            ['Accounts due', ($company->accountsNextDue?->toDateString() ?? '-') . ($company->accountsOverdue ? ' (overdue)' : '')],
            ['Confirmation due', ($company->confirmationStatementNextDue?->toDateString() ?? '-') . ($company->confirmationStatementOverdue ? ' (overdue)' : '')],
            ['Insolvency history', $company->hasInsolvencyHistory ? 'yes' : 'no'],
            ['Charges', $company->hasCharges ? 'yes' : 'no'],
        ]);

        return self::SUCCESS;
    }
}
