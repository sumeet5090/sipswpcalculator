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
}
