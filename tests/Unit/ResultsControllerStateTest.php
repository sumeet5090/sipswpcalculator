<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates calculation result state vectors and mathematical identity.
 */
final class ResultsControllerStateTest extends TestCase
{
    public function testCalculationResultDataIntegrity(): void
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'calculate_summary_metrics',
            'sip' => 25000,
            'years' => 20,
            'rate' => 14,
            'stepup' => 10
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node runner must return valid JSON');
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['total']);
        $this->assertGreaterThan(0, $result['invested']);
        $this->assertGreaterThan(0, $result['gains']);
        $this->assertEqualsWithDelta(
            $result['total'],
            $result['invested'] + $result['gains'],
            1.0,
            'Accounting Identity: Total Wealth must equal Invested + Gains'
        );
    }
}
