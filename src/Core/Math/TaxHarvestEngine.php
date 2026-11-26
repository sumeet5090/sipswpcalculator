<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless calculation engine for Section 112A LTCG Tax & Annual Tax Harvesting.
 *
 * Models:
 * - Finance (No. 2) Act 2024: 12.5% LTCG above ₹1,25,000/FY (+ optional 4% Health & Education Cess = 13.0%).
 * - Annual Tax Harvesting: Systematically realizing & reinvesting up to ₹1,25,000 gain/FY to reset cost basis.
 * - Grandfathering Clause (Jan 31, 2018 FMV): Cost = max(Actual Cost, min(FMV_2018, Current Value)).
 * - Immediate Exit Split (March 31 + April 1): Capturing 2 x ₹1,25,000 = ₹2,50,000 tax-free across FY boundary.
 */
final class TaxHarvestEngine
{
    public const DEFAULT_PORTFOLIO_VALUE = 2500000.0;
    public const DEFAULT_INVESTED_COST = 1500000.0;
    public const DEFAULT_EXPECTED_RETURN = 12.0;
    public const DEFAULT_HOLDING_YEARS = 10;
    public const DEFAULT_GRANDFATHER_FMV = 0.0;
    public const DEFAULT_INCLUDE_CESS = true;

    public const MIN_PORTFOLIO_VALUE = 100000.0;
    public const MAX_PORTFOLIO_VALUE = 100000000.0;
    public const MIN_INVESTED_COST = 50000.0;
    public const MAX_INVESTED_COST = 100000000.0;
    public const MIN_EXPECTED_RETURN = 4.0;
    public const MAX_EXPECTED_RETURN = 25.0;
    public const MIN_HOLDING_YEARS = 1;
    public const MAX_HOLDING_YEARS = 30;
    public const MIN_GRANDFATHER_FMV = 0.0;
    public const MAX_GRANDFATHER_FMV = 100000000.0;

    public const ANNUAL_EXEMPTION_LIMIT = 125000.0;
    public const BASE_LTCG_RATE = 0.125;
    public const CESS_MULTIPLIER = 1.04;

    /**
     * @return array{
     *     portfolio_value: float,
     *     invested_cost: float,
     *     effective_cost_basis: float,
     *     expected_return: float,
     *     holding_years: int,
     *     grandfather_fmv: float,
     *     include_cess: bool,
     *     effective_tax_rate_pct: float,
     *     buy_hold_tax: float,
     *     harvested_tax: float,
     *     cumulative_tax_saved: float,
     *     total_gains_harvested: float,
     *     final_gross_corpus: float,
     *     net_post_tax_wealth: float,
     *     march_april_savings: float,
     *     schedule: list<array{
     *         year: int,
     *         gross_value: float,
     *         buy_hold_tax: float,
     *         harvested_this_year: float,
     *         harvested_exit_tax: float,
     *         tax_saved: float,
     *         net_harvested_wealth: float
     *     }>
     * }
     */
    public static function calculate(
        float $portfolioValue,
        float $investedCost,
        float $expectedReturn = self::DEFAULT_EXPECTED_RETURN,
        int $holdingYears = self::DEFAULT_HOLDING_YEARS,
        float $grandfatherFmv = self::DEFAULT_GRANDFATHER_FMV,
        bool $includeCess = self::DEFAULT_INCLUDE_CESS
    ): array {
        $value0 = max(self::MIN_PORTFOLIO_VALUE, min(self::MAX_PORTFOLIO_VALUE, $portfolioValue));
        $cost = max(self::MIN_INVESTED_COST, min(self::MAX_INVESTED_COST, $investedCost));
        $returnPct = max(self::MIN_EXPECTED_RETURN, min(self::MAX_EXPECTED_RETURN, $expectedReturn));
        $years = max(self::MIN_HOLDING_YEARS, min(self::MAX_HOLDING_YEARS, $holdingYears));
        $fmv = max(self::MIN_GRANDFATHER_FMV, min(self::MAX_GRANDFATHER_FMV, $grandfatherFmv));

        $effectiveCost0 = $fmv > 0.0
            ? max($cost, min($fmv, $value0))
            : min($cost, $value0);

        $taxRate = $includeCess
            ? self::BASE_LTCG_RATE * self::CESS_MULTIPLIER
            : self::BASE_LTCG_RATE;
        $r = $returnPct / 100.0;

        // Immediate March 31 + April 1 Split check on current unrealized gain
        $currentGain = max(0.0, $value0 - $effectiveCost0);
        $singleFyTax = round(max(0.0, $currentGain - self::ANNUAL_EXEMPTION_LIMIT) * $taxRate);
        $splitFyTax = round(max(0.0, $currentGain - (2.0 * self::ANNUAL_EXEMPTION_LIMIT)) * $taxRate);
        $marchAprilSavings = max(0.0, $singleFyTax - $splitFyTax);

        $harvestCost = $effectiveCost0;
        $totalHarvested = 0.0;
        $schedule = [];

        $finalGross = $value0;
        $finalHoldTax = 0.0;
        $finalHarvestTax = 0.0;

        for ($y = 1; $y <= $years; $y++) {
            $grossValue = $value0 * pow(1.0 + $r, $y);
            $holdGain = max(0.0, $grossValue - $effectiveCost0);
            $holdTax = round(max(0.0, $holdGain - self::ANNUAL_EXEMPTION_LIMIT) * $taxRate);

            $unrealizedForHarvest = max(0.0, $grossValue - $harvestCost);
            $harvestedThisYear = min($unrealizedForHarvest, self::ANNUAL_EXEMPTION_LIMIT);
            $harvestCost += $harvestedThisYear;
            $totalHarvested += $harvestedThisYear;

            $remainingTaxable = max(0.0, $grossValue - $harvestCost);
            $harvestExitTax = round($remainingTaxable * $taxRate);
            $taxSaved = max(0.0, $holdTax - $harvestExitTax);
            $netHarvestedWealth = $grossValue - $harvestExitTax;

            $schedule[] = [
                'year' => $y,
                'gross_value' => (float) round($grossValue),
                'buy_hold_tax' => (float) $holdTax,
                'harvested_this_year' => (float) round($harvestedThisYear),
                'harvested_exit_tax' => (float) $harvestExitTax,
                'tax_saved' => (float) $taxSaved,
                'net_harvested_wealth' => (float) round($netHarvestedWealth),
            ];

            if ($y === $years) {
                $finalGross = $grossValue;
                $finalHoldTax = $holdTax;
                $finalHarvestTax = $harvestExitTax;
            }
        }

        $cumulativeTaxSaved = max(0.0, $finalHoldTax - $finalHarvestTax);

        return [
            'portfolio_value' => (float) $value0,
            'invested_cost' => (float) $cost,
            'effective_cost_basis' => (float) round($effectiveCost0),
            'expected_return' => (float) $returnPct,
            'holding_years' => $years,
            'grandfather_fmv' => (float) $fmv,
            'include_cess' => $includeCess,
            'effective_tax_rate_pct' => round($taxRate * 100.0, 2),
            'buy_hold_tax' => (float) $finalHoldTax,
            'harvested_tax' => (float) $finalHarvestTax,
            'cumulative_tax_saved' => (float) $cumulativeTaxSaved,
            'total_gains_harvested' => (float) round($totalHarvested),
            'final_gross_corpus' => (float) round($finalGross),
            'net_post_tax_wealth' => (float) round($finalGross - $finalHarvestTax),
            'march_april_savings' => (float) $marchAprilSavings,
            'schedule' => $schedule,
        ];
    }
}
