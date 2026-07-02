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
        $jsPath = __DIR__ . '/../../assets/js/calculators/MathEngine.js';
        $this->assertFileExists($jsPath, "MathEngine.js file not found at $jsPath");

        $jsCode = file_get_contents($jsPath);
        $jsCode = str_replace('export class MathEngine', 'class MathEngine', $jsCode);

        // Normalize parameter naming expected by MathEngine.js
        $jsInputs = [
            'sip' => $inputs['sip'] ?? 10000.0,
            'years' => $inputs['years'] ?? 20,
            'rate' => $inputs['rate'] ?? 12.0,
            'stepup' => $inputs['stepup'] ?? 10.0,
            'lumpsum' => $inputs['lumpsum'] ?? 0.0,
            'enable_swp' => $inputs['enable_swp'] ?? false,
            'swp_withdrawal' => $inputs['swp_withdrawal'] ?? 0.0,
            'swp_years' => $inputs['swp_years'] ?? 0,
            'swp_stepup' => $inputs['swp_stepup'] ?? 0.0,
            'swp_rate' => $inputs['swp_rate'] ?? 8.0,
        ];

        $script = $jsCode . "\nconsole.log(JSON.stringify(MathEngine.calculateCorpus(" . json_encode($jsInputs) . ")));";

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open("node", $descriptorspec, $pipes);
        if (!is_resource($process)) {
            $this->fail("Failed to execute Node.js process");
        }

        fwrite($pipes[0], $script);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);

        if ($status !== 0) {
            $this->fail("Node.js process failed with status $status. Error: $error");
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
        $inputs = InvestmentInputs::fromRequest($inputsData);
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
                    'rate' => -5, // Clamped to 0.1
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
            ]
        ];
    }
}
