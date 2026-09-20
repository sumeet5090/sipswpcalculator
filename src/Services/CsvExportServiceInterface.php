<?php

declare(strict_types=1);

namespace Services;

/**
 * CsvExportServiceInterface
 * Contract for raw CSV generation and formatting for investment schedules.
 */
interface CsvExportServiceInterface
{
    /**
     * Generate raw CSV content for investment schedule data.
     *
     * @param array $combined Results array from InvestmentCalculator
     * @param bool $enableSwp Whether SWP withdrawal columns should be included
     * @param string $currencySymbol Currency symbol to display in column headers
     */
    public function generate(array $combined, bool $enableSwp, string $currencySymbol = '₹'): string;
}
