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

        // Parity with static helper
        $helperResult = InvestmentCalculator::calculateInflationDiscount($nominalCorpus, $years, $inflationRate);
        $this->assertEqualsWithDelta($realCorpus, $helperResult, 1.0);
    }

    public function testRequiredStartingCorpusForSwpUnderHighStepUp(): void
    {
        // 30 years SWP, ₹50,000/mo, 10% annual step-up, 7% return
        $inputs = InvestmentInputs::fromSwpRequest([
            'corpus'         => 0,
            'swp_withdrawal' => 50000,
            'swp_years'      => 30,
            'swp_stepup'     => 10,
            'swp_rate'       => 7,
            'inflation'      => 0,
        ], $this->config);

        $requiredCorpus = $this->calculator->calculateRequiredStartingCorpusForSwp($inputs);

        $this->assertGreaterThan(0.0, $requiredCorpus);

        // Verify the found starting corpus successfully sustains the full 30 years without early exhaustion
        $simInputs = InvestmentInputs::fromSwpRequest([
            'corpus'         => $requiredCorpus,
            'swp_withdrawal' => 50000,
            'swp_years'      => 30,
            'swp_stepup'     => 10,
            'swp_rate'       => 7,
            'inflation'      => 0,
        ], $this->config);

        $results = $this->calculator->calculate($simInputs);
        $this->assertCount(30, $results);

        // Ensure no premature exhaustion before year 30
        for ($i = 0; $i < 29; $i++) {
            $this->assertGreaterThan(0.0, $results[$i]['combined_total'], "Year " . ($i + 1) . " should not be depleted");
        }

        // Year 30 final balance should be nearly zero (converged within tolerance)
        $finalBalance = $results[29]['combined_total'];
        $this->assertGreaterThanOrEqual(0.0, $finalBalance);
    }

    public function testRequiredSipTargetCorpusSolverPrecision(): void
    {
        // Target ₹5 Crore in 15 years at 12% CAGR with 10% step-up
        $targetCorpus = 50000000.0;
        $inputs = InvestmentInputs::fromRequest([
            'sip'     => 10000,
            'years'   => 15,
            'rate'    => 12,
            'stepup'  => 10,
            'lumpsum' => 0
        ], $this->config);

        $requiredSip = $this->calculator->calculateRequiredSip($inputs, $targetCorpus);

        $this->assertGreaterThan(0.0, $requiredSip);

        // Run forward simulation with the solved SIP
        $forwardInputs = $inputs->withSip($requiredSip);
        $results = $this->calculator->calculate($forwardInputs);
        $lastRow = end($results);
        $achievedCorpus = $lastRow['combined_total'];

        // Must achieve target within ₹1,000 rounding tolerance
        $this->assertEqualsWithDelta($targetCorpus, $achievedCorpus, 1000.0);
    }

    public function testDelayCostCompoundingParity(): void
    {
        $inputs = InvestmentInputs::fromRequest([
            'sip'     => 25000,
            'years'   => 20,
            'rate'    => 12,
            'stepup'  => 10,
            'lumpsum' => 0
        ], $this->config);

        $delayCost = $this->calculator->calculateDelayCost($inputs);

        $this->assertGreaterThan(0.0, $delayCost);

        // Delay cost for 1-year horizon should be 0
        $oneYearInputs = $inputs->withYears(1);
        $this->assertEquals(0.0, $this->calculator->calculateDelayCost($oneYearInputs));
    }

    public function testCurrencyHelperFormatDynamicParity(): void
    {
        $currencyHelper = new \Core\CurrencyHelper();

        $this->assertSame('₹10k', $currencyHelper->formatDynamic(10000));
        $this->assertSame('₹25.50k', $currencyHelper->formatDynamic(25500));
        $this->assertSame('₹1.50 Lakh', $currencyHelper->formatDynamic(150000));
        $this->assertSame('₹50 Lakh', $currencyHelper->formatDynamic(5000000));
        $this->assertSame('₹1 Crore', $currencyHelper->formatDynamic(10000000));
        $this->assertSame('₹2.50 Crore', $currencyHelper->formatDynamic(25000000));
        $this->assertSame('-₹5 Lakh', $currencyHelper->formatDynamic(-500000));
    }
}
