<?php

declare(strict_types=1);

namespace Services\Reports;

use Core\Inputs\EmiInputs;
use Core\Inputs\InflationInputs;
use Core\Math\EmiEngine;
use Core\Math\InflationEngine;

/**
 * Report handler for Loan EMI Amortization and Inflation Purchasing Power calculators.
 */
final class LoanAndInflationReportHandler extends AbstractSpecializedReportHandler
{
    public function getSupportedTypes(): array
    {
        return ['emi', 'inflation'];
    }

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string
    {
        $ext = ltrim($extension, '.');
        return match ($calculatorType) {
            'emi'       => "Loan_EMI_Amortization_Schedule.{$ext}",
            'inflation' => "Inflation_Purchasing_Power_Report.{$ext}",
            default     => "SIP_SWP_Yearly_Report.{$ext}",
        };
    }

    public function buildCsv(string $calculatorType, array $post, string $symbol): array
    {
        $filename = $this->resolveFilename($calculatorType, 'csv');

        if ($calculatorType === 'emi') {
            $inputs = $this->parseEmiInputs($post);
            $res = (new EmiEngine())->calculate(
                $inputs->getPrincipal(),
                $inputs->getRate(),
                $inputs->getYears()
            );

            $rows = [];
            foreach ($res['schedule'] as $row) {
                $rows[] = [
                    $row['year'],
                    $row['opening_balance'],
                    round((float) $res['monthly_emi'], 2),
                    $row['principal_paid'],
                    $row['interest_paid'],
                    $row['total_paid'],
                    $row['closing_balance'],
                ];
            }

            return [
                'headers'  => [
                    'Year',
                    "Opening Loan Balance ({$symbol})",
                    "Monthly EMI ({$symbol})",
                    "Principal Paid ({$symbol})",
                    "Interest Paid ({$symbol})",
                    "Total Annual Paid ({$symbol})",
                    "Closing Loan Balance ({$symbol})",
                ],
                'rows'     => $rows,
                'filename' => $filename,
            ];
        }

        $inputs = $this->parseInflationInputs($post);
        $res = (new InflationEngine())->calculate(
            $inputs->getAmount(),
            $inputs->getRate(),
            $inputs->getYears()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $res['present_value'],
                $row['future_cost'],
                $row['purchasing_power'],
                $row['purchasing_power_loss_percentage'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                "Today's Cost ({$symbol})",
                "Inflation-Adjusted Cost ({$symbol})",
                "Real Purchasing Power ({$symbol})",
                'Purchasing Power Loss (%)',
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    public function buildPdfContext(string $calculatorType, array $post, string $symbol): array
    {
        if ($calculatorType === 'emi') {
            $inputs = $this->parseEmiInputs($post);
            $res = (new EmiEngine())->calculate($inputs->getPrincipal(), $inputs->getRate(), $inputs->getYears());
            $totalPayment = (float) $res['total_amount_payable'];

            $headers = ['Year', 'Opening Balance', 'Principal Paid', 'Interest Paid', 'Total Annual Outflow', 'Closing Balance'];
            $rows = array_map(fn($r) => [
                'Year ' . $r['year'],
                $this->formatMoney($r['opening_balance'], $symbol),
                $this->formatMoney($r['principal_paid'], $symbol),
                $this->formatMoney($r['interest_paid'], $symbol),
                $this->formatMoney($r['total_paid'], $symbol),
                $this->formatMoney($r['closing_balance'], $symbol),
            ], $res['schedule']);

            return [
                'years'                 => $inputs->getYears(),
                'has_swp'               => true,
                'doc_title'             => 'Loan EMI Amortization Report',
                'doc_subtitle'          => 'Reducing-Balance Repayment Schedule & Interest Cost Audit',
                'proposal_prefix'       => 'EMI-',
                'summary_invested'      => $this->formatMoney($res['principal'], $symbol),
                'summary_interest'      => $this->formatMoney($res['total_interest'], $symbol),
                'summary_withdrawn'     => $this->formatMoney($totalPayment, $symbol),
                'summary_corpus'        => $this->formatMoney(round((float) $res['monthly_emi']), $symbol) . ' / mo',
                'raw_invested'          => $res['principal'],
                'raw_corpus'            => $totalPayment,
                'raw_withdrawn'         => 0.0,
                'multiplier_override'   => sprintf('%.1f%%', $res['interest_ratio_percentage']),
                'kpi_labels'            => [
                    'invested'   => 'Loan Amount',
                    'returns'    => 'Total Interest',
                    'withdrawn'  => 'Total Payment',
                    'corpus'     => 'Monthly EMI',
                    'multiplier' => 'Interest / Principal',
                ],
                'config_badge'          => 'Loan Facility Parameters',
                'config_rows'           => [
                    ['l1' => 'Loan Principal:', 'v1' => $this->formatMoney($res['principal'], $symbol), 'l2' => 'Interest Rate:', 'v2' => $res['annual_rate'] . '% p.a.'],
                    ['l1' => 'Loan Tenure:', 'v1' => $res['tenure_years'] . ' Years (' . ($res['tenure_years'] * 12) . ' EMIs)', 'l2' => 'Monthly EMI:', 'v2' => $this->formatMoney(round((float) $res['monthly_emi']), $symbol)],
                ],
                'has_inflation_callout' => false,
                'table_html'            => $this->buildHtmlTable($headers, $rows),
            ];
        }

        $inputs = $this->parseInflationInputs($post);
        $res = (new InflationEngine())->calculate($inputs->getAmount(), $inputs->getRate(), $inputs->getYears());

        $headers = ['Year', "Today's Cost", 'Future Living Cost', 'Real Purchasing Power', 'Power Lost'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            $this->formatMoney($res['present_value'], $symbol),
            $this->formatMoney($r['future_cost'], $symbol),
            $this->formatMoney($r['purchasing_power'], $symbol),
            sprintf('%.1f%%', $r['purchasing_power_loss_percentage']),
        ], $res['schedule']);

        return [
            'years'                 => $inputs->getYears(),
            'doc_title'             => 'Inflation & Purchasing Power Report',
            'doc_subtitle'          => 'Future Expense Escalation & CPI Erosion Analysis',
            'proposal_prefix'       => 'INF-',
            'summary_invested'      => $this->formatMoney($res['present_value'], $symbol),
            'summary_interest'      => $this->formatMoney($res['cost_increase'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['purchasing_power'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['future_cost'], $symbol),
            'raw_invested'          => $res['present_value'],
            'raw_corpus'            => $res['future_cost'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => sprintf('-%.1f%%', $res['purchasing_power_loss_percentage']),
            'kpi_labels'            => [
                'invested'   => "Today's Cost",
                'returns'    => 'Cost Escalation',
                'corpus'     => 'Future Living Cost',
                'multiplier' => 'Value Erosion',
            ],
            'config_badge'          => 'Inflation Projection Parameters',
            'config_rows'           => [
                ['l1' => 'Present Cost:', 'v1' => $this->formatMoney($res['present_value'], $symbol), 'l2' => 'Annual Inflation Rate:', 'v2' => $res['inflation_rate'] . '% p.a.'],
                ['l1' => 'Time Horizon:', 'v1' => $res['years'] . ' Years', 'l2' => 'Real Power Remaining:', 'v2' => $this->formatMoney($res['purchasing_power'], $symbol)],
            ],
            'has_inflation_callout' => true,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseEmiInputs(array $post): EmiInputs
    {
        return new EmiInputs(
            max(1000.0, $this->readFloat($post, 'emi_principal', 3000000.0)),
            max(0.1, $this->readFloat($post, 'emi_rate', 8.5)),
            max(1, $this->readInt($post, 'emi_years', 20))
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseInflationInputs(array $post): InflationInputs
    {
        return new InflationInputs(
            max(1.0, $this->readFloat($post, 'inf_amount', 50000.0)),
            max(0.0, $this->readFloat($post, 'inf_rate', 6.0)),
            max(1, $this->readInt($post, 'inf_years', 15))
        );
    }
}
