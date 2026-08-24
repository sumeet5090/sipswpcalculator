<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates Section 112A Tax-Harvesting calculation engine across diverse financial vectors.
 */
final class TaxHarvestingCalculationTest extends TestCase
{
    private function calculateTaxHarvesting(array $inputs): array
    {
        $nodeScript = __DIR__ . '/../run_js_calc.js';
        $payload = json_encode([
            'action' => 'calculate_tax_harvesting',
            'inputs' => $inputs
        ], JSON_THROW_ON_ERROR);

        $cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg($payload);
        $output = shell_exec($cmd);
        $result = json_decode((string) $output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertNotNull($result, 'Node execution result must be valid JSON');
        $this->assertTrue($result['success'], 'Node execution must report success');

        return $result['harvest'];
    }

    public function testTenYearSipTaxHarvestingAlpha(): void
    {
        // ₹25,000 monthly SIP for 10 years @ 12% CAGR
        $inputs = [
            'sip' => 25000,
            'years' => 10,
            'rate' => 12,
            'stepup' => 0,
            'inflation' => 0,
            'lumpsum' => 0,
            'enable_swp' => false,
            'ltcg_exemption' => 125000,
            'ltcg_tax_rate' => 0.125
        ];

        $res = $this->calculateTaxHarvesting($inputs);

        $this->assertGreaterThan(0, $res['standardTax']);
        $this->assertGreaterThan(0, $res['cumulativeSavings']);
        $this->assertLessThan($res['standardTax'], $res['harvestedTax']);
        // Over 10 years, max exemption realized is up to 10 * 1.25L = 12.5L
        $this->assertLessThanOrEqual(1250000, $res['totalHarvestedGains']);
    }

    public function testZeroTaxWhenGainsUnderExemption(): void
    {
        // ₹1,000 monthly SIP for 1 year @ 6%
        $inputs = [
            'sip' => 1000,
            'years' => 1,
            'rate' => 6,
            'stepup' => 0,
            'inflation' => 0,
            'lumpsum' => 0,
            'enable_swp' => false,
            'ltcg_exemption' => 125000,
            'ltcg_tax_rate' => 0.125
        ];

        $res = $this->calculateTaxHarvesting($inputs);

        $this->assertEquals(0, $res['standardTax']);
        $this->assertEquals(0, $res['harvestedTax']);
        $this->assertEquals(0, $res['cumulativeSavings']);
    }
}
