<?php

declare(strict_types=1);

namespace Services\Reports;

use Core\Inputs\CagrInputs;
use Core\Inputs\CompoundInterestInputs;
use Core\Inputs\FdInputs;
use Core\Inputs\PpfInputs;
use Core\Math\CagrEngine;
use Core\Math\CompoundInterestEngine;
use Core\Math\FdEngine;
use Core\Math\PpfEngine;

/**
 * Report handler for Compound Interest, CAGR, PPF, and Fixed Deposit calculators.
 */
final class SavingsAndLoanReportHandler extends AbstractSpecializedReportHandler
{
    public function getSupportedTypes(): array
    {
        return ['compound_interest', 'cagr', 'ppf', 'fd'];
    }

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string
    {
        $ext = ltrim($extension, '.');
        return match ($calculatorType) {
            'compound_interest' => "Compound_Interest_Schedule.{$ext}",
            'cagr'              => "CAGR_Yearly_Report.{$ext}",
            'ppf'               => "PPF_Maturity_Schedule.{$ext}",
            'fd'                => "FD_Maturity_Schedule.{$ext}",
            default             => "SIP_SWP_Yearly_Report.{$ext}",
        };
    }

    public function buildCsv(string $calculatorType, array $post, string $symbol): array
    {
        $filename = $this->resolveFilename($calculatorType, 'csv');

        return match ($calculatorType) {
            'compound_interest' => $this->buildCompoundInterestCsv($post, $symbol, $filename),
            'cagr'              => $this->buildCagrCsv($post, $symbol, $filename),
            'ppf'               => $this->buildPpfCsv($post, $symbol, $filename),
            default             => $this->buildFdCsv($post, $symbol, $filename),
        };
    }

