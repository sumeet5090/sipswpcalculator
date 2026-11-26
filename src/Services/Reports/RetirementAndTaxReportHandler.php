<?php

declare(strict_types=1);

namespace Services\Reports;

use Core\Inputs\EpfInputs;
use Core\Inputs\GratuityInputs;
use Core\Inputs\IncomeTaxInputs;
use Core\Inputs\NpsInputs;
use Core\Inputs\PostRetirementInputs;
use Core\Inputs\TaxHarvestInputs;
use Core\Math\EpfEngine;
use Core\Math\GratuityEngine;
use Core\Math\IncomeTaxEngine;
use Core\Math\NpsEngine;
use Core\Math\PostRetirementEngine;
use Core\Math\TaxHarvestEngine;

/**
 * Report handler for Gratuity, NPS, Post-Retirement, EPF/VPF, LTCG Tax Harvesting, and Income Tax calculators.
 */
final class RetirementAndTaxReportHandler extends AbstractSpecializedReportHandler
{
    public function getSupportedTypes(): array
    {
        return ['gratuity', 'nps', 'post_retirement', 'epf', 'tax_harvest', 'income_tax'];
    }

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string
    {
        $ext = ltrim($extension, '.');
        return match ($calculatorType) {
            'gratuity'        => "Gratuity_Statutory_Schedule.{$ext}",
            'nps'             => "NPS_Tier1_Pension_Schedule.{$ext}",
            'post_retirement' => "Post_Retirement_Drawdown_Plan.{$ext}",
            'epf'             => "EPF_VPF_Maturity_Schedule.{$ext}",
            'tax_harvest'     => "LTCG_Tax_Harvesting_Schedule.{$ext}",
            'income_tax'      => "Income_Tax_Regime_Comparison.{$ext}",
            default           => "SIP_SWP_Yearly_Report.{$ext}",
        };
    }

    public function buildCsv(string $calculatorType, array $post, string $symbol): array
    {
        $filename = $this->resolveFilename($calculatorType, 'csv');

        return match ($calculatorType) {
            'gratuity'        => $this->buildGratuityCsv($post, $symbol, $filename),
            'nps'             => $this->buildNpsCsv($post, $symbol, $filename),
            'post_retirement' => $this->buildPostRetirementCsv($post, $symbol, $filename),
            'epf'             => $this->buildEpfCsv($post, $symbol, $filename),
            'tax_harvest'     => $this->buildTaxHarvestCsv($post, $symbol, $filename),
            default           => $this->buildIncomeTaxCsv($post, $symbol, $filename),
        };
    }

