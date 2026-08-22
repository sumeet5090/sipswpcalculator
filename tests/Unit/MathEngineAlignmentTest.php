<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Core\InvestmentInputs;
use Core\InvestmentCalculator;

class MathEngineAlignmentTest extends TestCase
{
    /**
     * Helper to run calculations in JS using Node.js.
     */
    private function runJsCalculation(array $inputs): array
    {
        $runnerPath = __DIR__ . '/../run_js_calc.js';
        $this->assertFileExists($runnerPath, "JS runner script not found at $runnerPath");

        // Normalize parameter naming expected by MathEngine.ts
        $jsInputs = [
            'sip'            => $inputs['sip'] ?? 10000.0,
            'years'          => $inputs['years'] ?? 20,
            'rate'           => $inputs['rate'] ?? 12.0,
            'stepup'         => $inputs['stepup'] ?? 10.0,
            'lumpsum'        => $inputs['lumpsum'] ?? 0.0,
            'enable_swp'     => $inputs['enable_swp'] ?? false,
            'swp_withdrawal' => $inputs['swp_withdrawal'] ?? 0.0,
            'swp_years'      => $inputs['swp_years'] ?? 0,
            'swp_stepup'     => $inputs['swp_stepup'] ?? 0.0,
            'swp_rate'       => $inputs['swp_rate'] ?? 8.0,
            'ltcg_exemption' => $inputs['ltcg_exemption'] ?? 125000.0,
            'ltcg_tax_rate'  => $inputs['ltcg_tax_rate'] ?? 0.125,
        ];

        $jsonArg = escapeshellarg(json_encode($jsInputs));
        $cmd = "node --experimental-strip-types " . escapeshellarg($runnerPath) . " {$jsonArg}";

        $output = shell_exec($cmd);
        if (!$output) {
            $this->fail("Node runner failed or outputted empty response.");
        }

        $result = json_decode($output, true);
        if (!is_array($result)) {
            $this->fail("Failed to parse Node.js output: $output");
        }

        return $result;
    }

