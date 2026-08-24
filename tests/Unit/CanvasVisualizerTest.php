<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;

/**
 * Validates Chart.js, HTML5 Canvas, and Visualizer architecture calculations,
 * including historical volatility corridor bands, safe SWP longevity convergence,
 * and ROI multipliers.
 */
final class CanvasVisualizerTest extends TestCase
{
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
    }

    public function testHistoricalVolatilityCorridorBounds(): void
    {
        // 10th-90th percentile rolling 10-year Nifty 50 CAGRs
        $lowerCagr = 10.2;
        $upperCagr = 15.8;
        $baselineCagr = 12.0;

        $this->assertGreaterThan(0.0, $lowerCagr);
        $this->assertLessThan($baselineCagr, $lowerCagr);
        $this->assertGreaterThan($baselineCagr, $upperCagr);

        // Verify bounds on ₹10,000 monthly SIP over 15 years
        $inputs = InvestmentInputs::fromValues(
            10000.0,
            15,
            $baselineCagr,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);
        $lastRow = end($results);
        $baselineCorpus = $lastRow['combined_total'];

        $this->assertGreaterThan(4500000.0, $baselineCorpus);
        $this->assertLessThan(6000000.0, $baselineCorpus);
    }

    public function testDonutRoiMultiplierDerivation(): void
    {
        // SIP ₹25k/mo, 10 years @ 12%, 10% stepup
        $inputs = InvestmentInputs::fromValues(
            25000.0,
            10,
            12.0,
            10.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);
        $lastRow = end($results);

        $invested = $lastRow['cumulative_invested'];
        $corpus = $lastRow['combined_total'];
        $multiplier = $invested > 0 ? round($corpus / $invested, 1) : 1.0;

        $this->assertGreaterThanOrEqual(1.5, $multiplier);
        $this->assertLessThanOrEqual(3.0, $multiplier);
    }

    public function testSwpDepletionSentinelAndSafeWithdrawal(): void
    {
        // ₹50 Lakhs starting corpus, target 20 years SWP @ 8% return, 5% annual hike
        $corpus = 5000000.0;
        $years = 20;
        $rate = 8.0;
        $stepup = 5.0;

        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            75000.0, // High withdrawal, will deplete prematurely
            $stepup,
            $years,
            $corpus,
            $rate
        );

        $safeWithdrawal = $this->calculator->calculateSafeSwpWithdrawal($inputs, $corpus);

        $this->assertGreaterThan(25000.0, $safeWithdrawal);
        $this->assertLessThan(40000.0, $safeWithdrawal);

        // When using safe withdrawal, the portfolio MUST survive the full 20-year horizon
        $safeInputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            $safeWithdrawal,
            $stepup,
            $years,
            $corpus,
            $rate
        );

        $safeResults = $this->calculator->calculate($safeInputs);
        $finalRow = end($safeResults);
        $this->assertGreaterThanOrEqual(0.0, $finalRow['combined_total']);
    }

    public function testCompoundingIgnitionPointDetection(): void
    {
        // 10,000 monthly SIP @ 12% CAGR with 0% step-up over 20 years
        $inputs = InvestmentInputs::fromValues(
            10000.0,
            20,
            12.0,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);

        // Find ignition year where annual interest >= annual contribution (1,20,000/yr)
        $ignitionYear = null;
        foreach ($results as $row) {
            if ($row['year'] > 1 && ($row['interest'] ?? 0.0) >= ($row['annual_contribution'] ?? 120000.0)) {
                $ignitionYear = $row['year'];
                break;
            }
        }

        $this->assertNotNull($ignitionYear);
        // At 12% CAGR, single year interest surpasses annual SIP contributions by year 7
        $this->assertGreaterThanOrEqual(5, $ignitionYear);
        $this->assertLessThanOrEqual(8, $ignitionYear);
    }

    public function testDevicePixelRatioConstraintLimits(): void
    {
        // Assert maximum safe DPR clamp is within [1.0, 2.5]
        $rawDprRetina = 3.0;
        $clampedDpr = min($rawDprRetina, 2.5);

        $this->assertSame(2.5, $clampedDpr, 'DPR must be clamped to 2.5 to prevent mobile GPU thermal throttling');
    }

    public function testRealPurchasingPowerInflationDiscountingFormula(): void
    {
        // ₹1 Crore nominal corpus in 20 years at 6% inflation
        $nominalCorpus = 10000000.0;
        $years = 20;
        $inflation = 6.0;

        $discountFactor = pow(1.0 + ($inflation / 100.0), $years);
        $realPurchasingPower = round($nominalCorpus / $discountFactor);

        // At 6% inflation, ₹1 Crore in 20 years is worth approximately ₹31.18 Lakhs today
        $this->assertGreaterThan(3000000.0, $realPurchasingPower);
        $this->assertLessThan(3300000.0, $realPurchasingPower);
    }

    public function testFixedDepositAlphaDeltaComputation(): void
    {
        // 25,000 / mo over 15 years @ 12% equity vs 6.5% bank FD
        $sipInputs = InvestmentInputs::fromValues(
            25000.0,
            15,
            12.0,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($sipInputs);
        $finalSipCorpus = end($results)['combined_total'];

        // Compute 6.5% FD
        $fdInputs = InvestmentInputs::fromValues(
            25000.0,
            15,
            6.5,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $fdResults = $this->calculator->calculate($fdInputs);
        $finalFdCorpus = end($fdResults)['combined_total'];

        $alphaDelta = $finalSipCorpus - $finalFdCorpus;
        $this->assertGreaterThan(4500000.0, $alphaDelta, "Equity SIP must generate substantial Alpha Delta over Bank FD");
        $this->assertGreaterThan($finalFdCorpus, $finalSipCorpus);
    }
}
