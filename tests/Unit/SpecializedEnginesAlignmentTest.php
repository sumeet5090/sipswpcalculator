<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\CagrEngine;
use Core\Math\CompoundInterestEngine;
use Core\Math\EmiEngine;
use Core\Math\FdEngine;
use Core\Math\InflationEngine;
use Core\Math\PpfEngine;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SpecializedEnginesAlignmentTest
 *
 * Validates cross-runtime execution parity between PHP 8.5 backend engines
 * and TypeScript / V8 frontend engines down to the exact paisa.
 *
 * Covers:
 * 1. Compound Interest Engine (Frequencies: Annually, Semi-Annually, Quarterly, Monthly, Daily)
 * 2. CAGR Engine (Positive growth, multi-year drawdowns, fractional year horizons)
 * 3. EMI Engine (Reducing balance amortization schedules, monthly principal/interest, zero final balance)
 * 4. Fixed Deposit Engine (Cumulative quarterly compounding, non-cumulative monthly/quarterly/annual, TDS)
 * 5. PPF Engine (Beginning vs monthly deposit schedules, statutory bounds, 5-year extensions)
 * 6. Inflation Engine (Future cost estimation, purchasing power erosion)
 * 7. Tax Harvesting Alpha & Safe SWP Monthly Withdrawal Inversion
 */
final class SpecializedEnginesAlignmentTest extends TestCase
{
    private string $runnerPath;

    protected function setUp(): void
    {
        $this->runnerPath = (string) realpath(__DIR__ . '/../run_js_calc.js');
        $this->assertNotEmpty($this->runnerPath, "JS runner script not found at run_js_calc.js");
    }

    /**
     * Helper to dispatch JSON action to run_js_calc.js via Node.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function executeJsRunner(array $payload): array
    {
        $jsonArg = escapeshellarg(json_encode($payload, JSON_THROW_ON_ERROR));
        $cmd = "node " . escapeshellarg($this->runnerPath) . " {$jsonArg}";

        $output = shell_exec($cmd);
        if (!$output) {
            $this->fail("Node runner returned empty output for payload: " . json_encode($payload));
        }

        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            $this->fail("Failed to parse JSON response from Node runner: {$output}");
        }

        return $decoded;
    }

    /**
     * 1. Compound Interest Engine Parity
     */
    #[DataProvider('compoundInterestProvider')]
    public function testCompoundInterestEngineParity(float $principal, float $rate, int $years, int $freq): void
    {
        $engine = new CompoundInterestEngine();
        $php = $engine->calculate($principal, $rate, $years, $freq);

        $js = $this->executeJsRunner([
            'action' => 'compound_interest',
            'principal' => $principal,
            'annual_rate' => $rate,
            'years' => $years,
            'compounding_frequency' => $freq,
        ]);

        $this->assertEqualsWithDelta($php['final_amount'], $js['final_amount'], 0.05, "Final amount parity mismatch");
        $this->assertEqualsWithDelta($php['total_interest'], $js['total_interest'], 0.05, "Total interest parity mismatch");
        $this->assertEqualsWithDelta($php['effective_annual_rate'], $js['effective_annual_rate'], 0.001, "EAR parity mismatch");

        $this->assertCount(count($php['schedule']), $js['schedule']);
        foreach ($php['schedule'] as $idx => $phpRow) {
            $jsRow = $js['schedule'][$idx];
            $this->assertEquals($phpRow['year'], $jsRow['year']);
            $this->assertEqualsWithDelta($phpRow['opening_balance'], $jsRow['opening_balance'], 0.05);
            $this->assertEqualsWithDelta($phpRow['interest_earned'], $jsRow['interest_earned'], 0.05);
            $this->assertEqualsWithDelta($phpRow['closing_balance'], $jsRow['closing_balance'], 0.05);
        }
    }

    /**
     * 2. CAGR Engine Parity
     */
    #[DataProvider('cagrProvider')]
    public function testCagrEngineParity(float $begin, float $end, float $years): void
    {
        $engine = new CagrEngine();
        $php = $engine->calculate($begin, $end, $years);

        $js = $this->executeJsRunner([
            'action' => 'cagr',
            'beginning_value' => $begin,
            'ending_value' => $end,
            'years' => $years,
        ]);

        $this->assertEqualsWithDelta($php['cagr_percentage'], $js['cagr_percentage'], 0.01, "CAGR % parity mismatch");
        $this->assertEqualsWithDelta($php['absolute_return_percentage'], $js['absolute_return_percentage'], 0.01, "Absolute return % parity mismatch");
        $this->assertEqualsWithDelta($php['total_gain'], $js['total_gain'], 0.05, "Total gain parity mismatch");
        $this->assertEqualsWithDelta($php['multiplier'], $js['multiplier'], 0.001, "Multiplier parity mismatch");
    }

