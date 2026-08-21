<?php

declare(strict_types=1);

namespace Services;

/**
 * CsvExportService
 * Handles raw CSV generation and output stream delivery for investment schedule data.
 */
class CsvExportService
{
    /**
     * Generate raw CSV content for investment schedule data.
     *
     * @param array $combined Results array from InvestmentCalculator
     * @param bool $enableSwp Whether SWP withdrawal columns should be included
     * @param string $currencySymbol Currency symbol to display in column headers
     */
    public function generate(array $combined, bool $enableSwp, string $currencySymbol = '₹'): string
    {
        $resource = fopen('php://temp', 'r+');
        if ($resource === false) {
            throw new \RuntimeException('Failed to allocate stream resource for CSV export.');
        }

        $sym = trim($currencySymbol) !== '' ? trim($currencySymbol) : '₹';
        $hasTaxData = !empty($combined) && isset($combined[0]['ltcg_tax']);

        $headers = [
            'Year',
            "Begin Balance ({$sym})",
            "Monthly SIP ({$sym})",
            "Annual Contribution ({$sym})",
            "Cumulative Invested ({$sym})"
        ];
        if ($enableSwp) {
            $headers[] = "Monthly SWP ({$sym})";
            $headers[] = "Annual Withdrawal ({$sym})";
            $headers[] = "Cumulative Withdrawals ({$sym})";
        }
        $headers[] = "Interest Earned ({$sym})";
        $headers[] = "End Balance ({$sym})";
        if ($hasTaxData) {
            $headers[] = "Est. LTCG Tax ({$sym})";
            $headers[] = "Post-Tax Balance ({$sym})";
        }

        // Prepend UTF-8 BOM for Microsoft Excel compatibility
        fwrite($resource, "\xEF\xBB\xBF");

        fputcsv($resource, $headers, ',', '"', '');

        foreach ($combined as $row) {
            $csvRow = [
                $this->sanitizeCsvCell($row['year'] ?? 0),
                $this->sanitizeCsvCell($row['begin_balance'] ?? 0),
                $this->sanitizeCsvCell($row['sip_monthly'] ?? 0),
                $this->sanitizeCsvCell($row['annual_contribution'] ?? 0),
                $this->sanitizeCsvCell($row['cumulative_invested'] ?? 0)
            ];
            if ($enableSwp) {
                $csvRow[] = $this->sanitizeCsvCell($row['swp_monthly'] ?? 0);
                $csvRow[] = $this->sanitizeCsvCell($row['annual_withdrawal'] ?? 0);
                $csvRow[] = $this->sanitizeCsvCell($row['cumulative_withdrawals'] ?? 0);
            }
            $csvRow[] = $this->sanitizeCsvCell($row['interest'] ?? 0);
            $csvRow[] = $this->sanitizeCsvCell($row['combined_total'] ?? 0);
            if ($hasTaxData) {
                $csvRow[] = $this->sanitizeCsvCell($row['ltcg_tax'] ?? 0);
                $csvRow[] = $this->sanitizeCsvCell($row['post_tax_total'] ?? ($row['combined_total'] ?? 0));
            }
            fputcsv($resource, $csvRow, ',', '"', '');
        }

        rewind($resource);
        $csvContent = stream_get_contents($resource);
        fclose($resource);

        return is_string($csvContent) ? $csvContent : '';
    }

    private function sanitizeCsvCell(mixed $value): string
    {
        $str = (string) $value;
        if ($str === '') {
            return '';
        }
        $trimmed = ltrim($str);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r", '|'], true)) {
            if (is_numeric($str) && $str[0] !== '+' && $str[0] !== '=') {
                return $str;
            }
            return "'" . $str;
        }
        return $str;
    }
}
