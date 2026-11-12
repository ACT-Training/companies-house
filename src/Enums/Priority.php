<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Enums;

/**
 * Interactive calls (a person waiting on a page) may use the whole rate
 * ceiling. Background calls (jobs, scheduled commands) stop at the job budget.
 */
enum Priority: string
{
    case Interactive = 'interactive';
    case Background = 'background';
}
