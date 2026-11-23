<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\NpsEngine;
use PHPUnit\Framework\TestCase;

class NpsEngineTest extends TestCase
{
    public function testDefaultBaselineCalculation(): void
    {
        // 5000/mo, age 30 to 60 (30 yrs), 10% ROI, 40% annuity, 6% yield
        $res = NpsEngine::calculate(5000.0, 30, 60, 10.0, 40.0, 6.0);

        $this->assertEquals(5000.0, $res['monthly_contribution']);
        $this->assertEquals(30, $res['current_age']);
        $this->assertEquals(60, $res['retirement_age']);
        $this->assertEquals(30, $res['tenure_years']);
        $this->assertEquals(10.0, $res['expected_return_rate']);
        $this->assertEquals(40.0, $res['annuity_percent']);
        $this->assertEquals(6.0, $res['annuity_return_rate']);

        $this->assertEquals(1800000.0, $res['total_invested']);
        $this->assertEquals(11396627.0, $res['total_corpus']);
        $this->assertEquals(9596627.0, $res['total_interest']);
        $this->assertEquals(6837976.0, $res['lump_sum_amount']);
        $this->assertEquals(4558651.0, $res['annuity_amount']);
        $this->assertEquals(22793.0, $res['monthly_pension']);
        $this->assertFalse($res['is_small_corpus_eligible']);

        $this->assertCount(30, $res['schedule']);
        $lastYear = end($res['schedule']);
        $this->assertEquals(30, $lastYear['year']);
        $this->assertEquals(60, $lastYear['age']);
        $this->assertEquals(11396627.0, $lastYear['balance']);
    }

    public function testHighEarnerAggressiveScenario(): void
    {
        // 25,000/mo, age 25 to 60 (35 yrs), 12% ROI, 40% annuity, 6.5% yield
        $res = NpsEngine::calculate(25000.0, 25, 60, 12.0, 40.0, 6.5);

        $this->assertEquals(35, $res['tenure_years']);
        $this->assertEquals(25000.0 * 35 * 12, $res['total_invested']);
        $this->assertGreaterThan($res['total_invested'], $res['total_corpus']);
        $this->assertEquals($res['total_corpus'], $res['lump_sum_amount'] + $res['annuity_amount']);
        $this->assertFalse($res['is_small_corpus_eligible']);
    }

    public function test100PercentAnnuityAllocation(): void
    {
        // 10,000/mo, age 40 to 60 (20 yrs), 10% ROI, 100% annuity, 6% yield
        $res = NpsEngine::calculate(10000.0, 40, 60, 10.0, 100.0, 6.0);

        $this->assertEquals(0.0, $res['lump_sum_amount']);
        $this->assertEquals($res['total_corpus'], $res['annuity_amount']);
        $expectedPension = round($res['total_corpus'] * (0.06 / 12));
        $this->assertEquals($expectedPension, $res['monthly_pension']);
    }

    public function testSmallCorpusEligibility(): void
    {
        // 500/mo, age 58 to 60 (2 yrs), 5% ROI
        $res = NpsEngine::calculate(500.0, 58, 60, 5.0, 40.0, 6.0);

        $this->assertLessThanOrEqual(500000.0, $res['total_corpus']);
        $this->assertTrue($res['is_small_corpus_eligible']);
    }

    public function testBoundaryClamping(): void
    {
        // Negative / extreme values clamped to statutory bounds
        $res = NpsEngine::calculate(-100.0, 10, 80, 25.0, 20.0, 15.0);

        $this->assertEquals(NpsEngine::MIN_MONTHLY_CONTRIBUTION, $res['monthly_contribution']);
        $this->assertEquals(NpsEngine::MIN_CURRENT_AGE, $res['current_age']);
        $this->assertEquals(NpsEngine::MAX_RETIREMENT_AGE, $res['retirement_age']);
        $this->assertEquals(NpsEngine::MAX_EXPECTED_RETURN, $res['expected_return_rate']);
        $this->assertEquals(NpsEngine::MIN_ANNUITY_PERCENT, $res['annuity_percent']); // Clamped to 40% min
        $this->assertEquals(NpsEngine::MAX_ANNUITY_RATE, $res['annuity_return_rate']);
    }

    public function testRetirementAgeLessThanCurrentAgeEnforcement(): void
    {
        // Current age 50, retirement age 40 -> retirement age should clamp to at least current_age + 1 (51)
        $res = NpsEngine::calculate(5000.0, 50, 40, 10.0);

        $this->assertEquals(50, $res['current_age']);
        $this->assertEquals(51, $res['retirement_age']);
        $this->assertEquals(1, $res['tenure_years']);
        $this->assertCount(1, $res['schedule']);
    }
}
