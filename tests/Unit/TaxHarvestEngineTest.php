<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\TaxHarvestEngine;
use PHPUnit\Framework\TestCase;

class TaxHarvestEngineTest extends TestCase
{
    public function testDefaultBaselineCalculation(): void
    {
        // 25L portfolio, 15L cost, 12% return, 10 yrs, 0 grandfather FMV, 4% cess included (13.0%)
        $res = TaxHarvestEngine::calculate(2500000.0, 1500000.0, 12.0, 10, 0.0, true);

        $this->assertEquals(2500000.0, $res['portfolio_value']);
        $this->assertEquals(1500000.0, $res['effective_cost_basis']);
        $this->assertEquals(13.0, $res['effective_tax_rate_pct']);
        $this->assertGreaterThan($res['harvested_tax'], $res['buy_hold_tax']);
        $this->assertEquals($res['buy_hold_tax'] - $res['harvested_tax'], $res['cumulative_tax_saved']);
        $this->assertGreaterThan(100000.0, $res['cumulative_tax_saved']);
        $this->assertEquals(16250.0, $res['march_april_savings']); // 125000 * 0.13
        $this->assertCount(10, $res['schedule']);
    }

    public function testGrandfatheringFmvProtection(): void
    {
        // Actual cost 5L, current value 30L, Jan 31 2018 FMV = 18L => effective cost basis = 18L
        $res = TaxHarvestEngine::calculate(3000000.0, 500000.0, 12.0, 5, 1800000.0, true);
        $this->assertEquals(1800000.0, $res['effective_cost_basis']);

        // If FMV > current value (e.g., FMV 35L, current value 30L), capped at current value 30L
        $resCapped = TaxHarvestEngine::calculate(3000000.0, 500000.0, 12.0, 5, 3500000.0, true);
        $this->assertEquals(3000000.0, $resCapped['effective_cost_basis']);
    }

    public function testCessToggleImpact(): void
    {
        $withCess = TaxHarvestEngine::calculate(2500000.0, 1500000.0, 12.0, 10, 0.0, true);
        $withoutCess = TaxHarvestEngine::calculate(2500000.0, 1500000.0, 12.0, 10, 0.0, false);

        $this->assertEquals(13.0, $withCess['effective_tax_rate_pct']);
        $this->assertEquals(12.5, $withoutCess['effective_tax_rate_pct']);
        $this->assertGreaterThan($withoutCess['buy_hold_tax'], $withCess['buy_hold_tax']);
        $this->assertEquals(15625.0, $withoutCess['march_april_savings']); // 125000 * 0.125
    }

    public function testSmallGainZeroTaxScenario(): void
    {
        // Unrealized gain is 50k (< 1.25L), 1 yr @ 4% => total gain ~92k < 1.25L => zero tax
        $res = TaxHarvestEngine::calculate(1050000.0, 1000000.0, 4.0, 1, 0.0, true);
        $this->assertEquals(0.0, $res['buy_hold_tax']);
        $this->assertEquals(0.0, $res['harvested_tax']);
        $this->assertEquals(0.0, $res['cumulative_tax_saved']);
        $this->assertEquals(0.0, $res['march_april_savings']);
    }
}
