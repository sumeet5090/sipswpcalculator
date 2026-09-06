<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\CurrencyHelper;
use Core\PdfReportTableBuilder;
use PHPUnit\Framework\TestCase;

class PdfReportTableBuilderTest extends TestCase
{
    private PdfReportTableBuilder $builder;

    protected function setUp(): void
    {
        $currencyHelper = new CurrencyHelper();
        $this->builder = new PdfReportTableBuilder($currencyHelper);
    }

    public function testBuildReturnsFallbackForEmptySchedule(): void
    {
        $html = $this->builder->build([]);
        $this->assertStringContainsString('No cashflow data available', $html);
    }

    public function testBuildsYearlyTableWithCorrectColumnsForSipOnly(): void
    {
        $schedule = [
            [
                'year' => 1,
                'annual_contribution' => 120000.0,
                'cumulative_invested' => 120000.0,
                'annual_withdrawal' => 0.0,
                'cumulative_withdrawals' => 0.0,
                'interest' => 8145.0,
                'combined_total' => 128145.0,
            ],
            [
                'year' => 2,
                'annual_contribution' => 132000.0,
                'cumulative_invested' => 252000.0,
                'annual_withdrawal' => 0.0,
                'cumulative_withdrawals' => 0.0,
                'interest' => 24800.0,
                'combined_total' => 284945.0,
            ]
        ];

        $html = $this->builder->build($schedule, '₹', false);

        $this->assertStringContainsString('Year 1', $html);
        $this->assertStringContainsString('Year 2', $html);
        $this->assertStringContainsString('Annual Invested', $html);
        $this->assertStringContainsString('Total Invested', $html);
        $this->assertStringContainsString('Annual Gain', $html);
        $this->assertStringContainsString('Year-End Corpus', $html);
        $this->assertStringNotContainsString('Annual Payout', $html);
    }

    public function testBuildsYearlyTableWithSwpColumns(): void
    {
        $schedule = [
            [
                'year' => 1,
                'annual_contribution' => 120000.0,
                'cumulative_invested' => 120000.0,
                'annual_withdrawal' => 0.0,
                'cumulative_withdrawals' => 0.0,
                'interest' => 8145.0,
                'combined_total' => 128145.0,
            ],
            [
                'year' => 2,
                'annual_contribution' => 0.0,
                'cumulative_invested' => 120000.0,
                'annual_withdrawal' => 60000.0,
                'cumulative_withdrawals' => 60000.0,
                'interest' => 9000.0,
                'combined_total' => 77145.0,
            ]
        ];

        $html = $this->builder->build($schedule, '₹', true);

        $this->assertStringContainsString('Annual Payout', $html);
        $this->assertStringContainsString('Total Payout', $html);
        $this->assertStringContainsString('Year 1', $html);
        $this->assertStringContainsString('Year 2', $html);
    }

    public function testSkipsYearZeroWhenMultiYearScheduleProvided(): void
    {
        $schedule = [
            [
                'year' => 0,
                'annual_contribution' => 0.0,
                'cumulative_invested' => 100000.0,
                'combined_total' => 100000.0,
            ],
            [
                'year' => 1,
                'annual_contribution' => 120000.0,
                'cumulative_invested' => 220000.0,
                'combined_total' => 235000.0,
            ]
        ];

        $html = $this->builder->build($schedule, '₹', false);
        $this->assertStringNotContainsString('Year 0', $html);
        $this->assertStringContainsString('Year 1', $html);
    }
}
