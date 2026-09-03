<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\CompoundInterestEngine;
use PHPUnit\Framework\TestCase;

class CompoundInterestEngineTest extends TestCase
{
    private CompoundInterestEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CompoundInterestEngine();
    }

    public function testStandardAnnualCompounding(): void
    {
        // P = 100,000, r = 10%, t = 5 years, n = 1
        // A = 100,000 * (1.10)^5 = 161,051
        $result = $this->engine->calculate(100000.0, 10.0, 5, 1);

        $this->assertEquals(100000.0, $result['principal']);
        $this->assertEquals(161051.0, $result['final_amount']);
        $this->assertEquals(61051.0, $result['total_interest']);
        $this->assertEquals(10.0, $result['effective_annual_rate']);
        $this->assertEquals(7.2, $result['rule_of_72_years']);
        $this->assertCount(5, $result['schedule']);
        $this->assertEquals(1, $result['schedule'][0]['year']);
        $this->assertEquals(100000.0, $result['schedule'][0]['opening_balance']);
        $this->assertEquals(10000.0, $result['schedule'][0]['interest_earned']);
        $this->assertEquals(110000.0, $result['schedule'][0]['closing_balance']);
    }

    public function testMonthlyCompounding(): void
    {
        // P = 50,000, r = 12%, t = 3 years, n = 12
        // A = 50,000 * (1 + 0.12/12)^(12*3) = 50,000 * (1.01)^36 = 71,538.44
        $result = $this->engine->calculate(50000.0, 12.0, 3, 12);

        $this->assertEquals(50000.0, $result['principal']);
        $this->assertEquals(71538.44, $result['final_amount']);
        $this->assertEquals(21538.44, $result['total_interest']);
        $this->assertEquals(12.6825, $result['effective_annual_rate']); // EAR = (1.01)^12 - 1 = 12.6825%
        $this->assertEquals(6.0, $result['rule_of_72_years']);
        $this->assertCount(3, $result['schedule']);
    }

    public function testQuarterlyCompounding(): void
    {
        // P = 200,000, r = 8%, t = 2 years, n = 4
        // A = 200,000 * (1 + 0.08/4)^(4*2) = 200,000 * (1.02)^8 = 234,331.88
        $result = $this->engine->calculate(200000.0, 8.0, 2, 4);

        $this->assertEquals(200000.0, $result['principal']);
        $this->assertEquals(234331.88, $result['final_amount']);
        $this->assertEquals(34331.88, $result['total_interest']);
        $this->assertEquals(8.2432, $result['effective_annual_rate']);
    }

    public function testZeroValuesAndBoundaryGuards(): void
    {
        // Zero Principal
        $resZeroP = $this->engine->calculate(0.0, 12.0, 5, 1);
        $this->assertEquals(0.0, $resZeroP['final_amount']);
        $this->assertEquals(0.0, $resZeroP['total_interest']);
        $this->assertCount(0, $resZeroP['schedule']);

        // Zero Years
        $resZeroT = $this->engine->calculate(100000.0, 12.0, 0, 1);
        $this->assertEquals(100000.0, $resZeroT['final_amount']);
        $this->assertEquals(0.0, $resZeroT['total_interest']);
        $this->assertCount(0, $resZeroT['schedule']);

        // Zero Rate
        $resZeroR = $this->engine->calculate(100000.0, 0.0, 5, 1);
        $this->assertEquals(100000.0, $resZeroR['final_amount']);
        $this->assertEquals(0.0, $resZeroR['total_interest']);
        $this->assertEquals(0.0, $resZeroR['effective_annual_rate']);
        $this->assertNull($resZeroR['rule_of_72_years']);

        // Negative value clamping
        $resNeg = $this->engine->calculate(-5000.0, -10.0, -2, 0);
        $this->assertEquals(0.0, $resNeg['principal']);
        $this->assertEquals(0.0, $resNeg['final_amount']);
    }

    public function testDailyCompoundingFrequency(): void
    {
        // P = 1,000,000, r = 7%, t = 1 year, n = 365
        // A = 1,000,000 * (1 + 0.07/365)^365 = 1,072,500.98
        $result = $this->engine->calculate(1000000.0, 7.0, 1, 365);

        $this->assertEquals(1000000.0, $result['principal']);
        $this->assertEquals(1072500.98, $result['final_amount']);
        $this->assertEquals(72500.98, $result['total_interest']);
        $this->assertEquals(7.2501, $result['effective_annual_rate']);
        $this->assertCount(1, $result['schedule']);
    }

    public function testSemiAnnualCompoundingFrequency(): void
    {
        // P = 500,000, r = 9%, t = 3 years, n = 2
        // A = 500,000 * (1 + 0.09/2)^6 = 500,000 * (1.045)^6 = 651,130.06
        $result = $this->engine->calculate(500000.0, 9.0, 3, 2);

        $this->assertEquals(500000.0, $result['principal']);
        $this->assertEquals(651130.06, $result['final_amount']);
        $this->assertEquals(151130.06, $result['total_interest']);
        $this->assertEquals(9.2025, $result['effective_annual_rate']);
        $this->assertCount(3, $result['schedule']);
    }

    public function testFractionalInterestRates(): void
    {
        // P = 750,000, r = 8.375%, t = 4 years, n = 12
        $result = $this->engine->calculate(750000.0, 8.375, 4, 12);

        $this->assertEquals(750000.0, $result['principal']);
        $this->assertGreaterThan(1000000.0, $result['final_amount']);
        $this->assertEquals(8.7041, $result['effective_annual_rate']);
        $this->assertCount(4, $result['schedule']);
    }

    public function testMultiDecadeCompoundingScheduleIntegrity(): void
    {
        // 30-year horizon monthly compounding
        $principal = 1000000.0;
        $result = $this->engine->calculate($principal, 12.0, 30, 12);

        $this->assertCount(30, $result['schedule']);
        $sumInterest = 0.0;

        for ($i = 0; $i < 30; $i++) {
            $row = $result['schedule'][$i];
            $this->assertEquals($i + 1, $row['year']);
            $this->assertGreaterThan(0.0, $row['interest_earned']);
            $this->assertEqualsWithDelta($row['opening_balance'] + $row['interest_earned'], $row['closing_balance'], 0.05);

            if ($i > 0) {
                $prevRow = $result['schedule'][$i - 1];
                $this->assertEqualsWithDelta($prevRow['closing_balance'], $row['opening_balance'], 0.05);
            }
            $sumInterest += $row['interest_earned'];
        }

        $this->assertEqualsWithDelta($result['total_interest'], $sumInterest, 0.05);
        $this->assertEqualsWithDelta($principal + $result['total_interest'], $result['final_amount'], 0.05);
    }

    public function testHighPrincipalInstitutionalScaleSafety(): void
    {
        // ₹100 Crore institutional principal
        $principal = 1000000000.0;
        $result = $this->engine->calculate($principal, 10.0, 10, 4);

        $this->assertEquals($principal, $result['principal']);
        $this->assertGreaterThan(2500000000.0, $result['final_amount']);
        $this->assertEqualsWithDelta($principal + $result['total_interest'], $result['final_amount'], 0.05);
    }

    public function testRuleOf72VariousRates(): void
    {
        $res2 = $this->engine->calculate(10000.0, 2.0, 1, 1);
        $this->assertEquals(36.0, $res2['rule_of_72_years']);

        $res6 = $this->engine->calculate(10000.0, 6.0, 1, 1);
        $this->assertEquals(12.0, $res6['rule_of_72_years']);

        $res9 = $this->engine->calculate(10000.0, 9.0, 1, 1);
        $this->assertEquals(8.0, $res9['rule_of_72_years']);

        $res18 = $this->engine->calculate(10000.0, 18.0, 1, 1);
        $this->assertEquals(4.0, $res18['rule_of_72_years']);
    }
}
