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
     * Generate and output the CSV file directly to php://output stream and terminate request.
     *
     * @param array $combined Results array from InvestmentCalculator
     * @param bool $enableSwp Whether SWP withdrawal columns should be included
     */
    public function export(array $combined, bool $enableSwp): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=SIP_SWP_Yearly_Report.csv');
        $output = fopen('php://output', 'w');

        $headers = ['Year', 'Begin Balance (₹)', 'Monthly SIP (₹)', 'Annual Contribution (₹)', 'Cumulative Invested (₹)'];
        if ($enableSwp) {
            $headers[] = 'Monthly SWP (₹)';
            $headers[] = 'Annual Withdrawal (₹)';
            $headers[] = 'Cumulative Withdrawals (₹)';
        }
        $headers[] = 'Interest Earned (₹)';
        $headers[] = 'End Balance (₹)';

        fputcsv($output, $headers, ',', '"', '\\');

        foreach ($combined as $row) {
            $csvRow = [
                $row['year'],
                $row['begin_balance'],
                $row['sip_monthly'] !== null ? $row['sip_monthly'] : 0,
                $row['annual_contribution'],
                $row['cumulative_invested']
            ];
            if ($enableSwp) {
                $csvRow[] = $row['swp_monthly'] !== null ? $row['swp_monthly'] : 0;
                $csvRow[] = $row['annual_withdrawal'] !== null ? $row['annual_withdrawal'] : 0;
                $csvRow[] = $row['cumulative_withdrawals'];
            }
            $csvRow[] = $row['interest'];
            $csvRow[] = $row['combined_total'];
            fputcsv($output, $csvRow, ',', '"', '\\');
        }
        fclose($output);
        exit();
    }
}
