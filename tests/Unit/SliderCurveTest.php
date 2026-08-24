<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates SliderCurveHelper non-linear and linear transfer functions via Node runner.
 */
final class SliderCurveTest extends TestCase
{
    private function runSliderCurveTest(float $min, float $max, float $step, string $curve): array
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'slider_curve_test',
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'curve' => $curve
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result;
    }

    public function testLinearCurveExactBoundsAndMonotonicity(): void
    {
        $res = $this->runSliderCurveTest(500, 100000, 500, 'linear');

        $this->assertEquals(500, $res['posToVal0']);
        $this->assertEquals(50500, $res['posToVal50']); // snapped to step 500: 500 + round(99.5)*500 = 50500
        $this->assertEquals(100000, $res['posToVal100']);
        $this->assertEquals(0, $res['valToPosMin']);
        $this->assertEquals(100, $res['valToPosMax']);
        $this->assertTrue($res['monotonic']);
    }

    public function testCurrencyPiecewiseCurveResolvesRetailGranularity(): void
    {
        // Monthly SIP: Min 500, Max 10,00,000, Step 500
        $res = $this->runSliderCurveTest(500, 1000000, 500, 'currency_piecewise');

        // At 0% travel, should be minimum ₹500
        $this->assertEquals(500, $res['posToVal0']);

        // At 50% travel, should hit retail anchor (₹50,000)
        $this->assertEquals(50000, $res['posToVal50']);

        // At 100% travel, should hit max ₹10,00,000
        $this->assertEquals(1000000, $res['posToVal100']);

        // Must be strictly monotonic across all 5% increments
        $this->assertTrue($res['monotonic']);
    }

    public function testPowerQuadraticCurve(): void
    {
        $res = $this->runSliderCurveTest(100000, 100000000, 100000, 'power_quadratic');

        $this->assertEquals(100000, $res['posToVal0']);
        $this->assertEquals(100000000, $res['posToVal100']);
        $this->assertTrue($res['monotonic']);
    }
}
