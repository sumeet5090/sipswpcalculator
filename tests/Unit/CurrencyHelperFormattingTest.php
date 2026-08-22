<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates CurrencyFormatter formatting rules, Indian denomination thresholds, and negative sign placement.
 */
final class CurrencyHelperFormattingTest extends TestCase
{
    public function testIndianLakhAndCroreFormatting(): void
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'format_currency_test',
            'values' => [
                5000 => '₹5k',
                100000 => '₹1 Lakh',
                2500000 => '₹25 Lakh',
                10000000 => '₹1 Crore',
                52500000 => '₹5.25 Crore',
                -50000 => '-₹50k',
                0 => '₹0'
            ]
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'JS CurrencyFormatter must format Lakhs and Crores with exact parity');
    }
}
