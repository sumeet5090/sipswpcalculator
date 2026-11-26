<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\EpfEngine;
use PHPUnit\Framework\TestCase;

class EpfEngineTest extends TestCase
{
    public function testDefaultBaselineCalculation(): void
    {
        // 50,000 basic, age 28->58 (30 yrs), 0% VPF, 7% hike, 8.25% ROI, 0 init balance, capped EPS
        $res = EpfEngine::calculate(50000.0, 28, 58, 0.0, 7.0, 8.25, 0.0, true);

        $this->assertEquals(50000.0, $res['basic_salary']);
        $this->assertEquals(28, $res['current_age']);
        $this->assertEquals(58, $res['retirement_age']);
        $this->assertEquals(30, $res['tenure_years']);
        $this->assertEquals(8.25, $res['interest_rate']);
        $this->assertTrue($res['cap_eps']);

        $this->assertGreaterThan(35000000.0, $res['maturity_corpus']);
        $this->assertEqualsWithDelta(
            $res['total_employee_contribution'] + $res['total_employer_contribution'],
            $res['total_invested'],
            1.0
        );
        $this->assertEquals(1250.0 * 12.0 * 30.0, $res['total_eps_diverted']);
        $this->assertCount(30, $res['schedule']);

        $lastYear = end($res['schedule']);
        $this->assertIsArray($lastYear);
        $this->assertEquals(30, $lastYear['year']);
        $this->assertEquals(58, $lastYear['age']);
        $this->assertEquals($res['maturity_corpus'], $lastYear['closing_balance']);
    }

    public function testVpfAnd250kTaxLimitDetection(): void
    {
        // 1,00,000 basic + 20% VPF => 32% of 1L = 32k/mo = 3.84L/yr > 2.5L
        $res = EpfEngine::calculate(100000.0, 30, 58, 20.0, 5.0, 8.25, 0.0, true);
        $this->assertTrue($res['exceeds_250k_cap']);

        // Small basic 20,000, 0% VPF, 0% hike => 28,800/yr < 2.5L
        $resUnder = EpfEngine::calculate(20000.0, 30, 50, 0.0, 0.0, 8.25, 0.0, true);
        $this->assertFalse($resUnder['exceeds_250k_cap']);
    }

    public function testCappedEpsIncreasesEmployerEpf(): void
    {
        $capped = EpfEngine::calculate(50000.0, 28, 58, 0.0, 7.0, 8.25, 0.0, true);
        $uncapped = EpfEngine::calculate(50000.0, 28, 58, 0.0, 7.0, 8.25, 0.0, false);

        $this->assertGreaterThan($uncapped['total_employer_contribution'], $capped['total_employer_contribution']);
        $this->assertGreaterThan($uncapped['maturity_corpus'], $capped['maturity_corpus']);
        $this->assertLessThan($uncapped['total_eps_diverted'], $capped['total_eps_diverted']);
    }

    public function testExistingBalanceCompounding(): void
    {
        $withZero = EpfEngine::calculate(50000.0, 35, 58, 0.0, 5.0, 8.25, 0.0, true);
        $with5L = EpfEngine::calculate(50000.0, 35, 58, 0.0, 5.0, 8.25, 500000.0, true);

        $this->assertEquals(
            $withZero['total_employee_contribution'] + 500000.0,
            $with5L['total_employee_contribution']
        );
        $this->assertGreaterThan($withZero['maturity_corpus'] + 500000.0, $with5L['maturity_corpus']);
    }

    public function testBoundaryClamping(): void
    {
        $res = EpfEngine::calculate(100.0, 10, 30, -5.0, -2.0, 2.0, -500.0, true);

        $this->assertEquals(EpfEngine::MIN_BASIC_SALARY, $res['basic_salary']);
        $this->assertEquals(EpfEngine::MIN_CURRENT_AGE, $res['current_age']);
        $this->assertEquals(30, $res['retirement_age']);
        $this->assertEquals(0.0, $res['vpf_percent']);
        $this->assertEquals(0.0, $res['salary_hike']);
        $this->assertEquals(EpfEngine::MIN_INTEREST_RATE, $res['interest_rate']);
        $this->assertEquals(0.0, $res['current_balance']);
    }
}
