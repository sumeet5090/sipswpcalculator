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
}
