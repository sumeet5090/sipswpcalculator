<?php

declare(strict_types=1);

namespace Services;

use Core\CurrencyFormatterInterface;
use Core\CurrencyHelper;
use Core\Inputs\CagrInputs;
use Core\Inputs\CompoundInterestInputs;
use Core\Inputs\EmiInputs;
use Core\Inputs\FdInputs;
use Core\Inputs\InflationInputs;
use Core\Inputs\PpfInputs;
use Core\Math\CagrEngine;
use Core\Math\CompoundInterestEngine;
use Core\Math\EmiEngine;
use Core\Math\FdEngine;
use Core\Math\InflationEngine;
use Core\Math\PpfEngine;

/**
 * SpecializedReportBuilder
 * Builds mode-accurate CSV datasets and PDF report context arrays for the 6 specialized engines
 * (Compound Interest, CAGR, EMI, Inflation, PPF, FD) so every calculator honors its export promise.
 */
class SpecializedReportBuilder
{
    private const SPECIALIZED_MODES = [
        'compound_interest',
        'cagr',
        'emi',
        'inflation',
        'ppf',
        'fd',
    ];

    private CurrencyFormatterInterface $currencyFormatter;

    public function __construct(?CurrencyFormatterInterface $currencyFormatter = null)
    {
        $this->currencyFormatter = $currencyFormatter ?? new CurrencyHelper();
    }

