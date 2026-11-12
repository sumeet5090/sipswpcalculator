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

    public function testAllSixSpecializedModesBuildCsvAndPdfContext(): void
    {
        $modes = [
            'compound_interest' => ['ci_principal' => 500000, 'ci_rate' => 12, 'ci_years' => 10, 'ci_frequency' => 12],
            'cagr'              => ['cagr_initial' => 100000, 'cagr_final' => 250000, 'cagr_years' => 5],
            'emi'               => ['emi_principal' => 3000000, 'emi_rate' => 8.5, 'emi_years' => 20],
            'inflation'         => ['inf_amount' => 50000, 'inf_rate' => 6, 'inf_years' => 15],
            'ppf'               => ['ppf_deposit' => 150000, 'ppf_rate' => 7.1, 'ppf_years' => 15, 'ppf_timing' => 'beginning'],
            'fd'                => ['fd_principal' => 500000, 'fd_rate' => 7.0, 'fd_years' => 5, 'fd_senior' => 'false', 'fd_frequency' => 'cumulative'],
        ];

        foreach ($modes as $mode => $post) {
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
            $this->assertStringContainsString('<table class="breakdown-table">', (string) $pdf['table_html']);
        }
    }

    public function testStandardModesReturnNullForSpecializedBuilder(): void
    {
        $this->assertNull($this->builder->buildCsv('sip', [], '₹'));
        $this->assertNull($this->builder->buildPdfContext('combo', [], '₹'));
    }
}
