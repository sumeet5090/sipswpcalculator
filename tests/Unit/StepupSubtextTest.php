<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates that CurrencyHelper produces calibrated absolute rupee increments for Step-Up SIP.
 */
final class StepupSubtextTest extends TestCase
{
    private function getStepupSubtext(float $stepup, float $sip): string
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'format_stepup_subtext',
            'stepup' => $stepup,
            'sip' => $sip
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result['subtext'];
    }

    public function testTenPercentStepupOnTwentyFiveThousandSip(): void
    {
        $subtext = $this->getStepupSubtext(10.0, 25000.0);
        // 10% on 25k = +2.5k/mo, Year 2 = 27.5k/mo
        $this->assertStringContainsString('10% p.a.', $subtext);
        $this->assertStringContainsString('+₹2.5k/mo in Yr 2', $subtext);
        $this->assertStringContainsString('₹27.5k/mo', $subtext);
    }

    public function testFivePercentStepupOnFiftyThousandSip(): void
    {
        $subtext = $this->getStepupSubtext(5.0, 50000.0);
        // 5% on 50k = +2.5k/mo, Year 2 = 52.5k/mo
        $this->assertStringContainsString('5% p.a.', $subtext);
        $this->assertStringContainsString('+₹2.5k/mo in Yr 2', $subtext);
        $this->assertStringContainsString('₹52.5k/mo', $subtext);
    }

    public function testZeroStepupSubtext(): void
    {
        $subtext = $this->getStepupSubtext(0.0, 25000.0);
        $this->assertStringContainsString('Constant SIP without annual step-up', $subtext);
    }
}
