<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates chart schedule vector formation and crossover point calculations.
 */
final class ChartManagerParityTest extends TestCase
{
    public function testCrossoverPointDetectionAccuracy(): void
    {
        // 10,000 / mo at 12% p.a. over 15 years
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'calculate_sip_schedule',
            'sip' => 10000,
            'years' => 15,
            'rate' => 12,
            'stepup' => 0
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $schedule = $result['schedule'];

        $crossoverYear = null;
        foreach ($schedule as $row) {
            if ($row['combined_interest'] >= $row['combined_invested']) {
                $crossoverYear = $row['year'];
                break;
            }
        }

        // At 12% CAGR, cumulative returns overtake total investment at Year 11
        $this->assertSame(11, $crossoverYear, 'At 12% SIP, cumulative returns surpass principal at Year 11');
    }
}