    public function isSpecialized(string $calculatorType): bool
    {
        return in_array($calculatorType, self::SPECIALIZED_MODES, true);
    }

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string
    {
        $ext = ltrim($extension, '.');
        return match ($calculatorType) {
            'compound_interest' => "Compound_Interest_Schedule.{$ext}",
            'cagr'              => "CAGR_Yearly_Report.{$ext}",
            'emi'               => "Loan_EMI_Amortization_Schedule.{$ext}",
            'inflation'         => "Inflation_Purchasing_Power_Report.{$ext}",
            'ppf'               => "PPF_Maturity_Schedule.{$ext}",
            'fd'                => "FD_Maturity_Schedule.{$ext}",
            default             => "SIP_SWP_Yearly_Report.{$ext}",
        };
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}|null
     */
    public function buildCsv(string $calculatorType, array $post, string $sym): ?array
    {
        if (!$this->isSpecialized($calculatorType)) {
            return null;
        }

        $symbol = trim($sym) !== '' ? trim($sym) : '₹';
        $filename = $this->resolveFilename($calculatorType, 'csv');

        if ($calculatorType === 'compound_interest') {
            $inputs = $this->parseCompoundInterestInputs($post);
            $res = (new CompoundInterestEngine())->calculate(
                $inputs->getPrincipal(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getFrequency()
            );

            $rows = [];
            foreach ($res['schedule'] as $row) {
                $rows[] = [
                    $row['year'],
                    $row['opening_balance'],
                    $row['interest_earned'],
                    $row['closing_balance'],
                ];
            }

            return [
                'headers'  => ['Year', "Opening Balance ({$symbol})", "Interest Earned ({$symbol})", "Closing Balance ({$symbol})"],
                'rows'     => $rows,
                'filename' => $filename,
            ];
        }

        if ($calculatorType === 'cagr') {
            $inputs = $this->parseCagrInputs($post);
            $schedule = (new CagrEngine())->generateSchedule(
                $inputs->getInitialValue(),
                $inputs->getFinalValue(),
                $inputs->getYears()
            );

            $rows = [];
            foreach ($schedule as $row) {
                $rows[] = [
                    $row['year'],
                    $row['begin_balance'],
                    $row['interest'],
                    $row['closing_balance'],
                ];
            }

            return [
                'headers'  => ['Year', "Begin Value ({$symbol})", "Compounded Gain ({$symbol})", "Projected Value ({$symbol})"],
                'rows'     => $rows,
                'filename' => $filename,
            ];
        }

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

        if ($calculatorType === 'inflation') {
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

        if ($calculatorType === 'ppf') {
            $inputs = $this->parsePpfInputs($post);
            $res = PpfEngine::calculate(
                $inputs->getAnnualInvestment(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getTiming()
            );

            $rows = [];
            foreach ($res['schedule'] as $row) {
                $rows[] = [
                    $row['year'],
                    $row['opening_balance'],
                    $row['annual_deposit'],
                    $row['interest_earned'],
                    $row['closing_balance'],
                ];
            }

            return [
                'headers'  => [
                    'Year',
                    "Opening Balance ({$symbol})",
                    "Annual PPF Deposit ({$symbol})",
                    "Tax-Free Interest ({$symbol})",
                    "Closing Balance ({$symbol})",
                ],
                'rows'     => $rows,
                'filename' => $filename,
            ];
        }

        // FD mode
        $inputs = $this->parseFdInputs($post);
        $res = FdEngine::calculate(
            $inputs->getAmount(),
            $inputs->getRate(),
            $inputs->getYears(),
            $inputs->isSenior(),
            $inputs->getPayoutFrequency()
        );

        $rows = [];
        foreach ($res['yearly_schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['opening_balance'],
                $row['interest_earned'],
                $row['payout_withdrawn'],
                $row['closing_balance'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                "Opening Balance ({$symbol})",
                "Interest Earned ({$symbol})",
                "Interest Payout ({$symbol})",
                "Closing Balance ({$symbol})",
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    /**
     * Build specialized context overrides for PdfReportTemplate & Twig report views.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>|null
     */
    public function buildPdfContext(string $calculatorType, array $post, string $sym): ?array
    {
        if (!$this->isSpecialized($calculatorType)) {
            return null;
        }

        $symbol = trim($sym) !== '' ? trim($sym) : '₹';

        if ($calculatorType === 'compound_interest') {
            $inputs = $this->parseCompoundInterestInputs($post);
            $res = (new CompoundInterestEngine())->calculate(
                $inputs->getPrincipal(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getFrequency()
            );
            $freqLabels = [1 => 'Annually (1x/yr)', 2 => 'Semi-Annually (2x/yr)', 4 => 'Quarterly (4x/yr)', 12 => 'Monthly (12x/yr)'];
            $headers = ['Year', 'Opening Balance', 'Interest Earned', 'Closing Balance'];
            $rows = array_map(fn($r) => [
                'Year ' . $r['year'],
                $this->formatMoney($r['opening_balance'], $symbol),
                $this->formatMoney($r['interest_earned'], $symbol),
                $this->formatMoney($r['closing_balance'], $symbol),
            ], $res['schedule']);

            return [
                'years'            => $inputs->getYears(),
                'doc_title'        => 'Compound Interest Growth Report',
                'doc_subtitle'     => 'Compounding Yield Schedule & Effective Annual Rate Analysis',
                'proposal_prefix'  => 'CMP-',
                'summary_invested' => $this->formatMoney($res['principal'], $symbol),
                'summary_interest' => $this->formatMoney($res['total_interest'], $symbol),
                'summary_withdrawn' => $this->formatMoney(0.0, $symbol),
                'summary_corpus'   => $this->formatMoney($res['final_amount'], $symbol),
                'raw_invested'     => $res['principal'],
                'raw_corpus'       => $res['final_amount'],
                'raw_withdrawn'    => 0.0,
                'multiplier_override' => $res['principal'] > 0
                    ? number_format($res['final_amount'] / $res['principal'], 2) . 'x'
                    : '1.00x',
                'kpi_labels'       => [
                    'invested'   => 'Principal Invested',
                    'returns'    => 'Compound Interest',
                    'corpus'     => 'Maturity Value',
                    'multiplier' => 'Growth Factor',
                ],
                'config_badge'     => 'Compound Interest Parameters',
                'config_rows'      => [
                    ['l1' => 'Initial Principal:', 'v1' => $this->formatMoney($inputs->getPrincipal(), $symbol), 'l2' => 'Nominal Annual Rate:', 'v2' => $inputs->getRate() . '% p.a.'],
                    ['l1' => 'Duration:', 'v1' => $inputs->getYears() . ' Years', 'l2' => 'Compounding Frequency:', 'v2' => $freqLabels[$inputs->getFrequency()] ?? 'Monthly'],
                    ['l1' => 'Effective Annual Rate:', 'v1' => $res['effective_annual_rate'] . '%', 'l2' => 'Rule of 72 Doubling:', 'v2' => $res['rule_of_72_years'] !== null ? $res['rule_of_72_years'] . ' Years' : 'N/A'],
                ],
                'has_inflation_callout' => false,
                'table_html'       => $this->buildHtmlTable($headers, $rows),
            ];
        }

        if ($calculatorType === 'cagr') {
            $inputs = $this->parseCagrInputs($post);
            $res = (new CagrEngine())->calculate($inputs->getInitialValue(), $inputs->getFinalValue(), $inputs->getYears());
            $schedule = (new CagrEngine())->generateSchedule($inputs->getInitialValue(), $inputs->getFinalValue(), $inputs->getYears());

            $headers = ['Year', 'Begin Value', 'Compounded Gain', 'Projected Value'];
            $rows = array_map(fn($r) => [
                'Year ' . $r['year'],
                $this->formatMoney($r['begin_balance'], $symbol),
                $this->formatMoney($r['interest'], $symbol),
                $this->formatMoney($r['closing_balance'], $symbol),
            ], $schedule);

            return [
                'years'            => max(1, (int) ceil($inputs->getYears())),
                'doc_title'        => 'CAGR Performance Report',
                'doc_subtitle'     => 'Compound Annual Growth Rate & Holding Period Return',
                'proposal_prefix'  => 'CGR-',
                'summary_invested' => $this->formatMoney($res['beginning_value'], $symbol),
                'summary_interest' => $this->formatMoney($res['total_gain'], $symbol),
                'summary_withdrawn' => $this->formatMoney(0.0, $symbol),
                'summary_corpus'   => sprintf('%.2f%% p.a.', $res['cagr_percentage']),
                'raw_invested'     => $res['beginning_value'],
                'raw_corpus'       => $res['ending_value'],
                'raw_withdrawn'    => 0.0,
                'multiplier_override' => number_format($res['multiplier'], 2) . 'x',
                'kpi_labels'       => [
                    'invested'   => 'Initial Value',
                    'returns'    => 'Absolute Gain',
                    'corpus'     => 'CAGR Return',
                    'multiplier' => 'Wealth Multiplier',
                ],
                'config_badge'     => 'Holding Period & Valuation Parameters',
                'config_rows'      => [
                    ['l1' => 'Initial Investment:', 'v1' => $this->formatMoney($res['beginning_value'], $symbol), 'l2' => 'Final / Target Value:', 'v2' => $this->formatMoney($res['ending_value'], $symbol)],
                    ['l1' => 'Holding Period:', 'v1' => $res['years'] . ' Years', 'l2' => 'Absolute Return:', 'v2' => sprintf('%.2f%%', $res['absolute_return_percentage'])],
                ],
                'has_inflation_callout' => false,
                'table_html'       => $this->buildHtmlTable($headers, $rows),
            ];
        }

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
                'years'            => $inputs->getYears(),
                'has_swp'          => true,
                'doc_title'        => 'Loan EMI Amortization Report',
                'doc_subtitle'     => 'Reducing-Balance Repayment Schedule & Interest Cost Audit',
                'proposal_prefix'  => 'EMI-',
                'summary_invested' => $this->formatMoney($res['principal'], $symbol),
                'summary_interest' => $this->formatMoney($res['total_interest'], $symbol),
                'summary_withdrawn' => $this->formatMoney($totalPayment, $symbol),
                'summary_corpus'   => $this->formatMoney(round((float) $res['monthly_emi']), $symbol) . ' / mo',
                'raw_invested'     => $res['principal'],
                'raw_corpus'       => $totalPayment,
                'raw_withdrawn'    => 0.0,
                'multiplier_override' => sprintf('%.1f%%', $res['interest_ratio_percentage']),
                'kpi_labels'       => [
                    'invested'   => 'Loan Amount',
                    'returns'    => 'Total Interest',
                    'withdrawn'  => 'Total Payment',
                    'corpus'     => 'Monthly EMI',
                    'multiplier' => 'Interest / Principal',
                ],
                'config_badge'     => 'Loan Facility Parameters',
                'config_rows'      => [
                    ['l1' => 'Loan Principal:', 'v1' => $this->formatMoney($res['principal'], $symbol), 'l2' => 'Interest Rate:', 'v2' => $res['annual_rate'] . '% p.a.'],
                    ['l1' => 'Loan Tenure:', 'v1' => $res['tenure_years'] . ' Years (' . ($res['tenure_years'] * 12) . ' EMIs)', 'l2' => 'Monthly EMI:', 'v2' => $this->formatMoney(round((float) $res['monthly_emi']), $symbol)],
                ],
                'has_inflation_callout' => false,
                'table_html'       => $this->buildHtmlTable($headers, $rows),
            ];
        }

        if ($calculatorType === 'inflation') {
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
                'years'            => $inputs->getYears(),
                'doc_title'        => 'Inflation & Purchasing Power Report',
                'doc_subtitle'     => 'Future Expense Escalation & CPI Erosion Analysis',
                'proposal_prefix'  => 'INF-',
                'summary_invested' => $this->formatMoney($res['present_value'], $symbol),
                'summary_interest' => $this->formatMoney($res['cost_increase'], $symbol),
                'summary_withdrawn' => $this->formatMoney($res['purchasing_power'], $symbol),
                'summary_corpus'   => $this->formatMoney($res['future_cost'], $symbol),
                'raw_invested'     => $res['present_value'],
                'raw_corpus'       => $res['future_cost'],
                'raw_withdrawn'    => 0.0,
                'multiplier_override' => sprintf('-%.1f%%', $res['purchasing_power_loss_percentage']),
                'kpi_labels'       => [
                    'invested'   => "Today's Cost",
                    'returns'    => 'Cost Escalation',
                    'corpus'     => 'Future Living Cost',
                    'multiplier' => 'Value Erosion',
                ],
                'config_badge'     => 'Inflation Projection Parameters',
                'config_rows'      => [
                    ['l1' => 'Present Cost:', 'v1' => $this->formatMoney($res['present_value'], $symbol), 'l2' => 'Annual Inflation Rate:', 'v2' => $res['inflation_rate'] . '% p.a.'],
                    ['l1' => 'Time Horizon:', 'v1' => $res['years'] . ' Years', 'l2' => 'Real Power Remaining:', 'v2' => $this->formatMoney($res['purchasing_power'], $symbol)],
                ],
                'has_inflation_callout' => true,
                'table_html'       => $this->buildHtmlTable($headers, $rows),
            ];
        }

        if ($calculatorType === 'ppf') {
            $inputs = $this->parsePpfInputs($post);
            $res = PpfEngine::calculate(
                $inputs->getAnnualInvestment(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getTiming()
            );

            $headers = ['Year', 'Opening Balance', 'Annual Deposit', 'Tax-Free Interest', 'Closing Balance'];
            $rows = array_map(fn($r) => [
                'Year ' . $r['year'],
                $this->formatMoney($r['opening_balance'], $symbol),
                $this->formatMoney($r['annual_deposit'], $symbol),
                $this->formatMoney($r['interest_earned'], $symbol),
                $this->formatMoney($r['closing_balance'], $symbol),
            ], $res['schedule']);

            return [
                'years'            => $inputs->getYears(),
                'doc_title'        => 'PPF Maturity & Interest Schedule',
                'doc_subtitle'     => 'Public Provident Fund (EEE Tax-Exempt Sovereign Savings)',
                'proposal_prefix'  => 'PPF-',
                'summary_invested' => $this->formatMoney($res['total_invested'], $symbol),
                'summary_interest' => $this->formatMoney($res['total_interest'], $symbol),
                'summary_withdrawn' => $this->formatMoney(0.0, $symbol),
                'summary_corpus'   => $this->formatMoney($res['maturity_amount'], $symbol),
                'raw_invested'     => $res['total_invested'],
                'raw_corpus'       => $res['maturity_amount'],
                'raw_withdrawn'    => 0.0,
                'multiplier_override' => $res['total_invested'] > 0
                    ? number_format($res['maturity_amount'] / $res['total_invested'], 2) . 'x'
                    : '1.00x',
                'kpi_labels'       => [
                    'invested'   => 'Total PPF Deposit',
                    'returns'    => 'Tax-Free Interest',
                    'corpus'     => 'Maturity Corpus',
                    'multiplier' => 'Wealth Multiplier',
                ],
                'config_badge'     => 'Statutory PPF Parameters',
                'config_rows'      => [
                    ['l1' => 'Yearly Deposit:', 'v1' => $this->formatMoney($inputs->getAnnualInvestment(), $symbol), 'l2' => 'Notified PPF Rate:', 'v2' => $res['interest_rate'] . '% p.a.'],
                    ['l1' => 'Lock-in / Horizon:', 'v1' => $res['tenure_years'] . ' Years', 'l2' => 'Deposit Schedule:', 'v2' => $inputs->getTiming() === 'monthly' ? 'Monthly (Before 5th)' : 'Annual (Before Apr 5)'],
                ],
                'has_inflation_callout' => false,
                'table_html'       => $this->buildHtmlTable($headers, $rows),
            ];
        }

        // FD mode
        $inputs = $this->parseFdInputs($post);
        $res = FdEngine::calculate(
            $inputs->getAmount(),
            $inputs->getRate(),
            $inputs->getYears(),
            $inputs->isSenior(),
            $inputs->getPayoutFrequency()
        );

        $isCumulative = $inputs->getPayoutFrequency() === 'cumulative';
        $headers = ['Year', 'Opening Balance', 'Interest Earned', 'Interest Payout', 'Closing Balance'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            $this->formatMoney($r['opening_balance'], $symbol),
            $this->formatMoney($r['interest_earned'], $symbol),
            $r['payout_withdrawn'] > 0 ? $this->formatMoney($r['payout_withdrawn'], $symbol) : '—',
            $this->formatMoney($r['closing_balance'], $symbol),
        ], $res['yearly_schedule']);

        return [
            'years'            => max(1, (int) ceil($inputs->getYears())),
            'doc_title'        => 'Fixed Deposit Maturity Report',
            'doc_subtitle'     => 'Quarterly Compounding & Section 194A TDS Projection',
            'proposal_prefix'  => 'FDP-',
            'summary_invested' => $this->formatMoney($res['principal'], $symbol),
            'summary_interest' => $this->formatMoney($res['total_interest'], $symbol),
            'summary_withdrawn' => $this->formatMoney($res['periodic_payout'], $symbol),
            'summary_corpus'   => $this->formatMoney($isCumulative ? $res['maturity_amount'] : $res['periodic_payout'], $symbol),
            'raw_invested'     => $res['principal'],
            'raw_corpus'       => $res['maturity_amount'],
            'raw_withdrawn'    => 0.0,
            'multiplier_override' => $res['principal'] > 0
                ? number_format($res['maturity_amount'] / $res['principal'], 2) . 'x'
                : '1.00x',
            'kpi_labels'       => [
                'invested'   => 'Deposit Principal',
                'returns'    => 'Total Interest',
                'corpus'     => $isCumulative ? 'Maturity Amount' : 'Periodic Payout',
                'multiplier' => 'Yield Factor',
            ],
            'config_badge'     => 'Term Deposit Parameters',
            'config_rows'      => [
                ['l1' => 'Deposit Principal:', 'v1' => $this->formatMoney($res['principal'], $symbol), 'l2' => 'Effective Card Rate:', 'v2' => $res['effective_rate'] . '% p.a.' . ($res['is_senior_citizen'] ? ' (Senior)' : '')],
                ['l1' => 'Tenure:', 'v1' => $res['duration_years'] . ' Years', 'l2' => 'Payout Frequency:', 'v2' => ucfirst($res['payout_frequency'])],
                ['l1' => 'Est. Annual TDS:', 'v1' => $this->formatMoney($res['estimated_annual_tds'], $symbol), 'l2' => 'Post-Tax Yield (30% Slab):', 'v2' => $res['post_tax_yield_30_percent'] . '% p.a.'],
            ],
            'has_inflation_callout' => false,
            'table_html'       => $this->buildHtmlTable($headers, $rows),
        ];
    }

    private function parseCompoundInterestInputs(array $post): CompoundInterestInputs
    {
        return new CompoundInterestInputs(
            isset($post['ci_principal']) ? (float) $post['ci_principal'] : 500000.0,
            isset($post['ci_rate']) ? (float) $post['ci_rate'] : 12.0,
            isset($post['ci_years']) ? (int) $post['ci_years'] : 10,
            isset($post['ci_frequency']) ? (int) $post['ci_frequency'] : 12
        );
    }

    private function parseCagrInputs(array $post): CagrInputs
    {
        $initial = isset($post['cagr_initial']) ? max(1.0, (float) $post['cagr_initial']) : 100000.0;
        $final = isset($post['cagr_final']) ? max(1.0, (float) $post['cagr_final']) : 250000.0;
        $years = isset($post['cagr_years']) ? max(0.1, (float) $post['cagr_years']) : 5.0;
        return new CagrInputs($initial, $final, $years);
    }

    private function parseEmiInputs(array $post): EmiInputs
    {
        $principal = isset($post['emi_principal']) ? max(1000.0, (float) $post['emi_principal']) : 3000000.0;
        $rate = isset($post['emi_rate']) ? max(0.1, (float) $post['emi_rate']) : 8.5;
        $years = isset($post['emi_years']) ? max(1, (int) $post['emi_years']) : 20;
        return new EmiInputs($principal, $rate, $years);
    }

    private function parseInflationInputs(array $post): InflationInputs
    {
        $amount = isset($post['inf_amount']) ? max(1.0, (float) $post['inf_amount']) : 50000.0;
        $rate = isset($post['inf_rate']) ? max(0.0, (float) $post['inf_rate']) : 6.0;
        $years = isset($post['inf_years']) ? max(1, (int) $post['inf_years']) : 15;
        return new InflationInputs($amount, $rate, $years);
    }

    private function parsePpfInputs(array $post): PpfInputs
    {
        $deposit = (float) ($post['ppf_deposit'] ?? $post['ppf_annual_investment'] ?? 150000.0);
        $rate = isset($post['ppf_rate']) ? (float) $post['ppf_rate'] : 7.1;
        $years = isset($post['ppf_years']) ? (int) $post['ppf_years'] : 15;
        $timing = (string) ($post['ppf_timing'] ?? 'beginning');
        return new PpfInputs($deposit, $rate, $years, $timing);
    }

    private function parseFdInputs(array $post): FdInputs
    {
        $amount = (float) ($post['fd_principal'] ?? $post['fd_amount'] ?? 500000.0);
        $rate = isset($post['fd_rate']) ? (float) $post['fd_rate'] : 7.0;
        $years = isset($post['fd_years']) ? (float) $post['fd_years'] : 3.0;
        $isSenior = filter_var($post['fd_senior'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $payoutFrequency = (string) ($post['fd_frequency'] ?? 'cumulative');
        return new FdInputs($amount, $rate, $years, 4, $isSenior, $payoutFrequency);
    }

    private function formatMoney(float|int $amount, string $sym): string
    {
        if ($sym === '₹' || $sym === '') {
            return $this->currencyFormatter->format($amount);
        }
        $rounded = round((float) $amount);
        $sign = $rounded < 0 ? '-' : '';
        return $sign . $sym . number_format(abs($rounded));
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    private function buildHtmlTable(array $headers, array $rows): string
    {
        $html = '<table class="breakdown-table"><thead><tr>';
        foreach ($headers as $idx => $header) {
            $align = $idx === 0 ? 'left' : 'right';
            $html .= '<th style="text-align: ' . $align . ';">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $idx => $cell) {
                $align = $idx === 0 ? 'left' : 'right';
                $weight = ($idx === 0 || $idx === count($row) - 1) ? 'font-weight: bold; color: #0f172a;' : '';
                $html .= '<td style="text-align: ' . $align . '; ' . $weight . '">' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
