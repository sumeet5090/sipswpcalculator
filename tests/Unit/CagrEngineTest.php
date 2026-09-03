<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\CagrEngine;
use PHPUnit\Framework\TestCase;

class CagrEngineTest extends TestCase
{
    private CagrEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CagrEngine();
    }

    public function testStandardCagrCalculation(): void
    {
        // 100,000 grew to 200,000 in 5 years
        // CAGR = (200,000 / 100,000)^(1/5) - 1 = 2^(0.2) - 1 = 14.8698%
        $result = $this->engine->calculate(100000.0, 200000.0, 5.0);

        $this->assertEquals(100000.0, $result['beginning_value']);
        $this->assertEquals(200000.0, $result['ending_value']);
        $this->assertEquals(5.0, $result['years']);
        $this->assertEquals(14.8698, $result['cagr_percentage']);
        $this->assertEquals(100.0, $result['absolute_return_percentage']);
        $this->assertEquals(100000.0, $result['total_gain']);
        $this->assertEquals(2.0, $result['multiplier']);
    }

    public function testFractionalYearsCagr(): void
    {
        // 50,000 grew to 75,000 in 2.5 years
        // CAGR = (75,000 / 50,000)^(1/2.5) - 1 = (1.5)^(0.4) - 1 = 17.6079%
        $result = $this->engine->calculate(50000.0, 75000.0, 2.5);

        $this->assertEquals(17.6079, $result['cagr_percentage']);
        $this->assertEquals(50.0, $result['absolute_return_percentage']);
        $this->assertEquals(25000.0, $result['total_gain']);
        $this->assertEquals(1.5, $result['multiplier']);
    }

    public function testNegativeReturnsLossCagr(): void
    {
        // 100,000 dropped to 60,000 in 3 years
        // CAGR = (60,000 / 100,000)^(1/3) - 1 = (0.6)^(0.33333) - 1 = -15.6567%
        $result = $this->engine->calculate(100000.0, 60000.0, 3.0);

        $this->assertEquals(-15.6567, $result['cagr_percentage']);
        $this->assertEquals(-40.0, $result['absolute_return_percentage']);
        $this->assertEquals(-40000.0, $result['total_gain']);
        $this->assertEquals(0.6, $result['multiplier']);
    }

    public function testTotalLossBoundary(): void
    {
        $result = $this->engine->calculate(100000.0, 0.0, 4.0);

        $this->assertEquals(-100.0, $result['cagr_percentage']);
        $this->assertEquals(-100.0, $result['absolute_return_percentage']);
        $this->assertEquals(-100000.0, $result['total_gain']);
    }

    public function testInvalidBeginningValueThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(0.0, 100000.0, 3.0);
    }

    public function testInvalidYearsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(100000.0, 200000.0, 0.0);
    }

    public function testNegativeYearsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(100000.0, 200000.0, -2.5);
    }

    public function testZeroGrowthFlatReturn(): void
    {
        // 100,000 remained 100,000 over 5 years
        $result = $this->engine->calculate(100000.0, 100000.0, 5.0);

        $this->assertEquals(0.0, $result['cagr_percentage']);
        $this->assertEquals(0.0, $result['absolute_return_percentage']);
        $this->assertEquals(0.0, $result['total_gain']);
        $this->assertEquals(1.0, $result['multiplier']);
    }

    public function testSevereCapitalLossNearZero(): void
    {
        // 100,000 wiped out to ₹1 in 5 years
        $result = $this->engine->calculate(100000.0, 1.0, 5.0);

        $this->assertLessThan(-80.0, $result['cagr_percentage']);
        $this->assertEquals(-99.999, $result['absolute_return_percentage']);
        $this->assertEquals(-99999.0, $result['total_gain']);
        $this->assertEquals(0.0, $result['multiplier']);
    }

    public function testExtremeMultibaggerGrowth(): void
    {
        // ₹1 Lakh to ₹50 Lakh (50x multibagger) in 7 years
        // (50)^(1/7) - 1 = 74.8679%
        $result = $this->engine->calculate(100000.0, 5000000.0, 7.0);

        $this->assertEquals(74.8679, $result['cagr_percentage']);
        $this->assertEquals(4900.0, $result['absolute_return_percentage']);
        $this->assertEquals(4900000.0, $result['total_gain']);
        $this->assertEquals(50.0, $result['multiplier']);
    }

    public function testFineGrainFractionalYears(): void
    {
        // 3-month investment (0.25 years): 100,000 to 105,000
        // (1.05)^4 - 1 = 21.5506%
        $result = $this->engine->calculate(100000.0, 105000.0, 0.25);

        $this->assertEquals(21.5506, $result['cagr_percentage']);
        $this->assertEquals(5.0, $result['absolute_return_percentage']);
        $this->assertEquals(5000.0, $result['total_gain']);
        $this->assertEquals(1.05, $result['multiplier']);
    }

    public function testMicroValueAndMegaValue(): void
    {
        // Micro value: ₹100 to ₹250 in 2 years
        $micro = $this->engine->calculate(100.0, 250.0, 2.0);
        $this->assertEquals(58.1139, $micro['cagr_percentage']);
        $this->assertEquals(2.5, $micro['multiplier']);

        // Mega value: ₹500 Crore to ₹1,200 Crore in 6 years
        // (1200 / 500)^(1/6) - 1 = (2.4)^(0.166667) - 1 = 15.7094%
        $mega = $this->engine->calculate(5000000000.0, 12000000000.0, 6.0);
        $this->assertEquals(15.7094, $mega['cagr_percentage']);
        $this->assertEquals(140.0, $mega['absolute_return_percentage']);
        $this->assertEquals(2.4, $mega['multiplier']);
    }
}