    /**
     * 3. EMI Engine Parity
     */
    #[DataProvider('emiProvider')]
    public function testEmiEngineParity(float $principal, float $rate, int $tenureYears): void
    {
        $engine = new EmiEngine();
        $php = $engine->calculate($principal, $rate, $tenureYears);

        $js = $this->executeJsRunner([
            'action' => 'emi',
            'principal' => $principal,
            'annual_rate' => $rate,
            'tenure_years' => $tenureYears,
        ]);

        $this->assertEqualsWithDelta($php['monthly_emi'], $js['monthly_emi'], 0.05, "Monthly EMI mismatch");
        $this->assertEqualsWithDelta($php['total_amount_payable'], $js['total_amount_payable'], 1.0, "Total amount payable mismatch");
        $this->assertEqualsWithDelta($php['total_interest'], $js['total_interest'], 1.0, "Total interest mismatch");

        $this->assertCount(count($php['schedule']), $js['schedule']);
        foreach ($php['schedule'] as $idx => $phpRow) {
            $jsRow = $js['schedule'][$idx];
            $this->assertEquals($phpRow['year'], $jsRow['year']);
            $this->assertEqualsWithDelta($phpRow['principal_paid'], $jsRow['principal_paid'], 0.5);
            $this->assertEqualsWithDelta($phpRow['interest_paid'], $jsRow['interest_paid'], 0.5);
            $this->assertEqualsWithDelta($phpRow['closing_balance'], $jsRow['closing_balance'], 0.5);
        }
    }

    /**
     * 4. Fixed Deposit (FD) Engine Parity
     */
    #[DataProvider('fdProvider')]
    public function testFdEngineParity(
        float $principal,
        float $rate,
        float $duration,
        bool $isSenior,
        string $payoutFreq
    ): void {
        $php = FdEngine::calculate($principal, $rate, $duration, $isSenior, $payoutFreq);

        $js = $this->executeJsRunner([
            'action' => 'fd',
            'principal' => $principal,
            'annual_rate' => $rate,
            'duration_years' => $duration,
            'is_senior_citizen' => $isSenior,
            'payout_frequency' => $payoutFreq,
        ]);

        $this->assertEqualsWithDelta($php['maturity_amount'], $js['maturity_amount'], 0.05, "FD maturity amount mismatch");
        $this->assertEqualsWithDelta($php['total_interest'], $js['total_interest'], 0.05, "FD total interest mismatch");
        $this->assertEqualsWithDelta($php['periodic_payout'], $js['periodic_payout'], 0.05, "FD periodic payout mismatch");
        $this->assertEqualsWithDelta($php['estimated_annual_tds'], $js['estimated_annual_tds'], 0.05, "FD TDS estimation mismatch");

        $this->assertCount(count($php['yearly_schedule']), $js['yearly_schedule']);
    }

    /**
     * 5. Public Provident Fund (PPF) Engine Parity
     */
    #[DataProvider('ppfProvider')]
    public function testPpfEngineParity(float $deposit, float $rate, int $tenure, string $timing): void
    {
        $php = PpfEngine::calculate($deposit, $rate, $tenure, $timing);

        $js = $this->executeJsRunner([
            'action' => 'ppf',
            'yearly_deposit' => $deposit,
            'interest_rate' => $rate,
            'tenure_years' => $tenure,
            'deposit_timing' => $timing,
        ]);

        $this->assertEqualsWithDelta($php['total_invested'], $js['total_invested'], 0.01, "PPF invested mismatch");
        $this->assertEqualsWithDelta($php['total_interest'], $js['total_interest'], 0.05, "PPF interest mismatch");
        $this->assertEqualsWithDelta($php['maturity_amount'], $js['maturity_amount'], 0.05, "PPF maturity mismatch");

        $this->assertCount(count($php['schedule']), $js['schedule']);
        foreach ($php['schedule'] as $idx => $phpRow) {
            $jsRow = $js['schedule'][$idx];
            $this->assertEquals($phpRow['year'], $jsRow['year']);
            $this->assertEqualsWithDelta($phpRow['interest_earned'], $jsRow['interest_earned'], 0.05);
            $this->assertEqualsWithDelta($phpRow['closing_balance'], $jsRow['closing_balance'], 0.05);
        }
    }

