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
}
