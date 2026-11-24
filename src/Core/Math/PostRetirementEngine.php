<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for Post-Retirement Asset Allocation & Multi-Stream Drawdown.
 *
 * Synthesizes:
 * - Day-1 Capital Inflows: Existing personal savings/EPF/PPF + Gratuity lump sum (Sec 10(10)) + NPS 60% lump sum (Sec 10(12A))
 * - Guaranteed Monthly Inflows: NPS life annuity pension + employer/EPS pension
 * - Variable Living Drawdown (SWP): Deficit between living expense and guaranteed pension
 * - Month-by-month decumulation with annual inflation escalation
 * - Dynamic 3-Bucket Asset Allocation (Cash Buffer 0-3Y, Income 4-10Y, Long-Term Growth 10Y+)
 */
final class PostRetirementEngine
{
    public const DEFAULT_EXISTING_CORPUS = 5000000.0;
    public const DEFAULT_GRATUITY_LUMP_SUM = 1500000.0;
    public const DEFAULT_NPS_LUMP_SUM = 4000000.0;
    public const DEFAULT_GUARANTEED_PENSION = 25000.0;
    public const DEFAULT_MONTHLY_EXPENSE = 75000.0;
    public const DEFAULT_EXPECTED_RETURN = 8.5;
    public const DEFAULT_INFLATION_RATE = 6.0;
    public const DEFAULT_CURRENT_AGE = 60;
    public const DEFAULT_PLANNING_HORIZON_YEARS = 30;

    public const MIN_EXISTING_CORPUS = 0.0;
    public const MAX_EXISTING_CORPUS = 500000000.0;
    public const MIN_GRATUITY_LUMP_SUM = 0.0;
    public const MAX_GRATUITY_LUMP_SUM = 50000000.0;
    public const MIN_NPS_LUMP_SUM = 0.0;
    public const MAX_NPS_LUMP_SUM = 100000000.0;
    public const MIN_GUARANTEED_PENSION = 0.0;
    public const MAX_GUARANTEED_PENSION = 500000.0;
    public const MIN_MONTHLY_EXPENSE = 10000.0;
    public const MAX_MONTHLY_EXPENSE = 1000000.0;
    public const MIN_EXPECTED_RETURN = 4.0;
    public const MAX_EXPECTED_RETURN = 14.0;
    public const MIN_INFLATION_RATE = 3.0;
    public const MAX_INFLATION_RATE = 10.0;
    public const MIN_CURRENT_AGE = 40;
    public const MAX_CURRENT_AGE = 80;
    public const MIN_PLANNING_HORIZON_YEARS = 10;
    public const MAX_PLANNING_HORIZON_YEARS = 45;