    /**
     * Verify alignment on various input combinations.
     */
    #[DataProvider('inputProvider')]
    public function testMathAlignment(array $inputsData): void
    {
        // 1. Run PHP math engine
        $inputs = InvestmentInputs::fromRequest($inputsData, new \Services\ConfigService(__DIR__ . '/../../content/calculator_defaults.json'));
        $calculator = new InvestmentCalculator();
        $phpResults = $calculator->calculate($inputs);

        // 2. Run JS math engine
        // We pass the clamped/processed inputs from PHP to the JS engine so they get the same input vectors
        $jsData = [
            'sip' => $inputs->getSip(),
            'years' => $inputs->getYears(),
            'rate' => $inputs->getRate(),
            'stepup' => $inputs->getStepup(),
            'lumpsum' => $inputs->getLumpsum(),
            'enable_swp' => $inputs->isSwpEnabled(),
            'swp_withdrawal' => $inputs->getSwpWithdrawal(),
            'swp_years' => $inputs->getSwpYears(),
            'swp_stepup' => $inputs->getSwpStepup(),
            'swp_rate' => $inputs->getSwpRate(),
            'ltcg_exemption' => $inputs->getLtcgExemption(),
            'ltcg_tax_rate' => $inputs->getLtcgTaxRate(),
        ];
        $jsResults = $this->runJsCalculation($jsData);

        // 3. Compare count
        $this->assertEquals(count($phpResults), count($jsResults), "Yearly records count mismatch");

        // 4. Compare row by row
        foreach ($phpResults as $index => $phpRow) {
            $jsRow = $jsResults[$index];
            $year = $phpRow['year'];

            // Calculate dynamic deltas to account for floating-point precision limitations at astronomical numbers
            $balanceDelta = max(1.0, abs(($phpRow['begin_balance'] ?? 0.0) * 0.00001));
            $totalDelta = max(1.0, abs(($phpRow['combined_total'] ?? 0.0) * 0.00001));
            $interestDelta = max(1.0, abs(($phpRow['interest'] ?? 0.0) * 0.00001));
            $contribDelta = max(1.0, abs(($phpRow['annual_contribution'] ?? 0.0) * 0.00001));
            $cumInvestedDelta = max(1.0, abs(($phpRow['cumulative_invested'] ?? 0.0) * 0.00001));
            $withdrawalDelta = max(1.0, abs(($phpRow['annual_withdrawal'] ?? 0.0) * 0.00001));
            $cumWithdrawalDelta = max(1.0, abs(($phpRow['cumulative_withdrawals'] ?? 0.0) * 0.00001));

            $this->assertEquals($phpRow['year'], $jsRow['year'], "Year mismatch at index $index");
            $this->assertEqualsWithDelta($phpRow['begin_balance'], $jsRow['begin_balance'], $balanceDelta, "begin_balance mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['sip_monthly'], $jsRow['sip_monthly'], 1.0, "sip_monthly mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['annual_contribution'], $jsRow['annual_contribution'], $contribDelta, "annual_contribution mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['cumulative_invested'], $jsRow['cumulative_invested'], $cumInvestedDelta, "cumulative_invested mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['swp_monthly'], $jsRow['swp_monthly'], 1.0, "swp_monthly mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['annual_withdrawal'], $jsRow['annual_withdrawal'], $withdrawalDelta, "annual_withdrawal mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['cumulative_withdrawals'], $jsRow['cumulative_withdrawals'], $cumWithdrawalDelta, "cumulative_withdrawals mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['interest'], $jsRow['interest'], $interestDelta, "interest mismatch at year $year");
            $this->assertEqualsWithDelta($phpRow['combined_total'], $jsRow['combined_total'], $totalDelta, "combined_total mismatch at year $year");
        }
    }

    /**
     * Verify alignment of Required SIP binary search inversion.
     */
    public function testBinarySearchRequiredSipAlignment(): void
    {
        $calculator = new InvestmentCalculator();
        $inputs = InvestmentInputs::fromValues(0.0, 15, 12.0, 10.0, false, 0.0, 0.0, 0, 0.0);
        $targetCorpus = 10000000.0;

        $phpSip = $calculator->calculateRequiredSip($inputs, $targetCorpus);

        $runnerPath = __DIR__ . '/../run_js_calc.js';
        $jsPayload = [
            'action' => 'required_sip',
            'inputs' => [
                'sip' => 0,
                'years' => 15,
                'rate' => 12.0,
                'stepup' => 10.0,
                'lumpsum' => 0.0,
                'enable_swp' => false,
                'swp_withdrawal' => 0,
                'swp_years' => 0,
                'swp_stepup' => 0,
                'swp_rate' => 8
            ],
            'target_corpus' => $targetCorpus
        ];

        $jsonArg = escapeshellarg(json_encode($jsPayload));
        $output = shell_exec("node " . escapeshellarg($runnerPath) . " {$jsonArg}");
        $jsRes = json_decode((string) $output, true);
        $jsSip = (float) ($jsRes['result'] ?? -1);

        $this->assertEqualsWithDelta($phpSip, $jsSip, 1.0, "Required SIP mismatch between PHP and JS");
    }

    /**
     * Verify alignment of SWP Starting Corpus binary search inversion.
     */
    public function testBinarySearchRequiredSwpCorpusAlignment(): void
    {
        $calculator = new InvestmentCalculator();
        $inputs = InvestmentInputs::fromValues(0.0, 0, 0.0, 0.0, true, 50000.0, 5.0, 20, 0.0, 8.0);

        $phpCorpus = $calculator->calculateRequiredStartingCorpusForSwp($inputs);

        $runnerPath = __DIR__ . '/../run_js_calc.js';
        $jsPayload = [
            'action' => 'swp_required_corpus',
            'inputs' => [
                'sip' => 0,
                'years' => 0,
                'rate' => 0,
                'stepup' => 0,
                'lumpsum' => 0,
                'enable_swp' => true,
                'swp_withdrawal' => 50000.0,
                'swp_years' => 20,
                'swp_stepup' => 5.0,
                'swp_rate' => 8.0
            ]
        ];

        $jsonArg = escapeshellarg(json_encode($jsPayload));
        $output = shell_exec("node " . escapeshellarg($runnerPath) . " {$jsonArg}");
        $jsRes = json_decode((string) $output, true);
        $jsCorpus = (float) ($jsRes['result'] ?? -1);

        $this->assertEqualsWithDelta($phpCorpus, $jsCorpus, 1.0, "SWP Required Corpus mismatch between PHP and JS");
    }

    /**
     * Verify alignment of Inflation Discounting.
     */
    public function testInflationDiscountAlignment(): void
    {
        $phpDiscount = InvestmentCalculator::calculateInflationDiscount(10000000.0, 20, 6.0);

        $runnerPath = __DIR__ . '/../run_js_calc.js';
        $jsPayload = [
            'action' => 'inflation_discount',
            'corpus' => 10000000.0,
            'years' => 20,
            'inflation' => 6.0
        ];

        $jsonArg = escapeshellarg(json_encode($jsPayload));
        $output = shell_exec("node " . escapeshellarg($runnerPath) . " {$jsonArg}");
        $jsRes = json_decode((string) $output, true);
        $jsDiscount = (float) ($jsRes['result'] ?? -1);

        $this->assertEqualsWithDelta($phpDiscount, $jsDiscount, 0.01, "Inflation discount mismatch between PHP and JS");
    }

    /**
     * Verify alignment of Delay Cost calculation.
     */
    public function testDelayCostAlignment(): void
    {
        $calculator = new InvestmentCalculator();
        $inputs = InvestmentInputs::fromValues(25000.0, 15, 12.0, 10.0, false, 0.0, 0.0, 0, 0.0);

        $phpDelayCost = $calculator->calculateDelayCost($inputs);

        $runnerPath = __DIR__ . '/../run_js_calc.js';
        $jsPayload = [
            'action' => 'delay_cost',
            'inputs' => [
                'sip' => 25000.0,
                'years' => 15,
                'rate' => 12.0,
                'stepup' => 10.0,
                'lumpsum' => 0.0,
                'enable_swp' => false,
                'swp_withdrawal' => 0,
                'swp_years' => 0,
                'swp_stepup' => 0,
                'swp_rate' => 8
            ]
        ];

        $jsonArg = escapeshellarg(json_encode($jsPayload));
        $output = shell_exec("node " . escapeshellarg($runnerPath) . " {$jsonArg}");
        $jsRes = json_decode((string) $output, true);
        $jsDelayCost = (float) ($jsRes['result'] ?? -1);

        $this->assertEqualsWithDelta($phpDelayCost, $jsDelayCost, 1.0, "Delay cost mismatch between PHP and JS");
    }

    public static function inputProvider(): array
    {
        return [
            'default_case' => [
                []
            ],
            'standard_sip_no_stepup' => [
                [
                    'sip' => 5000,
                    'years' => 15,
                    'rate' => 12,
                    'stepup' => 0
                ]
            ],
            'sip_with_lumpsum' => [
                [
                    'sip' => 10000,
                    'years' => 10,
                    'rate' => 15,
                    'stepup' => 10,
                    'lumpsum' => 100000
                ]
            ],
            'sip_and_swp_default' => [
                [
                    'sip' => 10000,
                    'years' => 20,
                    'rate' => 12,
                    'stepup' => 10,
                    'enable_swp' => 1,
                    'swp_withdrawal' => 50000,
                    'swp_years' => 15,
                    'swp_stepup' => 6,
                    'swp_rate' => 8
                ]
            ],
            'lower_bounds' => [
                [
                    'sip' => 100, // Clamped to 500
                    'years' => 0, // Clamped to 1
                    'rate' => -5, // Clamped to 1
                    'stepup' => -5,
                    'lumpsum' => -100
                ]
            ],
            'upper_bounds' => [
                [
                    'sip' => 2000000, // Clamped to 1,000,000
                    'years' => 100, // Clamped to 50
                    'rate' => 50, // Clamped to 30
                    'stepup' => 70, // Clamped to 50
                    'lumpsum' => 50000000, // Clamped to 10,000,000
                    'enable_swp' => 1,
                    'swp_withdrawal' => 2000000, // Clamped to 1,000,000
                    'swp_years' => 80, // Clamped to 50
                    'swp_stepup' => 30, // Clamped to 20
                    'swp_rate' => 40 // Clamped to 30
                ]
            ],
            'pure_lumpsum' => [
                [
                    'sip' => 0,
                    'years' => 10,
                    'rate' => 14,
                    'stepup' => 0,
                    'lumpsum' => 500000
                ]
            ],
            'early_depletion_swp' => [
                [
                    'sip' => 0,
                    'years' => 1,
                    'rate' => 5,
                    'lumpsum' => 500000,
                    'enable_swp' => 1,
                    'swp_withdrawal' => 80000,
                    'swp_years' => 10,
                    'swp_stepup' => 5,
                    'swp_rate' => 6
                ]
            ],
            'zero_rate_linear' => [
                [
                    'sip' => 10000,
                    'years' => 5,
                    'rate' => 0,
                    'stepup' => 0,
                    'lumpsum' => 0
                ]
            ],
            'standalone_swp_regime' => [
                [
                    'sip' => 0,
                    'years' => 1,
                    'rate' => 8,
                    'lumpsum' => 5000000,
                    'enable_swp' => 1,
                    'swp_withdrawal' => 40000,
                    'swp_years' => 15,
                    'swp_stepup' => 6,
                    'swp_rate' => 8.5
                ]
            ],
            'micro_sip_regime' => [
                [
                    'sip' => 500,
                    'years' => 1,
                    'rate' => 1,
                    'stepup' => 0,
                    'lumpsum' => 0
                ]
            ],
            'ultra_high_wealth_regime' => [
                [
                    'sip' => 1000000,
                    'years' => 40,
                    'rate' => 25,
                    'stepup' => 20,
                    'lumpsum' => 10000000
                ]
            ],
            'guide_scenario_b' => [
                [
                    'sip' => 20000,
                    'years' => 15,
                    'rate' => 12,
                    'stepup' => 10,
                    'lumpsum' => 0
                ]
            ],
            'guide_stepup_5pct' => [
                [
                    'sip' => 10000,
                    'years' => 20,
                    'rate' => 12,
                    'stepup' => 5,
                    'lumpsum' => 0
                ]
            ],
            'guide_swp_plan_2' => [
                [
                    'sip' => 0,
                    'years' => 0,
                    'rate' => 8,
                    'lumpsum' => 10000000,
                    'enable_swp' => 1,
                    'swp_withdrawal' => 45000,
                    'swp_years' => 25,
                    'swp_stepup' => 5,
                    'swp_rate' => 8
                ]
            ]
        ];
    }
}
