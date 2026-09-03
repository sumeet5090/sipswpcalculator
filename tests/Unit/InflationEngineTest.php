<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\InflationEngine;
use PHPUnit\Framework\TestCase;

class InflationEngineTest extends TestCase
{
    private InflationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new InflationEngine();
    }

    public function testStandardInflationProjection(): void
    {
        // PV = 1,000,000, i = 6%, t = 10 years
        // Future Cost = 1,000,000 * (1.06)^10 = 1,790,847.7
        // Purchasing Power = 1,000,000 / (1.06)^10 = 558,394.78
        $result = $this->engine->calculate(1000000.0, 6.0, 10);

        $this->assertEquals(1000000.0, $result['present_value']);
        $this->assertEquals(6.0, $result['inflation_rate']);
        $this->assertEquals(10, $result['years']);
        $this->assertEquals(1790847.7, $result['future_cost']);
        $this->assertEquals(558394.78, $result['purchasing_power']);
        $this->assertEquals(790847.7, $result['cost_increase']);
        $this->assertEquals(44.16, $result['purchasing_power_loss_percentage']);
        $this->assertCount(10, $result['schedule']);

        // Schedule check
        $this->assertEquals(1, $result['schedule'][0]['year']);
        $this->assertEquals(1060000.0, $result['schedule'][0]['future_cost']);
        $this->assertEquals(943396.23, $result['schedule'][0]['purchasing_power']);
    }

    public function testZeroInflationBoundary(): void
    {
        $result = $this->engine->calculate(500000.0, 0.0, 15);

        $this->assertEquals(500000.0, $result['future_cost']);
        $this->assertEquals(500000.0, $result['purchasing_power']);
        $this->assertEquals(0.0, $result['cost_increase']);
        $this->assertEquals(0.0, $result['purchasing_power_loss_percentage']);
    }

    public function testZeroYearsBoundary(): void
    {
        $result = $this->engine->calculate(500000.0, 7.0, 0);

        $this->assertEquals(500000.0, $result['future_cost']);
        $this->assertEquals(500000.0, $result['purchasing_power']);
        $this->assertEquals(0.0, $result['cost_increase']);
        $this->assertCount(0, $result['schedule']);
    }

    public function testHyperinflationRate(): void
    {
        // PV = 100,000, i = 20%, t = 10 years
        // (1.20)^10 = 6.191736
        $result = $this->engine->calculate(100000.0, 20.0, 10);

        $this->assertEquals(619173.64, $result['future_cost']);
        $this->assertEquals(16150.56, $result['purchasing_power']);
        $this->assertEquals(83.85, $result['purchasing_power_loss_percentage']);
    }

    public function testFourDecadeLongHorizon(): void
    {
        // 40-year horizon at 6.5% inflation
        $result = $this->engine->calculate(50000.0, 6.5, 40);

        $this->assertEquals(40, $result['years']);
        $this->assertCount(40, $result['schedule']);
        $this->assertGreaterThan(600000.0, $result['future_cost']);
        $this->assertLessThan(4500.0, $result['purchasing_power']);
        $this->assertGreaterThan(90.0, $result['purchasing_power_loss_percentage']);
    }

    public function testPurchasingPowerIdentity(): void
    {
        $pv = 250000.0;
        $rate = 7.5;
        $result = $this->engine->calculate($pv, $rate, 20);

        foreach ($result['schedule'] as $row) {
            $year = $row['year'];
            $expectedPv = $row['purchasing_power'] * pow(1.0 + $rate / 100.0, $year);
            $this->assertEqualsWithDelta($pv, $expectedPv, 0.05, "Year {$year} purchasing power identity violated");
        }
    }

    public function testScheduleMonotonicity(): void
    {
        $result = $this->engine->calculate(100000.0, 6.0, 15);

        for ($i = 1; $i < 15; $i++) {
            $prev = $result['schedule'][$i - 1];
            $curr = $result['schedule'][$i];

            $this->assertGreaterThan($prev['future_cost'], $curr['future_cost']);
            $this->assertLessThan($prev['purchasing_power'], $curr['purchasing_power']);
            $this->assertGreaterThan($prev['purchasing_power_loss_percentage'], $curr['purchasing_power_loss_percentage']);
        }
    }

    public function testNegativeInputsClamping(): void
    {
        $result = $this->engine->calculate(-100000.0, -8.0, -5);

        $this->assertEquals(0.0, $result['present_value']);
        $this->assertEquals(0.0, $result['future_cost']);
        $this->assertEquals(0.0, $result['purchasing_power']);
        $this->assertCount(0, $result['schedule']);
    }
}
