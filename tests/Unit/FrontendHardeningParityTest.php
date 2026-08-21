<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigService;

class FrontendHardeningParityTest extends TestCase
{
    private ConfigService $config;
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->config = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $this->calculator = new InvestmentCalculator();
    }

    public function testTaxExemptionFloorClampingWhenGainsBelowThreshold(): void
    {
        // Gains below statutory ₹1.25L exemption
        $inputs = InvestmentInputs::fromRequest([
            'sip' => 1000,
            'years' => 1,
            'rate' => 10,
            'stepup' => 0,
            'lumpsum' => 0
        ], $this->config);

        $results = $this->calculator->calculate($inputs);
        $lastRow = end($results);

        $totalInvested = $lastRow['cumulative_invested'];
        $preTaxCorpus = $lastRow['combined_total'];
        $preTaxGains = $preTaxCorpus - $totalInvested;

        $this->assertLessThan(125000.0, $preTaxGains);

        // Taxable gains must clamp to 0, and LTCG tax must be 0
        $taxableGains = max(0.0, $preTaxGains - 125000.0);
        $this->assertEquals(0.0, $taxableGains);

        $ltcgTax = $lastRow['ltcg_tax'] ?? 0.0;
        $this->assertEquals(0.0, $ltcgTax);
        $this->assertEquals($preTaxCorpus, $lastRow['post_tax_total']);
    }

    public function testTaxExemptionAboveThreshold(): void
    {
        // Wealth creation resulting in gains > ₹1.25L
        $inputs = InvestmentInputs::fromRequest([
            'sip' => 25000,
            'years' => 10,
            'rate' => 12,
            'stepup' => 10,
            'lumpsum' => 0
        ], $this->config);

        $results = $this->calculator->calculate($inputs);
        $lastRow = end($results);

        $totalInvested = $lastRow['cumulative_invested'];
        $preTaxCorpus = $lastRow['combined_total'];
        $preTaxGains = $preTaxCorpus - $totalInvested;

        $this->assertGreaterThan(125000.0, $preTaxGains);

        $taxableGains = max(0.0, $preTaxGains - 125000.0);
        $expectedTax = $taxableGains * 0.125;

        $this->assertEqualsWithDelta($expectedTax, $lastRow['ltcg_tax'], 1.0);
        $this->assertEqualsWithDelta($preTaxCorpus - $expectedTax, $lastRow['post_tax_total'], 1.0);
    }

    public function testRuleOf72DoublingCalculations(): void
    {
        $computeDoubling = static function (float $rate): ?float {
            if ($rate <= 0.0) {
                return null;
            }
            return 72.0 / $rate;
        };

        // Standard rate of 12%
        $this->assertEquals(6.0, $computeDoubling(12.0));

        // Rate of 8%
        $this->assertEquals(9.0, $computeDoubling(8.0));

        // Guard at 0% and negative rates
        $this->assertNull($computeDoubling(0.0), '0% rate must be guarded to prevent division by zero');
        $this->assertNull($computeDoubling(-5.0), 'Negative rate must return null');
    }

    public function testInflationDiscountingFormulaParity(): void
    {
        $nominalCorpus = 10000000.0; // ₹1 Crore
        $years = 15;
        $inflationRate = 6.0;

        $discountFactor = pow(1.0 + ($inflationRate / 100.0), $years);
        $realCorpus = $nominalCorpus / $discountFactor;

        $this->assertGreaterThan(0.0, $realCorpus);
        $this->assertLessThan($nominalCorpus, $realCorpus);
        $this->assertEqualsWithDelta(4172650.5, $realCorpus, 100.0);
    }
}
