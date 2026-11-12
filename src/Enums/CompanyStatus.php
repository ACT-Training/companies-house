<?php

declare(strict_types=1);

namespace ACTTraining\CompaniesHouse\Enums;

/**
 * Values of company_status in the Companies House API.
 */
enum CompanyStatus: string
{
    case Active = 'active';
    case Dissolved = 'dissolved';
    case Liquidation = 'liquidation';
    case Receivership = 'receivership';
    case Administration = 'administration';
    case VoluntaryArrangement = 'voluntary-arrangement';
    case ConvertedClosed = 'converted-closed';
    case InsolvencyProceedings = 'insolvency-proceedings';
    case Registered = 'registered';
    case Removed = 'removed';
    case Closed = 'closed';
    case Open = 'open';
    case Unknown = 'unknown';

    public static function fromApi(?string $value): self
    {
        return $value === null ? self::Unknown : (self::tryFrom($value) ?? self::Unknown);
    }

    public function isInsolvent(): bool
    {
        return in_array($this, [
            self::Liquidation,
            self::Receivership,
            self::Administration,
            self::VoluntaryArrangement,
            self::InsolvencyProceedings,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::VoluntaryArrangement => 'Voluntary arrangement',
            self::ConvertedClosed => 'Converted or closed',
            self::InsolvencyProceedings => 'Insolvency proceedings',
            default => ucfirst($this->value),
        };
    }
}
