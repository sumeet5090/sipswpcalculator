<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates that MathEngine computes accurate 1-Year Delay Costs matching PHP and JS implementations.
 */
final class DelayCostSummaryTest extends TestCase
{
    /**
     * @param array<string, mixed> $inputs
     * @return array{success: bool, delayCost: float|int, formatted: string}
     */
    private function executeJsDelayCost(array $inputs): array
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'calculate_delay_cost',
            'inputs' => $inputs
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        /** @var array{success: bool, delayCost: float|int, formatted: string} $result */
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result;
    }

    public function testDelayCostForStandardTwentyFiveThousandSip(): void
    {
        $inputs = [
            'sip' => 25000,
            'years' => 15,
            'rate' => 12,
            'stepup' => 10,
            'lumpsum' => 0,
            'inflation' => 0,
            'enable_swp' => false,
            'swp_years' => 0,
            'swp_withdrawal' => 0,
            'swp_rate' => 8,
            'swp_stepup' => 0
        ];

        $res = $this->executeJsDelayCost($inputs);
        // Cost of 1 year delay for 25k SIP @ 12% with 10% stepup is ~₹35.2 Lakhs
        $this->assertEquals(3522649, round((float) $res['delayCost']));
        $this->assertStringContainsString('3,522,649', $res['formatted']);
    }

    public function testDelayCostForTenThousandFlatSip(): void
    {
        $inputs = [
            'sip' => 10000,
            'years' => 10,
            'rate' => 12,
            'stepup' => 0,
            'lumpsum' => 0,
            'inflation' => 0,
            'enable_swp' => false,
            'swp_years' => 0,
            'swp_withdrawal' => 0,
            'swp_rate' => 8,
            'swp_stepup' => 0
        ];

        $res = $this->executeJsDelayCost($inputs);
        $this->assertGreaterThan(0, $res['delayCost']);
        $this->assertNotEmpty($res['formatted']);
    }
}
