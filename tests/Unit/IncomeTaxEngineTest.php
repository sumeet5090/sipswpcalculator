<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\IncomeTaxEngine;
use PHPUnit\Framework\TestCase;

class IncomeTaxEngineTest extends TestCase
{
    public function testDefaultBaseline15LakhSalary(): void
    {
        // 15L gross, 7.5L basic, 3L HRA, 3.6L rent, Metro, 1.5L 80C, 25k 80D, 50k NPS, 0 home loan
        $res = IncomeTaxEngine::calculate(1500000.0, 750000.0, 300000.0, 360000.0, true, 150000.0, 25000.0, 50000.0, 0.0);

        // HRA = min(300000, 360000 - 75000 = 285000, 375000) = 285000
        $this->assertEquals(285000.0, $res['hra_exemption']);
        $this->assertEquals('new', $res['recommended_regime']);
        $this->assertEquals(97500.0, $res['new_total_tax']);
        $this->assertGreaterThan(0.0, $res['tax_savings']);
        $this->assertCount(8, $res['schedule']);
    }

    public function testNewRegime1275LakhZeroTaxRebate(): void
    {
        // Gross 12,75,000 - 75,000 std deduction = 12,00,000 taxable => 0 tax under FY 2025-26 Sec 87A
        $res = IncomeTaxEngine::calculate(1275000.0, 600000.0, 240000.0, 0.0, true, 0.0, 0.0, 0.0, 0.0);
        $this->assertEquals(1200000.0, $res['new_taxable_income']);
        $this->assertEquals(0.0, $res['new_total_tax']);
        $this->assertEquals('new', $res['recommended_regime']);
    }

    public function testOldRegimeWinsWithMaxDeductionsAndHomeLoan(): void
    {
        // 15L gross, high HRA (3.6L), 80C (1.5L), 80D (1L), NPS (50k), Home Loan 24(b) (2L)
        $res = IncomeTaxEngine::calculate(1500000.0, 750000.0, 360000.0, 480000.0, true, 150000.0, 100000.0, 50000.0, 200000.0);
        $this->assertEquals('old', $res['recommended_regime']);
        $this->assertLessThan($res['new_total_tax'], $res['old_total_tax']);
    }

    public function testNonMetro40PercentHraCap(): void
    {
        // Basic 5L, HRA received 3L, Rent 3.6L => Metro (50% = 2.5L), Non-Metro (40% = 2.0L)
        $metro = IncomeTaxEngine::calculate(1500000.0, 500000.0, 300000.0, 360000.0, true);
        $nonMetro = IncomeTaxEngine::calculate(1500000.0, 500000.0, 300000.0, 360000.0, false);

        $this->assertEquals(250000.0, $metro['hra_exemption']);
        $this->assertEquals(200000.0, $nonMetro['hra_exemption']);
    }
}
