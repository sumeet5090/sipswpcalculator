<?php

declare(strict_types=1);

namespace Core;

/**
 * PdfReportTableBuilder
 * Builds clean, canonical, and mathematically verified tabular cashflow ledgers
 * directly from calculated server schedules for institutional PDF generation.
 */
class PdfReportTableBuilder
{
    private CurrencyFormatterInterface $formatter;

    public function __construct(CurrencyFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Build clean, semantic HTML table for Dompdf rendering.
     *
     * @param array<int, array<string, mixed>> $schedule Yearly breakdown rows
     * @param string $currencySymbol
     * @param bool $hasSwp
     * @return string
     */
    public function build(array $schedule, string $currencySymbol = '₹', bool $hasSwp = false): string
    {
        if (empty($schedule)) {
            return '<table class="breakdown-table"><tbody><tr><td style="text-align:center; padding: 12px; color: #64748b;">No cashflow data available.</td></tr></tbody></table>';
        }

        $html = '<table class="breakdown-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="text-align: left; width: 12%;">Year</th>';
        $html .= '<th style="text-align: right;">Annual Invested</th>';
        $html .= '<th style="text-align: right;">Total Invested</th>';

        if ($hasSwp) {
            $html .= '<th style="text-align: right;">Annual Payout</th>';
            $html .= '<th style="text-align: right;">Total Payout</th>';
        }

        $html .= '<th style="text-align: right;">Annual Gain</th>';
        $html .= '<th style="text-align: right;">Year-End Corpus</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($schedule as $row) {
            $year = (int) ($row['year'] ?? 0);
            if ($year === 0 && count($schedule) > 1) {
                continue; // Skip year 0 initialization row if multi-year schedule exists
            }

            $annualContrib = (float) ($row['annual_contribution'] ?? 0);
            $totalInvested = (float) ($row['cumulative_invested'] ?? 0);
            $annualWithdrawal = (float) ($row['annual_withdrawal'] ?? 0);
            $totalWithdrawn = (float) ($row['cumulative_withdrawals'] ?? 0);
            $annualInterest = (float) ($row['interest'] ?? 0);
            $corpus = (float) ($row['combined_total'] ?? 0);

            $html .= '<tr>';
            $html .= '<td style="text-align: left; font-weight: bold; color: #0f172a;">Year ' . $year . '</td>';
            $html .= '<td style="text-align: right;">' . htmlspecialchars($this->formatter->format($annualContrib)) . '</td>';
            $html .= '<td style="text-align: right;">' . htmlspecialchars($this->formatter->format($totalInvested)) . '</td>';

            if ($hasSwp) {
                $html .= '<td style="text-align: right; color: #e11d48;">' . ($annualWithdrawal > 0 ? htmlspecialchars($this->formatter->format($annualWithdrawal)) : '—') . '</td>';
                $html .= '<td style="text-align: right; color: #e11d48;">' . ($totalWithdrawn > 0 ? htmlspecialchars($this->formatter->format($totalWithdrawn)) : '—') . '</td>';
            }

            $html .= '<td style="text-align: right; color: #059669;">' . htmlspecialchars($this->formatter->format($annualInterest)) . '</td>';
            $html .= '<td style="text-align: right; font-weight: bold; color: #0f172a;">' . htmlspecialchars($this->formatter->format($corpus)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