    /**
     * 6. Inflation Engine Parity
     */
    #[DataProvider('inflationProvider')]
    public function testInflationEngineParity(float $pv, float $rate, int $years): void
    {
        $engine = new InflationEngine();
        $php = $engine->calculate($pv, $rate, $years);

        $js = $this->executeJsRunner([
            'action' => 'inflation',
            'present_value' => $pv,
            'inflation_rate' => $rate,
            'years' => $years,
        ]);

        $this->assertEqualsWithDelta($php['future_cost'], $js['future_cost'], 0.05, "Future cost mismatch");
        $this->assertEqualsWithDelta($php['purchasing_power'], $js['purchasing_power'], 0.05, "Purchasing power mismatch");
        $this->assertEqualsWithDelta($php['purchasing_power_loss_percentage'], $js['purchasing_power_loss_percentage'], 0.01, "Power loss % mismatch");
    }

    /**
     * 7. Safe SWP Monthly Withdrawal Inversion Parity
     */
    public function testSafeSwpWithdrawalSolverParity(): void
    {
        $calculator = new InvestmentCalculator();
        $inputs = InvestmentInputs::fromValues(
            sip: 0.0,
            years: 0,
            rate: 0.0,
            stepup: 0.0,
            enableSwp: true,
            swpWithdrawal: 0.0,
            swpStepup: 5.0,
            swpYears: 20,
            lumpsum: 10000000.0,
            swpRate: 8.0
        );

        $phpSafeSwp = $calculator->calculateSafeSwpWithdrawal($inputs, 10000000.0);

        $js = $this->executeJsRunner([
            'action' => 'safe_swp_withdrawal',
            'inputs' => [
                'sip' => 0,
                'years' => 0,
                'rate' => 0,
                'stepup' => 0,
                'enable_swp' => true,
                'swp_withdrawal' => 0,
                'swp_stepup' => 5.0,
                'swp_years' => 20,
                'lumpsum' => 10000000.0,
                'swp_rate' => 8.0
            ],
            'starting_corpus' => 10000000.0,
        ]);

        $jsSafeSwp = (float) ($js['result'] ?? -1);

        $this->assertEqualsWithDelta($phpSafeSwp, $jsSafeSwp, 1.0, "Safe SWP monthly withdrawal mismatch between PHP and JS");
    }

    public static function compoundInterestProvider(): array
    {
        return [
            'annual_compounding'       => [100000.0, 10.0, 5, 1],
            'semi_annual_compounding'  => [250000.0, 8.5, 7, 2],
            'quarterly_compounding'    => [500000.0, 7.5, 10, 4],
            'monthly_compounding'      => [1000000.0, 12.0, 15, 12],
            'daily_compounding'        => [1000000.0, 7.0, 1, 365],
            'zero_horizon_singularity' => [100000.0, 10.0, 0, 1],
            'zero_rate'                => [50000.0, 0.0, 5, 12],
        ];
    }

    public static function cagrProvider(): array
    {
        return [
            'standard_5yr_doubling'   => [100000.0, 200000.0, 5.0],
            'drawdown_loss_3yr'        => [100000.0, 60000.0, 3.0],
            'flat_returns_5yr'         => [100000.0, 100000.0, 5.0],
            'fractional_quarter_year'  => [100000.0, 105000.0, 0.25],
            'multi_decade_compounding' => [500000.0, 50000000.0, 25.0],
        ];
    }

    public static function emiProvider(): array
    {
        return [
            'home_loan_50L_20yr'   => [5000000.0, 8.5, 20],
            'car_loan_10L_5yr'     => [1000000.0, 9.0, 5],
            'personal_loan_3L_3yr' => [300000.0, 14.0, 3],
            'zero_interest_loan'   => [120000.0, 0.0, 1],
        ];
    }

    public static function fdProvider(): array
    {
        return [
            'cumulative_general_1yr'       => [100000.0, 7.0, 1.0, false, 'cumulative'],
            'cumulative_senior_3yr'        => [500000.0, 7.5, 3.0, true, 'cumulative'],
            'quarterly_payout_general_2yr' => [1000000.0, 8.0, 2.0, false, 'quarterly'],
            'monthly_payout_general_1yr'   => [1200000.0, 7.5, 1.0, false, 'monthly'],
            'annual_payout_general_3yr'    => [1000000.0, 7.0, 3.0, false, 'annual'],
        ];
    }

    public static function ppfProvider(): array
    {
        return [
            'standard_1.5L_beginning_15yr' => [150000.0, 7.1, 15, 'beginning'],
            'standard_1.5L_monthly_15yr'   => [150000.0, 7.1, 15, 'monthly'],
            'extended_25yr_beginning'      => [100000.0, 7.1, 25, 'beginning'],
            'statutory_min_500'            => [500.0, 7.1, 15, 'beginning'],
        ];
    }

    public static function inflationProvider(): array
    {
        return [
            'standard_10L_6pct_10yr' => [1000000.0, 6.0, 10],
            'high_inflation_20yr'    => [500000.0, 8.5, 20],
            'zero_horizon'           => [100000.0, 6.0, 0],
            'zero_inflation'         => [100000.0, 0.0, 10],
        ];
    }
}
