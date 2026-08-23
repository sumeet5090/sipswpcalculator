<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates that CurrencyHelper produces calibrated rate realism subtext across return bands.
 */
final class RateRealismSubtextTest extends TestCase
{
    private function getRateSubtext(float $rate): string
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'format_rate_subtext',
            'rate' => $rate
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result['subtext'];
    }

    public function testConservativeDebtSubtext(): void
    {
        $subtext = $this->getRateSubtext(6.5);
        $this->assertStringContainsString('Conservative Debt', $subtext);
    }

    public function testBalancedHybridSubtext(): void
    {
        $subtext = $this->getRateSubtext(9.5);
        $this->assertStringContainsString('Balanced Hybrid', $subtext);
    }

    public function testNiftyFifteenYearBenchmarkSubtext(): void
    {
        $subtext = $this->getRateSubtext(12.0);
        $this->assertStringContainsString('Nifty 50 15Y Benchmark', $subtext);
    }

    public function testAggressiveMidSmallCapSubtext(): void
    {
        $subtext = $this->getRateSubtext(14.5);
        $this->assertStringContainsString('Aggressive Mid/Small Cap', $subtext);
    }

    public function testHighCagrRealismCautionSubtext(): void
    {
        $subtext = $this->getRateSubtext(18.0);
        $this->assertStringContainsString('Exceeds Nifty 50 15Y Baseline', $subtext);
        $this->assertStringContainsString('⚠️', $subtext);
    }
}
