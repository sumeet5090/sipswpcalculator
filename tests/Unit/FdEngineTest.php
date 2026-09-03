<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\FdEngine;
use PHPUnit\Framework\TestCase;

class FdEngineTest extends TestCase
{
    public function testStandardQuarterlyCompoundingCumulativeFd(): void
    {
        // ₹1,00,000 at 7.0% for 1 year with quarterly compounding
        // A = 100000 * (1 + 7/400)^4 = 100000 * (1.0175)^4 = 1,07,185.90
        $result = FdEngine::calculate(100000.0, 7.0, 1.0, false, 'cumulative');

        $this->assertEquals(100000.0, $result['principal']);
        $this->assertEquals(7.0, $result['effective_rate']);
        $this->assertEquals(1.0, $result['duration_years']);
        $this->assertEquals(107185.90, $result['maturity_amount']);
        $this->assertEquals(7185.90, $result['total_interest']);
        $this->assertEquals(0.0, $result['periodic_payout']);
        $this->assertCount(1, $result['yearly_schedule']);
    }

    public function testSeniorCitizenRateBonus(): void
    {
        // General: 7.0%, Senior: 7.5%
        $general = FdEngine::calculate(500000.0, 7.0, 3.0, false, 'cumulative');
        $senior = FdEngine::calculate(500000.0, 7.0, 3.0, true, 'cumulative');

        $this->assertEquals(7.0, $general['effective_rate']);
        $this->assertEquals(7.5, $senior['effective_rate']);
        $this->assertTrue($senior['is_senior_citizen']);
        $this->assertGreaterThan($general['maturity_amount'], $senior['maturity_amount']);
    }

    public function testNonCumulativeQuarterlyPayout(): void
    {
        // ₹10,00,000 at 8.0% for 2 years with quarterly payout
        // Payout = 1000000 * 8 / 400 = ₹20,000 per quarter
        $result = FdEngine::calculate(1000000.0, 8.0, 2.0, false, 'quarterly');

        $this->assertEquals(1000000.0, $result['maturity_amount']); // Principal returned
        $this->assertEquals(20000.0, $result['periodic_payout']);
        $this->assertEquals(160000.0, $result['total_interest']); // 8 quarters * 20,000
        $this->assertCount(2, $result['yearly_schedule']);
    }

    public function testSection194aTdsThresholdEstimation(): void
    {
        // ₹10,00,000 at 8.0% produces ₹80,000 annual interest (exceeds ₹40,000 general threshold)
        // TDS = 10% of ₹80,000 = ₹8,000/year
        $general = FdEngine::calculate(1000000.0, 8.0, 1.0, false, 'cumulative');
        $this->assertEquals(8243.22, $general['estimated_annual_tds']); // 10% of total quarterly compound interest ₹82,432.16

        // Small deposit: ₹2,00,000 at 7.0% produces ~₹14,371 interest (below ₹40,000 threshold)
        $small = FdEngine::calculate(200000.0, 7.0, 1.0, false, 'cumulative');
        $this->assertEquals(0.0, $small['estimated_annual_tds']);
    }

    public function testFdInputClampingAndBoundaries(): void
    {
        $clamped = FdEngine::calculate(-10000.0, -5.0, 0.1);
        $this->assertEquals(0.0, $clamped['principal']);
        $this->assertEquals(0.0, $clamped['effective_rate']);
        $this->assertEquals(0.25, $clamped['duration_years']); // Min 0.25 yrs (3 months)
    }

    public function testNonCumulativeMonthlyPayoutFormula(): void
    {
        // ₹12 Lakh at 7.5% with monthly discounted payout
        $principal = 1200000.0;
        $rate = 7.5;
        $result = FdEngine::calculate($principal, $rate, 2.0, false, 'monthly');

        $this->assertEquals($principal, $result['maturity_amount']); // Principal returned
        $this->assertGreaterThan(7400.0, $result['periodic_payout']);
        $this->assertLessThan(7500.0, $result['periodic_payout']);
        // 24 months of monthly payout
        $this->assertEqualsWithDelta($result['periodic_payout'] * 24, $result['total_interest'], 0.10);
    }

    public function testNonCumulativeAnnualPayout(): void
    {
        // ₹10 Lakh at 7.0% for 3 years with annual simple interest payout
        $result = FdEngine::calculate(1000000.0, 7.0, 3.0, false, 'annual');

        $this->assertEquals(1000000.0, $result['maturity_amount']);
        $this->assertEquals(70000.0, $result['periodic_payout']);
        $this->assertEquals(210000.0, $result['total_interest']);
        $this->assertCount(3, $result['yearly_schedule']);
    }

    public function testSeniorCitizenTdsThresholdFiftyThousand(): void
    {
        // Senior threshold is ₹50,000
        // ₹6 Lakh @ 7.5% produces ~₹46,310 interest (below ₹50,000 threshold) -> TDS = 0
        $below = FdEngine::calculate(600000.0, 7.0, 1.0, true, 'cumulative'); // 7.0% + 0.5% = 7.5%
        $this->assertEquals(0.0, $below['estimated_annual_tds']);

        // ₹8 Lakh @ 7.5% produces ~₹61,747 interest (above ₹50,000 threshold) -> TDS = 10%
        $above = FdEngine::calculate(800000.0, 7.0, 1.0, true, 'cumulative');
        $this->assertGreaterThan(6000.0, $above['estimated_annual_tds']);
        $this->assertEqualsWithDelta($above['total_interest'] * 0.10, $above['estimated_annual_tds'], 0.05);
    }

    public function testPostTaxYield30PercentSlab(): void
    {
        $result = FdEngine::calculate(500000.0, 7.0, 3.0, false, 'cumulative');

        $this->assertGreaterThan(4.8, $result['post_tax_yield_30_percent']);
        $this->assertLessThan(5.1, $result['post_tax_yield_30_percent']);
    }

    public function testShortTenureThreeMonthsQuarterly(): void
    {
        // 0.25 years (3 months = 1 quarter)
        $result = FdEngine::calculate(100000.0, 8.0, 0.25, false, 'cumulative');

        $this->assertEquals(0.25, $result['duration_years']);
        $this->assertEquals(102000.0, $result['maturity_amount']);
        $this->assertEquals(2000.0, $result['total_interest']);
        $this->assertCount(1, $result['yearly_schedule']);
    }

    public function testTenYearsMaxTenureSchedule(): void
    {
        // 10 years = 40 compounding quarters
        $result = FdEngine::calculate(1000000.0, 7.0, 10.0, false, 'cumulative');

        $this->assertEquals(10.0, $result['duration_years']);
        $this->assertCount(10, $result['yearly_schedule']);
        $this->assertGreaterThan(2000000.0, $result['maturity_amount']); // > 2x doubling
        $this->assertEqualsWithDelta(1000000.0 + $result['total_interest'], $result['maturity_amount'], 0.05);
    }
}