    public function buildPdfContext(string $calculatorType, array $post, string $symbol): array
    {
        return match ($calculatorType) {
            'gratuity'        => $this->buildGratuityPdf($post, $symbol),
            'nps'             => $this->buildNpsPdf($post, $symbol),
            'post_retirement' => $this->buildPostRetirementPdf($post, $symbol),
            'epf'             => $this->buildEpfPdf($post, $symbol),
            'tax_harvest'     => $this->buildTaxHarvestPdf($post, $symbol),
            default           => $this->buildIncomeTaxPdf($post, $symbol),
        };
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildGratuityCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parseGratuityInputs($post);
        $res = GratuityEngine::calculate(
            $inputs->getMonthlySalary(),
            $inputs->getTenureYears(),
            $inputs->getTenureMonths(),
            $inputs->isCoveredByAct()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['accrued_amount'],
                $row['is_vested'] ? 'Vested' : 'Not Vested',
                $row['tax_exempt'],
                $row['taxable'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                "Accrued Gratuity ({$symbol})",
                'Vesting Status',
                "Tax-Exempt Sec 10(10) ({$symbol})",
                "Taxable Gratuity ({$symbol})",
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildNpsCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parseNpsInputs($post);
        $res = NpsEngine::calculate(
            $inputs->getMonthlyContribution(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getExpectedReturnRate(),
            $inputs->getAnnuityPercent(),
            $inputs->getAnnuityReturnRate()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['age'],
                $row['invested'],
                $row['interest'],
                $row['balance'],
                $row['lump_sum_share'],
                $row['annuity_share'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                'Age',
                "Cumulative Invested ({$symbol})",
                "Compounding Gains ({$symbol})",
                "Total Corpus ({$symbol})",
                "Tax-Free Lump Sum ({$symbol})",
                "Annuity Corpus ({$symbol})",
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildPostRetirementCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parsePostRetirementInputs($post);
        $res = PostRetirementEngine::calculate(
            $inputs->getExistingCorpus(),
            $inputs->getGratuityLumpSum(),
            $inputs->getNpsLumpSum(),
            $inputs->getGuaranteedPension(),
            $inputs->getMonthlyExpense(),
            $inputs->getExpectedReturn(),
            $inputs->getInflationRate(),
            $inputs->getCurrentAge(),
            $inputs->getPlanningHorizonYears()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['age'],
                $row['opening_balance'],
                $row['annual_living_expense'],
                $row['annual_pension_received'],
                $row['annual_swp_drawn'],
                $row['growth_earned'],
                $row['closing_balance'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                'Age',
                "Opening Balance ({$symbol})",
                "Annual Living Expense ({$symbol})",
                "Guaranteed Pension ({$symbol})",
                "Net SWP Drawn ({$symbol})",
                "Portfolio Growth ({$symbol})",
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
    private function buildEpfCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parseEpfInputs($post);
        $res = EpfEngine::calculate(
            $inputs->getBasicSalary(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getVpfPercent(),
            $inputs->getSalaryHike(),
            $inputs->getInterestRate(),
            $inputs->getCurrentBalance(),
            $inputs->isCapEps()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['age'],
                $row['opening_balance'],
                $row['employee_contribution'],
                $row['employer_contribution'],
                $row['interest_earned'],
                $row['closing_balance'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                'Age',
                "Opening Balance ({$symbol})",
                "Employee + VPF ({$symbol})",
                "Employer EPF ({$symbol})",
                "EPFO Interest ({$symbol})",
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
    private function buildTaxHarvestCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parseTaxHarvestInputs($post);
        $res = TaxHarvestEngine::calculate(
            $inputs->getPortfolioValue(),
            $inputs->getInvestedCost(),
            $inputs->getExpectedReturn(),
            $inputs->getHoldingYears(),
            $inputs->getGrandfatherFmv(),
            $inputs->isIncludeCess()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['year'],
                $row['gross_value'],
                $row['buy_hold_tax'],
                $row['harvested_this_year'],
                $row['harvested_exit_tax'],
                $row['tax_saved'],
                $row['net_harvested_wealth'],
            ];
        }

        return [
            'headers'  => [
                'Year',
                "Gross Portfolio Value ({$symbol})",
                "Buy & Hold LTCG Tax ({$symbol})",
                "Harvested Gains ({$symbol})",
                "Harvested Exit Tax ({$symbol})",
                "Annual Tax Saved ({$symbol})",
                "Net Post-Tax Wealth ({$symbol})",
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    private function buildIncomeTaxCsv(array $post, string $symbol, string $filename): array
    {
        $inputs = $this->parseIncomeTaxInputs($post);
        $res = IncomeTaxEngine::calculate(
            $inputs->getGrossSalary(),
            $inputs->getBasicSalary(),
            $inputs->getHraReceived(),
            $inputs->getRentPaid(),
            $inputs->isMetro(),
            $inputs->getSec80c(),
            $inputs->getSec80d(),
            $inputs->getNps80ccd1b(),
            $inputs->getHomeLoan24b()
        );

        $rows = [];
        foreach ($res['schedule'] as $row) {
            $rows[] = [
                $row['label'],
                $row['gross_income'],
                $row['old_tax'],
                $row['new_tax'],
                $row['savings'],
            ];
        }

        return [
            'headers'  => [
                'Salary Tier',
                "Gross Income ({$symbol})",
                "Old Regime Tax ({$symbol})",
                "New Regime Tax ({$symbol})",
                "Regime Savings ({$symbol})",
            ],
            'rows'     => $rows,
            'filename' => $filename,
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildGratuityPdf(array $post, string $symbol): array
    {
        $inputs = $this->parseGratuityInputs($post);
        $res = GratuityEngine::calculate(
            $inputs->getMonthlySalary(),
            $inputs->getTenureYears(),
            $inputs->getTenureMonths(),
            $inputs->isCoveredByAct()
        );

        $headers = ['Year', 'Accrued Gratuity', 'Vesting', 'Sec 10(10) Exempt', 'Taxable Share'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            $this->formatMoney($r['accrued_amount'], $symbol),
            $r['is_vested'] ? 'Vested' : 'Unvested',
            $this->formatMoney($r['tax_exempt'], $symbol),
            $this->formatMoney($r['taxable'], $symbol),
        ], $res['schedule']);

        return [
            'years'                 => max(1, $res['effective_years']),
            'has_swp'               => true,
            'doc_title'             => 'Statutory Gratuity Valuation Report',
            'doc_subtitle'          => 'Payment of Gratuity Act, 1972 & Section 10(10) Exemption Analysis',
            'proposal_prefix'       => 'GRT-',
            'summary_invested'      => $this->formatMoney($res['monthly_salary'], $symbol),
            'summary_interest'      => $this->formatMoney($res['tax_exempt_amount'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['taxable_amount'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['gratuity_amount'], $symbol),
            'raw_invested'          => $res['monthly_salary'],
            'raw_corpus'            => $res['gratuity_amount'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['is_eligible'] ? 'Vested (5+ Yrs)' : 'Not Vested (<5 Yrs)',
            'kpi_labels'            => [
                'invested'   => 'Last Drawn Basic + DA',
                'returns'    => 'Tax-Exempt Gratuity',
                'withdrawn'  => 'Taxable Gratuity',
                'corpus'     => 'Total Gratuity Payable',
                'multiplier' => 'Vesting Status',
            ],
            'config_badge'          => 'Statutory Service Parameters',
            'config_rows'           => [
                ['l1' => 'Last Drawn Basic + DA:', 'v1' => $this->formatMoney($res['monthly_salary'], $symbol), 'l2' => 'Act Coverage:', 'v2' => $res['is_covered_by_act'] ? 'Covered (15/26 Formula)' : 'Not Covered (15/30)'],
                ['l1' => 'Service Tenure:', 'v1' => "{$res['tenure_years']} Yrs {$res['tenure_months']} Mos", 'l2' => 'Effective Rounded Yrs:', 'v2' => $res['effective_years'] . ' Years'],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildNpsPdf(array $post, string $symbol): array
    {
        $inputs = $this->parseNpsInputs($post);
        $res = NpsEngine::calculate(
            $inputs->getMonthlyContribution(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getExpectedReturnRate(),
            $inputs->getAnnuityPercent(),
            $inputs->getAnnuityReturnRate()
        );

        $headers = ['Year', 'Age', 'Invested', 'Total Corpus', '60% Lump Sum', 'Annuity Share'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            (string) $r['age'],
            $this->formatMoney($r['invested'], $symbol),
            $this->formatMoney($r['balance'], $symbol),
            $this->formatMoney($r['lump_sum_share'], $symbol),
            $this->formatMoney($r['annuity_share'], $symbol),
        ], $res['schedule']);

        return [
            'years'                 => $res['tenure_years'],
            'has_swp'               => true,
            'doc_title'             => 'NPS Tier-1 Wealth & Pension Report',
            'doc_subtitle'          => 'PFRDA Superannuation Exit Split & Monthly Annuity Cashflow',
            'proposal_prefix'       => 'NPS-',
            'summary_invested'      => $this->formatMoney($res['total_invested'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_interest'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['lump_sum_amount'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['monthly_pension'], $symbol) . ' / mo',
            'raw_invested'          => $res['total_invested'],
            'raw_corpus'            => $res['total_corpus'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['total_invested'] > 0
                ? number_format($res['total_corpus'] / $res['total_invested'], 2) . 'x'
                : '1.00x',
            'kpi_labels'            => [
                'invested'   => 'Total Invested',
                'returns'    => 'Compounding Gains',
                'withdrawn'  => 'Tax-Free Lump Sum',
                'corpus'     => 'Monthly Pension',
                'multiplier' => 'Wealth Multiplier',
            ],
            'config_badge'          => 'NPS Tier-1 Parameters',
            'config_rows'           => [
                ['l1' => 'Monthly Contribution:', 'v1' => $this->formatMoney($res['monthly_contribution'], $symbol), 'l2' => 'Investment Horizon:', 'v2' => "Age {$res['current_age']} → {$res['retirement_age']} ({$res['tenure_years']} Yrs)"],
                ['l1' => 'Expected Return:', 'v1' => $res['expected_return_rate'] . '% p.a.', 'l2' => 'Annuity Allocation:', 'v2' => "{$res['annuity_percent']}% @ {$res['annuity_return_rate']}%"],
                ['l1' => 'Total Corpus at Exit:', 'v1' => $this->formatMoney($res['total_corpus'], $symbol), 'l2' => 'Annuity Purchase:', 'v2' => $this->formatMoney($res['annuity_amount'], $symbol)],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildPostRetirementPdf(array $post, string $symbol): array
    {
        $inputs = $this->parsePostRetirementInputs($post);
        $res = PostRetirementEngine::calculate(
            $inputs->getExistingCorpus(),
            $inputs->getGratuityLumpSum(),
            $inputs->getNpsLumpSum(),
            $inputs->getGuaranteedPension(),
            $inputs->getMonthlyExpense(),
            $inputs->getExpectedReturn(),
            $inputs->getInflationRate(),
            $inputs->getCurrentAge(),
            $inputs->getPlanningHorizonYears()
        );

        $headers = ['Year', 'Age', 'Opening Balance', 'Living Expense', 'Pension', 'Net SWP', 'Closing Balance'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            (string) $r['age'],
            $this->formatMoney($r['opening_balance'], $symbol),
            $this->formatMoney($r['annual_living_expense'], $symbol),
            $this->formatMoney($r['annual_pension_received'], $symbol),
            $this->formatMoney($r['annual_swp_drawn'], $symbol),
            $this->formatMoney($r['closing_balance'], $symbol),
        ], $res['schedule']);

        $longevityText = $res['is_perpetual']
            ? 'Age 90+ (Perpetual)'
            : 'Age ' . ($res['depletion_age'] ?? 0) . ' (Yr ' . ($res['depletion_year'] ?? 0) . ')';

        return [
            'years'                 => $inputs->getPlanningHorizonYears(),
            'has_swp'               => true,
            'doc_title'             => 'Post-Retirement Asset Drawdown Report',
            'doc_subtitle'          => 'Multi-Stream Cashflow, SWR Sustainability & 3-Bucket Allocation',
            'proposal_prefix'       => 'RET-',
            'summary_invested'      => $this->formatMoney($res['total_initial_corpus'], $symbol),
            'summary_interest'      => $this->formatMoney($inputs->getGuaranteedPension(), $symbol) . ' / mo',
            'summary_withdrawn'     => $this->formatMoney($res['initial_monthly_swp'], $symbol) . ' / mo',
            'summary_corpus'        => $longevityText,
            'raw_invested'          => $res['total_initial_corpus'],
            'raw_corpus'            => $res['final_balance'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => sprintf('%.2f%% SWR', $res['initial_swr_percent']),
            'kpi_labels'            => [
                'invested'   => 'Initial Nest Egg',
                'returns'    => 'Guaranteed Pension',
                'withdrawn'  => 'Net Monthly SWP',
                'corpus'     => 'Portfolio Longevity',
                'multiplier' => 'Safe Withdrawal Rate',
            ],
            'config_badge'          => '3-Bucket Strategy & Drawdown Parameters',
            'config_rows'           => [
                ['l1' => 'Initial Nest Egg:', 'v1' => $this->formatMoney($res['total_initial_corpus'], $symbol), 'l2' => 'Year-1 Monthly Expense:', 'v2' => $this->formatMoney($inputs->getMonthlyExpense(), $symbol)],
                ['l1' => 'Return / Inflation:', 'v1' => "{$inputs->getExpectedReturn()}% / {$inputs->getInflationRate()}% p.a.", 'l2' => 'Horizon From Age:', 'v2' => "Age {$inputs->getCurrentAge()} ({$inputs->getPlanningHorizonYears()} Yrs)"],
                ['l1' => 'Bucket 1 (0–3Y Cash):', 'v1' => $this->formatMoney($res['bucket_1_cash'], $symbol), 'l2' => 'Bucket 2 / 3 Split:', 'v2' => $this->formatMoney($res['bucket_2_income'], $symbol) . ' / ' . $this->formatMoney($res['bucket_3_growth'], $symbol)],
            ],
            'has_inflation_callout' => true,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildEpfPdf(array $post, string $symbol): array
    {
        $inputs = $this->parseEpfInputs($post);
        $res = EpfEngine::calculate(
            $inputs->getBasicSalary(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getVpfPercent(),
            $inputs->getSalaryHike(),
            $inputs->getInterestRate(),
            $inputs->getCurrentBalance(),
            $inputs->isCapEps()
        );

        $headers = ['Year', 'Age', 'Opening Balance', 'Employee + VPF', 'Employer EPF', 'Interest', 'Closing Balance'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            (string) $r['age'],
            $this->formatMoney($r['opening_balance'], $symbol),
            $this->formatMoney($r['employee_contribution'], $symbol),
            $this->formatMoney($r['employer_contribution'], $symbol),
            $this->formatMoney($r['interest_earned'], $symbol),
            $this->formatMoney($r['closing_balance'], $symbol),
        ], $res['schedule']);

        return [
            'years'                 => $res['tenure_years'],
            'has_swp'               => true,
            'doc_title'             => 'EPF & VPF Provident Fund Schedule',
            'doc_subtitle'          => 'EPFO Monthly Running-Balance Compounding & EPS Split Audit',
            'proposal_prefix'       => 'EPF-',
            'summary_invested'      => $this->formatMoney($res['total_employee_contribution'], $symbol),
            'summary_interest'      => $this->formatMoney($res['total_employer_contribution'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['total_interest'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['maturity_corpus'], $symbol),
            'raw_invested'          => $res['total_invested'],
            'raw_corpus'            => $res['maturity_corpus'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => $res['total_invested'] > 0
                ? number_format($res['maturity_corpus'] / $res['total_invested'], 2) . 'x'
                : '1.00x',
            'kpi_labels'            => [
                'invested'   => 'Employee + VPF',
                'returns'    => 'Employer EPF Match',
                'withdrawn'  => 'EPFO Interest',
                'corpus'     => 'EPF Maturity Corpus',
                'multiplier' => 'Corpus Multiplier',
            ],
            'config_badge'          => 'EPFO & VPF Contribution Parameters',
            'config_rows'           => [
                ['l1' => 'Monthly Basic + DA:', 'v1' => $this->formatMoney($res['basic_salary'], $symbol), 'l2' => 'Voluntary VPF / Hike:', 'v2' => "{$res['vpf_percent']}% / {$res['salary_hike']}% p.a."],
                ['l1' => 'EPFO Interest Rate:', 'v1' => $res['interest_rate'] . '% p.a.', 'l2' => 'EPS Wage Cap (₹15k):', 'v2' => $res['cap_eps'] ? 'Capped (₹1,250/mo EPS)' : 'Uncapped 8.33% EPS'],
                ['l1' => 'Total Diverted to EPS:', 'v1' => $this->formatMoney($res['total_eps_diverted'], $symbol), 'l2' => '₹2.5L Tax-Free Check:', 'v2' => $res['exceeds_250k_cap'] ? 'Exceeds ₹2.5L Limit' : 'Within Tax-Free Cap'],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildTaxHarvestPdf(array $post, string $symbol): array
    {
        $inputs = $this->parseTaxHarvestInputs($post);
        $res = TaxHarvestEngine::calculate(
            $inputs->getPortfolioValue(),
            $inputs->getInvestedCost(),
            $inputs->getExpectedReturn(),
            $inputs->getHoldingYears(),
            $inputs->getGrandfatherFmv(),
            $inputs->isIncludeCess()
        );

        $headers = ['Year', 'Gross Corpus', 'Buy & Hold Tax', 'Harvested', 'Tax Saved', 'Net Wealth'];
        $rows = array_map(fn($r) => [
            'Year ' . $r['year'],
            $this->formatMoney($r['gross_value'], $symbol),
            $this->formatMoney($r['buy_hold_tax'], $symbol),
            $this->formatMoney($r['harvested_this_year'], $symbol),
            $this->formatMoney($r['tax_saved'], $symbol),
            $this->formatMoney($r['net_harvested_wealth'], $symbol),
        ], $res['schedule']);

        return [
            'years'                 => $inputs->getHoldingYears(),
            'has_swp'               => true,
            'doc_title'             => 'Section 112A LTCG Tax Harvesting Report',
            'doc_subtitle'          => 'Annual ₹1.25 Lakh Cost-Basis Step-Up & Tax Savings Analysis',
            'proposal_prefix'       => 'LTCG-',
            'summary_invested'      => $this->formatMoney($res['buy_hold_tax'], $symbol),
            'summary_interest'      => $this->formatMoney($res['harvested_tax'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['cumulative_tax_saved'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['net_post_tax_wealth'], $symbol),
            'raw_invested'          => $res['effective_cost_basis'],
            'raw_corpus'            => $res['net_post_tax_wealth'],
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => sprintf('%.1f%% LTCG', $res['effective_tax_rate_pct']),
            'kpi_labels'            => [
                'invested'   => 'Buy & Hold Tax',
                'returns'    => 'Harvested LTCG Tax',
                'withdrawn'  => 'Cumulative Tax Saved',
                'corpus'     => 'Net Post-Tax Wealth',
                'multiplier' => 'Effective Tax Rate',
            ],
            'config_badge'          => 'Capital Gains & Section 112A Parameters',
            'config_rows'           => [
                ['l1' => 'Portfolio Value:', 'v1' => $this->formatMoney($res['portfolio_value'], $symbol), 'l2' => 'Effective Cost Basis:', 'v2' => $this->formatMoney($res['effective_cost_basis'], $symbol)],
                ['l1' => 'Expected Return:', 'v1' => "{$res['expected_return']}% CAGR ({$res['holding_years']} Yrs)", 'l2' => 'Mar 31 + Apr 1 Split:', 'v2' => $this->formatMoney($res['march_april_savings'], $symbol)],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildIncomeTaxPdf(array $post, string $symbol): array
    {
        $inputs = $this->parseIncomeTaxInputs($post);
        $res = IncomeTaxEngine::calculate(
            $inputs->getGrossSalary(),
            $inputs->getBasicSalary(),
            $inputs->getHraReceived(),
            $inputs->getRentPaid(),
            $inputs->isMetro(),
            $inputs->getSec80c(),
            $inputs->getSec80d(),
            $inputs->getNps80ccd1b(),
            $inputs->getHomeLoan24b()
        );

        $headers = ['Salary Tier', 'Gross Income', 'Old Regime Tax', 'New Regime Tax', 'Regime Savings'];
        $rows = array_map(fn($r) => [
            $r['label'],
            $this->formatMoney($r['gross_income'], $symbol),
            $this->formatMoney($r['old_tax'], $symbol),
            $this->formatMoney($r['new_tax'], $symbol),
            $this->formatMoney($r['savings'], $symbol),
        ], $res['schedule']);

        return [
            'years'                 => max(1, count($res['schedule'])),
            'has_swp'               => true,
            'doc_title'             => 'FY 2025-26 Income Tax & HRA Comparison Report',
            'doc_subtitle'          => 'Old Tax Regime (Chapter VI-A + HRA) vs. New Regime (₹12L 87A Rebate)',
            'proposal_prefix'       => 'ITR-',
            'summary_invested'      => $this->formatMoney($res['hra_exemption'], $symbol),
            'summary_interest'      => $this->formatMoney($res['old_total_tax'], $symbol),
            'summary_withdrawn'     => $this->formatMoney($res['new_total_tax'], $symbol),
            'summary_corpus'        => $this->formatMoney($res['tax_savings'], $symbol),
            'raw_invested'          => $res['gross_salary'],
            'raw_corpus'            => max(0.0, $res['gross_salary'] - min($res['old_total_tax'], $res['new_total_tax'])),
            'raw_withdrawn'         => 0.0,
            'multiplier_override'   => strtoupper($res['recommended_regime']) . ' REGIME',
            'kpi_labels'            => [
                'invested'   => 'HRA Exemption',
                'returns'    => 'Old Regime Tax',
                'withdrawn'  => 'New Regime Tax',
                'corpus'     => 'Recommended Savings',
                'multiplier' => 'Optimal Choice',
            ],
            'config_badge'          => 'Salary Structure & Deduction Parameters',
            'config_rows'           => [
                ['l1' => 'Annual Gross Salary:', 'v1' => $this->formatMoney($res['gross_salary'], $symbol), 'l2' => 'Annual Basic + DA:', 'v2' => $this->formatMoney($res['basic_salary'], $symbol) . ($res['is_metro'] ? ' (Metro)' : ' (Non-Metro)')],
                ['l1' => 'Old Regime Deductions:', 'v1' => $this->formatMoney($res['old_total_deductions'], $symbol), 'l2' => 'Effective Tax (O / N):', 'v2' => "{$res['old_effective_rate']}% / {$res['new_effective_rate']}%"],
            ],
            'has_inflation_callout' => false,
            'table_html'            => $this->buildHtmlTable($headers, $rows),
        ];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseGratuityInputs(array $post): GratuityInputs
    {
        return new GratuityInputs(
            $this->readFloat($post, 'gratuity_salary', GratuityEngine::DEFAULT_MONTHLY_SALARY),
            $this->readInt($post, 'gratuity_years', GratuityEngine::DEFAULT_TENURE_YEARS),
            $this->readInt($post, 'gratuity_months', 0),
            $this->readBool($post, 'gratuity_covered', true)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseNpsInputs(array $post): NpsInputs
    {
        return new NpsInputs(
            $this->readFloat($post, 'nps_monthly_contribution', NpsEngine::DEFAULT_MONTHLY_CONTRIBUTION),
            $this->readInt($post, 'nps_current_age', NpsEngine::DEFAULT_CURRENT_AGE),
            $this->readInt($post, 'nps_retirement_age', NpsEngine::DEFAULT_RETIREMENT_AGE),
            $this->readFloat($post, 'nps_expected_return', NpsEngine::DEFAULT_EXPECTED_RETURN),
            $this->readFloat($post, 'nps_annuity_percent', NpsEngine::DEFAULT_ANNUITY_PERCENT),
            $this->readFloat($post, 'nps_annuity_rate', NpsEngine::DEFAULT_ANNUITY_RATE)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parsePostRetirementInputs(array $post): PostRetirementInputs
    {
        return new PostRetirementInputs(
            $this->readFloat($post, 'post_ret_existing_corpus', PostRetirementEngine::DEFAULT_EXISTING_CORPUS),
            $this->readFloat($post, 'post_ret_gratuity', PostRetirementEngine::DEFAULT_GRATUITY_LUMP_SUM),
            $this->readFloat($post, 'post_ret_nps_lump_sum', PostRetirementEngine::DEFAULT_NPS_LUMP_SUM),
            $this->readFloat($post, 'post_ret_pension', PostRetirementEngine::DEFAULT_GUARANTEED_PENSION),
            $this->readFloat($post, 'post_ret_expense', PostRetirementEngine::DEFAULT_MONTHLY_EXPENSE),
            $this->readFloat($post, 'post_ret_return', PostRetirementEngine::DEFAULT_EXPECTED_RETURN),
            $this->readFloat($post, 'post_ret_inflation', PostRetirementEngine::DEFAULT_INFLATION_RATE),
            $this->readInt($post, 'post_ret_age', PostRetirementEngine::DEFAULT_CURRENT_AGE),
            $this->readInt($post, 'post_ret_horizon', PostRetirementEngine::DEFAULT_PLANNING_HORIZON_YEARS)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseEpfInputs(array $post): EpfInputs
    {
        return new EpfInputs(
            $this->readFloat($post, 'epf_basic_salary', EpfEngine::DEFAULT_BASIC_SALARY),
            $this->readInt($post, 'epf_current_age', EpfEngine::DEFAULT_CURRENT_AGE),
            $this->readInt($post, 'epf_retirement_age', EpfEngine::DEFAULT_RETIREMENT_AGE),
            $this->readFloat($post, 'epf_vpf_percent', EpfEngine::DEFAULT_VPF_PERCENT),
            $this->readFloat($post, 'epf_salary_hike', EpfEngine::DEFAULT_SALARY_HIKE),
            $this->readFloat($post, 'epf_interest_rate', EpfEngine::DEFAULT_INTEREST_RATE),
            $this->readFloat($post, 'epf_current_balance', EpfEngine::DEFAULT_CURRENT_BALANCE),
            $this->readBool($post, 'epf_cap_eps', EpfEngine::DEFAULT_CAP_EPS)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseTaxHarvestInputs(array $post): TaxHarvestInputs
    {
        return new TaxHarvestInputs(
            $this->readFloat($post, 'tax_portfolio_value', TaxHarvestEngine::DEFAULT_PORTFOLIO_VALUE),
            $this->readFloat($post, 'tax_invested_cost', TaxHarvestEngine::DEFAULT_INVESTED_COST),
            $this->readFloat($post, 'tax_expected_return', TaxHarvestEngine::DEFAULT_EXPECTED_RETURN),
            $this->readInt($post, 'tax_holding_years', TaxHarvestEngine::DEFAULT_HOLDING_YEARS),
            $this->readFloat($post, 'tax_grandfather_fmv', TaxHarvestEngine::DEFAULT_GRANDFATHER_FMV),
            $this->readBool($post, 'tax_include_cess', TaxHarvestEngine::DEFAULT_INCLUDE_CESS)
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function parseIncomeTaxInputs(array $post): IncomeTaxInputs
    {
        return new IncomeTaxInputs(
            $this->readFloat($post, 'it_gross_salary', IncomeTaxEngine::DEFAULT_GROSS_SALARY),
            $this->readFloat($post, 'it_basic_salary', IncomeTaxEngine::DEFAULT_BASIC_SALARY),
            $this->readFloat($post, 'it_hra_received', IncomeTaxEngine::DEFAULT_HRA_RECEIVED),
            $this->readFloat($post, 'it_rent_paid', IncomeTaxEngine::DEFAULT_RENT_PAID),
            $this->readBool($post, 'it_is_metro', IncomeTaxEngine::DEFAULT_IS_METRO),
            $this->readFloat($post, 'it_sec_80c', IncomeTaxEngine::DEFAULT_SEC_80C),
            $this->readFloat($post, 'it_sec_80d', IncomeTaxEngine::DEFAULT_SEC_80D),
            $this->readFloat($post, 'it_nps_80ccd1b', IncomeTaxEngine::DEFAULT_NPS_80CCD1B),
            $this->readFloat($post, 'it_home_loan_24b', IncomeTaxEngine::DEFAULT_HOME_LOAN_24B)
        );
    }
}
