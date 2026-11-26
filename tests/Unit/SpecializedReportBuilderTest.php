<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\SpecializedReportBuilder;

class SpecializedReportBuilderTest extends TestCase
{
    private SpecializedReportBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SpecializedReportBuilder();
    }

    public function testAllTwelveSpecializedModesBuildCsvAndPdfContext(): void
    {
        $modes = [
            'compound_interest' => ['ci_principal' => 500000, 'ci_rate' => 12, 'ci_years' => 10, 'ci_frequency' => 12],
            'cagr'              => ['cagr_initial' => 100000, 'cagr_final' => 250000, 'cagr_years' => 5],
            'emi'               => ['emi_principal' => 3000000, 'emi_rate' => 8.5, 'emi_years' => 20],
            'inflation'         => ['inf_amount' => 50000, 'inf_rate' => 6, 'inf_years' => 15],
            'ppf'               => ['ppf_deposit' => 150000, 'ppf_rate' => 7.1, 'ppf_years' => 15, 'ppf_timing' => 'beginning'],
            'fd'                => ['fd_principal' => 500000, 'fd_rate' => 7.0, 'fd_years' => 5, 'fd_senior' => 'false', 'fd_frequency' => 'cumulative'],
            'gratuity'          => ['gratuity_salary' => 80000, 'gratuity_years' => 10, 'gratuity_months' => 7, 'gratuity_covered' => '1'],
            'nps'               => ['nps_monthly_contribution' => 10000, 'nps_current_age' => 30, 'nps_retirement_age' => 60, 'nps_expected_return' => 10, 'nps_annuity_percent' => 40, 'nps_annuity_rate' => 6],
            'post_retirement'   => ['post_ret_existing_corpus' => 5000000, 'post_ret_gratuity' => 1500000, 'post_ret_nps_lump_sum' => 4000000, 'post_ret_pension' => 25000, 'post_ret_expense' => 75000, 'post_ret_return' => 8.5, 'post_ret_inflation' => 6.0, 'post_ret_age' => 60, 'post_ret_horizon' => 30],
            'epf'               => ['epf_basic_salary' => 60000, 'epf_current_age' => 28, 'epf_retirement_age' => 58, 'epf_vpf_percent' => 5, 'epf_salary_hike' => 7, 'epf_interest_rate' => 8.25, 'epf_current_balance' => 100000, 'epf_cap_eps' => '1'],
            'tax_harvest'       => ['tax_portfolio_value' => 2500000, 'tax_invested_cost' => 1500000, 'tax_expected_return' => 12, 'tax_holding_years' => 10, 'tax_grandfather_fmv' => 0, 'tax_include_cess' => '1'],
            'income_tax'        => ['it_gross_salary' => 1800000, 'it_basic_salary' => 900000, 'it_hra_received' => 360000, 'it_rent_paid' => 420000, 'it_is_metro' => '1', 'it_sec_80c' => 150000, 'it_sec_80d' => 25000, 'it_nps_80ccd1b' => 50000, 'it_home_loan_24b' => 0],
        ];

        foreach ($modes as $mode => $post) {
            $this->assertTrue($this->builder->isSpecialized($mode), "isSpecialized must return true for {$mode}");
            $csv = $this->builder->buildCsv($mode, $post, '$');
            $this->assertNotNull($csv, "CSV failed for {$mode}");
            $this->assertNotEmpty($csv['headers'], "CSV headers empty for {$mode}");
            $this->assertNotEmpty($csv['rows'], "CSV rows empty for {$mode}");
            $this->assertStringEndsWith('.csv', $csv['filename']);

            $pdf = $this->builder->buildPdfContext($mode, $post, '₹');
            $this->assertNotNull($pdf, "PDF context failed for {$mode}");
            $this->assertArrayHasKey('doc_title', $pdf);
            $this->assertArrayHasKey('kpi_labels', $pdf);
            $this->assertArrayHasKey('config_rows', $pdf);
            $this->assertArrayHasKey('summary_corpus', $pdf);
            $this->assertNotSame('', trim((string) $pdf['summary_corpus']), "summary_corpus empty for {$mode}");
            $this->assertStringContainsString('<table class="breakdown-table">', (string) $pdf['table_html']);
        }
    }

    public function testStandardModesReturnNullForSpecializedBuilder(): void
    {
        $this->assertNull($this->builder->buildCsv('sip', [], '₹'));
        $this->assertNull($this->builder->buildPdfContext('combo', [], '₹'));
    }
}