    public function buildPdfContext(string $calculatorType, array $post, string $symbol): array
    {
        return match ($calculatorType) {
            'compound_interest' => $this->buildCompoundInterestPdf($post, $symbol),
            'cagr'              => $this->buildCagrPdf($post, $symbol),
            'ppf'               => $this->buildPpfPdf($post, $symbol),
            default             => $this->buildFdPdf($post, $symbol),
        };
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildCompoundInterestCsv(array $post, string $symbol, string $filename): array
    {
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

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildCagrCsv(array $post, string $symbol, string $filename): array
    {
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

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildPpfCsv(array $post, string $symbol, string $filename): array
    {
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

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildFdCsv(array $post, string $symbol, string $filename): array
    {
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
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildCompoundInterestPdf(array $post, string $symbol): array
    {
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
            'years'                 => $inputs->getYears(),
            'doc_title'             => 'Compound Interest Growth Report',
            'doc_subtitle'          => 'Compounding Yield Schedule & Effective Annual Rate Analysis',
            'proposal_prefix'       => 'CMP-',
            'summary_invested'      => $this->formatMoney($res['principal'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_interest'], $symbol),
            'summary_withdrawn'     => $this->formatMoney(0.0, $symbol),
            'summary_corpus'        => $this->formatMoney($res['final_amount'], $symbol),
            'raw_invested'          => $res['principal'],
            'raw_corpus'            => $res['final_amount'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['principal'] > 0
                ? number_format($res['final_amount'] / $res['principal'], 2) . 'x'
                : '1.00x',
            'kpi_labels'            => [
                'invested'   => 'Principal Invested',
                'returns'    => 'Compound Interest',
                'corpus'     => 'Maturity Value',
                'multiplier' => 'Growth Factor',
            ],
            'config_badge'          => 'Compound Interest Parameters',
            'config_rows'           => [
                ['l1' => 'Initial Principal:', 'v1' => $this->formatMoney($inputs->getPrincipal(), $symbol), 'l2' => 'Nominal Annual Rate:', 'v2' => $inputs->getRate() . '% p.a.'],
                ['l1' => 'Duration:', 'v1' => $inputs->getYears() . ' Years', 'l2' => 'Compounding Frequency:', 'v2' => $freqLabels[$inputs->getFrequency()] ?? 'Monthly'],
                ['l1' => 'Effective Annual Rate:', 'v1' => $res['effective_annual_rate'] . '%', 'l2' => 'Rule of 72 Doubling:', 'v2' => $res['rule_of_72_years'] !== null ? $res['rule_of_72_years'] . ' Years' : 'N/A'],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildCagrPdf(array $post, string $symbol): array
    {
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
            'years'                 => max(1, (int) ceil($inputs->getYears())),
            'doc_title'             => 'CAGR Performance Report',
            'doc_subtitle'          => 'Compound Annual Growth Rate & Holding Period Return',
            'proposal_prefix'       => 'CGR-',
            'summary_invested'      => $this->formatMoney($res['beginning_value'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_gain'], $symbol),
            'summary_withdrawn'     => $this->formatMoney(0.0, $symbol),
            'summary_corpus'        => sprintf('%.2f%% p.a.', $res['cagr_percentage']),
            'raw_invested'          => $res['beginning_value'],
            'raw_corpus'            => $res['ending_value'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => number_format($res['multiplier'], 2) . 'x',
            'kpi_labels'            => [
                'invested'   => 'Initial Value',
                'returns'    => 'Absolute Gain',
                'corpus'     => 'CAGR Return',
                'multiplier' => 'Wealth Multiplier',
            ],
            'config_badge'          => 'Holding Period & Valuation Parameters',
            'config_rows'           => [
                ['l1' => 'Initial Investment:', 'v1' => $this->formatMoney($res['beginning_value'], $symbol), 'l2' => 'Final / Target Value:', 'v2' => $this->formatMoney($res['ending_value'], $symbol)],
                ['l1' => 'Holding Period:', 'v1' => $res['years'] . ' Years', 'l2' => 'Absolute Return:', 'v2' => sprintf('%.2f%%', $res['absolute_return_percentage'])],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildPpfPdf(array $post, string $symbol): array
    {
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
            'years'                 => $inputs->getYears(),
            'doc_title'             => 'PPF Maturity & Interest Schedule',
            'doc_subtitle'          => 'Public Provident Fund (EEE Tax-Exempt Sovereign Savings)',
            'proposal_prefix'       => 'PPF-',
            'summary_invested'      => $this->formatMoney($res['total_invested'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_interest'], $symbol),
            'summary_withdrawn'     => $this->formatMoney(0.0, $symbol),
            'summary_corpus'        => $this->formatMoney($res['maturity_amount'], $symbol),
            'raw_invested'          => $res['total_invested'],
            'raw_corpus'            => $res['maturity_amount'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['total_invested'] > 0
                ? number_format($res['maturity_amount'] / $res['total_invested'], 2) . 'x'
                : '1.00x',
            'kpi_labels'            => [
                'invested'   => 'Total PPF Deposit',
                'returns'    => 'Tax-Free Interest',
                'corpus'     => 'Maturity Corpus',
                'multiplier' => 'Wealth Multiplier',
            ],
            'config_badge'          => 'Statutory PPF Parameters',
            'config_rows'           => [
                ['l1' => 'Yearly Deposit:', 'v1' => $this->formatMoney($inputs->getAnnualInvestment(), $symbol), 'l2' => 'Notified PPF Rate:', 'v2' => $res['interest_rate'] . '% p.a.'],
                ['l1' => 'Lock-in / Horizon:', 'v1' => $res['tenure_years'] . ' Years', 'l2' => 'Deposit Schedule:', 'v2' => $inputs->getTiming() === 'monthly' ? 'Monthly (Before 5th)' : 'Annual (Before Apr 5)'],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildFdPdf(array $post, string $symbol): array
    {
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
            'years'                 => max(1, (int) ceil($inputs->getYears())),
            'doc_title'             => 'Fixed Deposit Maturity Report',
            'doc_subtitle'          => 'Quarterly Compounding & Section 194A TDS Projection',
            'proposal_prefix'       => 'FDP-',
            'summary_invested'      => $this->formatMoney($res['principal'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_interest'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['periodic_payout'], $symbol),
            'summary_corpus'        => $this->formatMoney($isCumulative ? $res['maturity_amount'] : $res['periodic_payout'], $symbol),
            'raw_invested'          => $res['principal'],
            'raw_corpus'            => $res['maturity_amount'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['principal'] > 0
                ? number_format($res['maturity_amount'] / $res['principal'], 2) . 'x'
                : '1.00x',
            'kpi_labels'            => [
                'invested'   => 'Deposit Principal',
                'returns'    => 'Total Interest',
                'corpus'     => $isCumulative ? 'Maturity Amount' : 'Periodic Payout',
                'multiplier' => 'Yield Factor',
            ],
            'config_badge'          => 'Term Deposit Parameters',
            'config_rows'           => [
                ['l1' => 'Deposit Principal:', 'v1' => $this->formatMoney($res['principal'], $symbol), 'l2' => 'Effective Card Rate:', 'v2' => $res['effective_rate'] . '% p.a.' . ($res['is_senior_citizen'] ? ' (Senior)' : '')],
                ['l1' => 'Tenure:', 'v1' => $res['duration_years'] . ' Years', 'l2' => 'Payout Frequency:', 'v2' => ucfirst($res['payout_frequency'])],
                ['l1' => 'Est. Annual TDS:', 'v1' => $this->formatMoney($res['estimated_annual_tds'], $symbol), 'l2' => 'Post-Tax Yield (30% Slab):', 'v2' => $res['post_tax_yield_30_percent'] . '% p.a.'],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseCompoundInterestInputs(array $post): CompoundInterestInputs
    {
        return new CompoundInterestInputs(
            $this->readFloat($post, 'ci_principal', 500000.0),
            $this->readFloat($post, 'ci_rate', 12.0),
            $this->readInt($post, 'ci_years', 10),
            $this->readInt($post, 'ci_frequency', 12)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseCagrInputs(array $post): CagrInputs
    {
        return new CagrInputs(
            max(1.0, $this->readFloat($post, 'cagr_initial', 100000.0)),
            max(1.0, $this->readFloat($post, 'cagr_final', 250000.0)),
            max(0.1, $this->readFloat($post, 'cagr_years', 5.0))
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parsePpfInputs(array $post): PpfInputs
    {
        $deposit = array_key_exists('ppf_deposit', $post)
            ? $this->readFloat($post, 'ppf_deposit', 150000.0)
            : $this->readFloat($post, 'ppf_annual_investment', 150000.0);

        return new PpfInputs(
            $deposit,
            $this->readFloat($post, 'ppf_rate', 7.1),
            $this->readInt($post, 'ppf_years', 15),
            $this->readStr($post, 'ppf_timing', 'beginning')
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseFdInputs(array $post): FdInputs
    {
        $amount = array_key_exists('fd_principal', $post)
            ? $this->readFloat($post, 'fd_principal', 500000.0)
            : $this->readFloat($post, 'fd_amount', 500000.0);

        return new FdInputs(
            $amount,
            $this->readFloat($post, 'fd_rate', 7.0),
            $this->readFloat($post, 'fd_years', 3.0),
            4,
            $this->readBool($post, 'fd_senior', false),
            $this->readStr($post, 'fd_frequency', 'cumulative')
        );
    }
}