    /**
     * Calculate multi-stream post-retirement drawdown, portfolio longevity, and 3-bucket allocation.
     *
     * @param float $existingCorpus Accumulated savings, mutual funds, EPF, PPF
     * @param float $gratuityLumpSum Gratuity payout received at retirement
     * @param float $npsLumpSum 60% tax-free NPS lump sum under Section 10(12A)
     * @param float $guaranteedPension Monthly guaranteed pension (NPS annuity + EPS)
     * @param float $monthlyExpense Target monthly living expenses in Year 1
     * @param float $expectedReturn Annualized expected portfolio return (4% to 14%)
     * @param float $inflationRate Annual inflation rate for living expenses (3% to 10%)
     * @param int $currentAge Superannuation age at retirement (40 to 80)
     * @param int $planningHorizonYears Duration to plan longevity (10 to 45 years)
     * @return array{
     *     total_initial_corpus: float,
     *     initial_monthly_swp: float,
     *     initial_swr_percent: float,
     *     is_perpetual: bool,
     *     depletion_month: ?int,
     *     depletion_year: ?float,
     *     depletion_age: ?int,
     *     final_balance: float,
     *     bucket_1_cash: float,
     *     bucket_2_income: float,
     *     bucket_3_growth: float,
     *     schedule: list<array{
     *         year: int,
     *         age: int,
     *         opening_balance: float,
     *         annual_living_expense: float,
     *         annual_pension_received: float,
     *         annual_swp_drawn: float,
     *         growth_earned: float,
     *         closing_balance: float,
     *         is_depleted: bool
     *     }>
     * }
     */
    public static function calculate(
        float $existingCorpus,
        float $gratuityLumpSum,
        float $npsLumpSum,
        float $guaranteedPension,
        float $monthlyExpense,
        float $expectedReturn,
        float $inflationRate = self::DEFAULT_INFLATION_RATE,
        int $currentAge = self::DEFAULT_CURRENT_AGE,
        int $planningHorizonYears = self::DEFAULT_PLANNING_HORIZON_YEARS
    ): array {
        $existing = max(self::MIN_EXISTING_CORPUS, min(self::MAX_EXISTING_CORPUS, $existingCorpus));
        $gratuity = max(self::MIN_GRATUITY_LUMP_SUM, min(self::MAX_GRATUITY_LUMP_SUM, $gratuityLumpSum));
        $nps = max(self::MIN_NPS_LUMP_SUM, min(self::MAX_NPS_LUMP_SUM, $npsLumpSum));
        $pension = max(self::MIN_GUARANTEED_PENSION, min(self::MAX_GUARANTEED_PENSION, $guaranteedPension));
        $expense = max(self::MIN_MONTHLY_EXPENSE, min(self::MAX_MONTHLY_EXPENSE, $monthlyExpense));
        $returnRate = max(self::MIN_EXPECTED_RETURN, min(self::MAX_EXPECTED_RETURN, $expectedReturn));
        $infRate = max(self::MIN_INFLATION_RATE, min(self::MAX_INFLATION_RATE, $inflationRate));
        $age = max(self::MIN_CURRENT_AGE, min(self::MAX_CURRENT_AGE, $currentAge));
        $horizonYears = max(self::MIN_PLANNING_HORIZON_YEARS, min(self::MAX_PLANNING_HORIZON_YEARS, $planningHorizonYears));

        $totalInitialCorpus = round($existing + $gratuity + $nps);
        $initialMonthlySwp = round(max(0.0, $expense - $pension));

        $initialSwrPercent = $totalInitialCorpus > 0.0
            ? round((($initialMonthlySwp * 12.0) / $totalInitialCorpus) * 100.0, 2)
            : 0.0;

        // 3-Bucket Asset Allocation
        $bucket1Cash = round(min($totalInitialCorpus, 3.0 * 12.0 * $initialMonthlySwp));
        $remainingAfterB1 = max(0.0, $totalInitialCorpus - $bucket1Cash);
        $bucket2Income = round(min($remainingAfterB1, max(0.0, $totalInitialCorpus * 0.40)));
        $bucket3Growth = max(0.0, $totalInitialCorpus - $bucket1Cash - $bucket2Income);

        $monthlyReturn = ($returnRate / 100.0) / 12.0;
        $inflationDecimal = $infRate / 100.0;

        $balance = (float) $totalInitialCorpus;
        $depletionMonth = null;
        $schedule = [];

        for ($y = 1; $y <= $horizonYears; $y++) {
            $openingBalance = $balance;
            $yIdx = $y - 1;

            $annualLivingExpense = round(($expense * pow(1.0 + $inflationDecimal, $yIdx)) * 12.0);
            $annualPensionReceived = round($pension * 12.0);
            $annualSwpDrawn = round(max(0.0, $annualLivingExpense - $annualPensionReceived));

            $monthlyExpenseThisYear = $expense * pow(1.0 + $inflationDecimal, $yIdx);
            $monthlySwpThisYear = max(0.0, $monthlyExpenseThisYear - $pension);

            $yearGrowth = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                $monthGlobal = ($y - 1) * 12 + $m;

                if ($balance <= 0.0) {
                    $balance = 0.0;
                    if ($depletionMonth === null) {
                        $depletionMonth = $monthGlobal;
                    }
                    continue;
                }

                $growth = $balance * $monthlyReturn;
                $yearGrowth += $growth;
                $balance = ($balance + $growth) - $monthlySwpThisYear;

                if ($balance <= 0.0) {
                    $balance = 0.0;
                    if ($depletionMonth === null) {
                        $depletionMonth = $monthGlobal;
                    }
                }
            }

            $schedule[] = [
                'year' => $y,
                'age' => $age + $y,
                'opening_balance' => (float) round($openingBalance),
                'annual_living_expense' => (float) $annualLivingExpense,
                'annual_pension_received' => (float) $annualPensionReceived,
                'annual_swp_drawn' => (float) $annualSwpDrawn,
                'growth_earned' => (float) round($yearGrowth),
                'closing_balance' => (float) round($balance),
                'is_depleted' => ($balance <= 0.0),
            ];
        }

        $isPerpetual = ($depletionMonth === null);
        $depletionYear = $depletionMonth !== null ? round($depletionMonth / 12.0, 1) : null;
        $depletionAge = $depletionMonth !== null ? ($age + (int) floor($depletionMonth / 12.0)) : null;

        return [
            'total_initial_corpus' => (float) $totalInitialCorpus,
            'initial_monthly_swp' => (float) $initialMonthlySwp,
            'initial_swr_percent' => (float) $initialSwrPercent,
            'is_perpetual' => $isPerpetual,
            'depletion_month' => $depletionMonth,
            'depletion_year' => $depletionYear,
            'depletion_age' => $depletionAge,
            'final_balance' => (float) round($balance),
            'bucket_1_cash' => (float) $bucket1Cash,
            'bucket_2_income' => (float) $bucket2Income,
            'bucket_3_growth' => (float) $bucket3Growth,
            'schedule' => $schedule,
        ];
    }
}
