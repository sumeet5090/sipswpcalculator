<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\EmiEngine;
use PHPUnit\Framework\TestCase;

class EmiEngineTest extends TestCase
{
    private EmiEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new EmiEngine();
    }

    public function testStandardHomeLoanEmi(): void
    {
        // P = 2,500,000, r = 8.5%, tenure = 20 years (240 months)
        // Standard banking formula check:
        // monthly rate = 8.5 / 1200 = 0.00708333
        // factor = (1 + 0.00708333)^240 = 5.435747
        // EMI = 2,500,000 * 0.00708333 * 5.435747 / 4.435747 = 21,695.57
        $result = $this->engine->calculate(2500000.0, 8.5, 20);

        $this->assertEquals(2500000.0, $result['principal']);
        $this->assertEquals(8.5, $result['annual_rate']);
        $this->assertEquals(20, $result['tenure_years']);
        $this->assertEquals(21695.58, $result['monthly_emi']);
        $this->assertEquals(5206939.4, $result['total_amount_payable']);
        $this->assertEquals(2706939.4, $result['total_interest']);
        $this->assertEquals(108.28, $result['interest_ratio_percentage']);
        $this->assertCount(20, $result['schedule']);

        // Schedule assertions
        $this->assertEquals(1, $result['schedule'][0]['year']);
        $this->assertEquals(2500000.0, $result['schedule'][0]['opening_balance']);
        // Final year balance must be 0
        $this->assertEquals(0.0, $result['schedule'][19]['closing_balance']);
    }

    public function testZeroInterestLoan(): void
    {
        // P = 120,000, r = 0%, tenure = 1 year
        // EMI = 120,000 / 12 = 10,000
        $result = $this->engine->calculate(120000.0, 0.0, 1);

        $this->assertEquals(10000.0, $result['monthly_emi']);
        $this->assertEquals(120000.0, $result['total_amount_payable']);
        $this->assertEquals(0.0, $result['total_interest']);
        $this->assertEquals(0.0, $result['interest_ratio_percentage']);
        $this->assertEquals(0.0, $result['schedule'][0]['closing_balance']);
    }

    public function testInvalidPrincipalThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(0.0, 9.0, 5);
    }

    public function testInvalidTenureThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(500000.0, 9.0, 0);
    }

    public function testMaximumThirtyYearsHomeLoanConvergence(): void
    {
        // 30-year home loan: ₹50 Lakh at 9%
        $principal = 5000000.0;
        $result = $this->engine->calculate($principal, 9.0, 30);

        $this->assertEquals(30, $result['tenure_years']);
        $this->assertCount(30, $result['schedule']);
        $this->assertEquals(0.0, $result['schedule'][29]['closing_balance']);

        $sumPrincipal = 0.0;
        $sumInterest = 0.0;
        for ($i = 0; $i < 30; $i++) {
            $row = $result['schedule'][$i];
            $sumPrincipal += $row['principal_paid'];
            $sumInterest += $row['interest_paid'];
            $this->assertEqualsWithDelta($row['opening_balance'] - $row['principal_paid'], $row['closing_balance'], 0.05);

            if ($i > 0) {
                $this->assertEqualsWithDelta($result['schedule'][$i - 1]['closing_balance'], $row['opening_balance'], 0.05);
            }
        }

        $this->assertEqualsWithDelta($principal, $sumPrincipal, 0.05);
        $this->assertEqualsWithDelta($result['total_interest'], $sumInterest, 0.05);
    }

    public function testShortOneYearLoan(): void
    {
        // 1-year consumer loan: ₹6 Lakh at 12%
        $result = $this->engine->calculate(600000.0, 12.0, 1);

        $this->assertEquals(1, $result['tenure_years']);
        $this->assertCount(1, $result['schedule']);
        $this->assertEquals(53309.27, $result['monthly_emi']);
        $this->assertEquals(0.0, $result['schedule'][0]['closing_balance']);
        $this->assertEquals(600000.0, $result['schedule'][0]['principal_paid']);
    }

    public function testHighInterestPersonalLoan(): void
    {
        // Unsecured personal loan: ₹5 Lakh at 21% for 3 years
        $result = $this->engine->calculate(500000.0, 21.0, 3);

        $this->assertEquals(18837.53, $result['monthly_emi']);
        $this->assertGreaterThan(170000.0, $result['total_interest']);
        $this->assertGreaterThan(30.0, $result['interest_ratio_percentage']);
    }

    public function testLowInterestSubsidizedLoan(): void
    {
        // Priority sector agricultural/education loan: ₹10 Lakh at 4.5% for 10 years
        $result = $this->engine->calculate(1000000.0, 4.5, 10);

        $this->assertEquals(10363.84, $result['monthly_emi']);
        $this->assertLessThan(250000.0, $result['total_interest']);
        $this->assertEquals(0.0, $result['schedule'][9]['closing_balance']);
    }

    public function testInstitutionalCommercialLoan(): void
    {
        // ₹10 Crore commercial mortgage at 8.75% for 15 years
        $principal = 100000000.0;
        $result = $this->engine->calculate($principal, 8.75, 15);

        $this->assertEquals($principal, $result['principal']);
        $this->assertGreaterThan(990000.0, $result['monthly_emi']);
        $this->assertEquals(0.0, $result['schedule'][14]['closing_balance']);
    }
}
