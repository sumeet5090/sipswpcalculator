<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;

final class LongevityGuardianTest extends TestCase
{
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
    }

    public function testCalculateSafeSwpWithdrawalSustainsTargetHorizon(): void
    {
        // 1 Crore corpus, 20 years SWP, 8% return, 5% annual step-up
        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            100000.0, // Too high, will deplete early
            5.0,
            20,
            10000000.0,
            8.0
        );

        $safeWithdrawal = $this->calculator->calculateSafeSwpWithdrawal($inputs, 10000000.0);
        $this->assertGreaterThan(50000.0, $safeWithdrawal);
        $this->assertLessThan(65000.0, $safeWithdrawal);

        // Run simulation with safe withdrawal to confirm zero premature depletion
        $safeInputs = $inputs->withSwpWithdrawal($safeWithdrawal);
        $results = $this->calculator->calculate($safeInputs);

        $this->assertCount(20, $results);
        $lastRow = end($results);
        $this->assertGreaterThanOrEqual(0.0, $lastRow['combined_total'], "Safe SWP must leave non-negative terminal corpus");

        // Verify none of the intermediate years depleted to 0
        $rowCount = count($results);
        for ($i = 0; $i < $rowCount - 1; $i++) {
            $this->assertGreaterThan(0.0, $results[$i]['combined_total'], "Year {$results[$i]['year']} must not deplete prematurely");
        }
        $this->assertGreaterThanOrEqual(0.0, $results[$rowCount - 1]['combined_total'], "Final year must sustain >= 0");
    }

    public function testCalculateSafeSwpWithdrawalZeroInputsReturnZero(): void
    {
        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            50000.0,
            0.0,
            0,
            0.0,
            8.0
        );

        $this->assertEquals(0.0, $this->calculator->calculateSafeSwpWithdrawal($inputs, 0.0));
    }
}
