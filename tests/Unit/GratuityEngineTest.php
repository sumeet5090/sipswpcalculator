<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\GratuityEngine;
use Core\Inputs\GratuityInputs;
use Core\Strategies\GratuityStrategy;
use PHPUnit\Framework\TestCase;

final class GratuityEngineTest extends TestCase
{
    public function testStandardGratuityCalculationForFiveYears(): void
    {
        // 15/26 * 50,000 * 5 = 144,230.769... -> rounded to 144,231
        $res = GratuityEngine::calculate(50000.0, 5, 0, true);

        $this->assertTrue($res['is_eligible']);
        $this->assertTrue($res['is_covered_by_act']);
        $this->assertSame(5, $res['effective_years']);
        $this->assertEqualsWithDelta(144231.0, $res['gratuity_amount'], 1.0);
        $this->assertEqualsWithDelta(144231.0, $res['tax_exempt_amount'], 1.0);
        $this->assertSame(0.0, $res['taxable_amount']);
    }

    public function testSixMonthRoundingRuleRoundsUpWhenExceedingSixMonths(): void
    {
        // 5 years 7 months -> 6 effective years
        // 15/26 * 50,000 * 6 = 173,076.92... -> rounded to 173,077
        $res = GratuityEngine::calculate(50000.0, 5, 7, true);

        $this->assertTrue($res['is_eligible']);
        $this->assertSame(6, $res['effective_years']);
        $this->assertEqualsWithDelta(173077.0, $res['gratuity_amount'], 1.0);
    }

    public function testSixMonthRoundingRuleRoundsDownWhenSixMonthsOrLess(): void
    {
        // 5 years 6 months -> 5 effective years
        $res = GratuityEngine::calculate(50000.0, 5, 6, true);

        $this->assertTrue($res['is_eligible']);
        $this->assertSame(5, $res['effective_years']);
        $this->assertEqualsWithDelta(144231.0, $res['gratuity_amount'], 1.0);
    }

    public function testNotCoveredByActUsesThirtyDayDivisorAndNoFractionalRounding(): void
    {
        // Not covered: 5 years 7 months -> 5 completed years only
        // 15/30 * 50,000 * 5 = 125,000
        $res = GratuityEngine::calculate(50000.0, 5, 7, false);

        $this->assertFalse($res['is_covered_by_act']);
        $this->assertTrue($res['is_eligible']);
        $this->assertSame(5, $res['effective_years']);
        $this->assertEqualsWithDelta(125000.0, $res['gratuity_amount'], 1.0);
    }

    public function testServiceBelowVestingThresholdReturnsZeroGratuity(): void
    {
        // 3 years of service -> Below 5-year statutory vesting cliff
        $res = GratuityEngine::calculate(60000.0, 3, 0, true);

        $this->assertFalse($res['is_eligible']);
        $this->assertSame(0.0, $res['gratuity_amount']);
        $this->assertSame(0.0, $res['tax_exempt_amount']);
        $this->assertSame(0.0, $res['taxable_amount']);
    }

    public function testStatutoryTaxExemptionCeilingAtTwentyLakhs(): void
    {
        // 15/26 * 150,000 * 30 = 2,596,153.84... -> 2,596,154
        // Tax-exempt capped at 20,00,000; Taxable = 5,96,154
        $res = GratuityEngine::calculate(150000.0, 30, 0, true);

        $this->assertTrue($res['is_eligible']);
        $this->assertEqualsWithDelta(2596154.0, $res['gratuity_amount'], 1.0);
        $this->assertEqualsWithDelta(2000000.0, $res['tax_exempt_amount'], 1.0);
        $this->assertEqualsWithDelta(596154.0, $res['taxable_amount'], 1.0);
    }

    public function testScheduleAccrualProgressionAndVestingCliff(): void
    {
        $res = GratuityEngine::calculate(50000.0, 6, 0, true);
        $schedule = $res['schedule'];

        $this->assertCount(6, $schedule);

        // Years 1 to 4 should be marked not vested
        for ($i = 0; $i < 4; $i++) {
            $this->assertFalse($schedule[$i]['is_vested']);
            $this->assertGreaterThan(0.0, $schedule[$i]['accrued_amount']);
        }

        // Years 5 and 6 should be marked vested
        $this->assertTrue($schedule[4]['is_vested']);
        $this->assertTrue($schedule[5]['is_vested']);
    }

    public function testGratuityInputsValueObject(): void
    {
        $inputs = new GratuityInputs(75000.0, 8, 4, true);

        $this->assertSame(75000.0, $inputs->getMonthlySalary());
        $this->assertSame(8, $inputs->getTenureYears());
        $this->assertSame(4, $inputs->getTenureMonths());
        $this->assertTrue($inputs->isCoveredByAct());

        $array = $inputs->toArray();
        $this->assertSame(75000.0, $array['gratuity_salary']);
        $this->assertSame(8, $array['gratuity_years']);
        $this->assertSame(4, $array['gratuity_months']);
        $this->assertSame(1, $array['gratuity_covered']);
    }
}
