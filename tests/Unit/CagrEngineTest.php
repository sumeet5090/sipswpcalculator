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
}
