<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * CompoundInterestEngine
 * Stateless, high-precision mathematical engine for Compound Interest simulations.
 * Implements A = P * (1 + r/n)^(n*t) with compounding schedules and Rule of 72 metrics.
 */
final class CompoundInterestEngine
{
    /**
     * Compute compound interest and return comprehensive financial projections.
     *
     * @param float $principal Initial principal amount (>= 0)
     * @param float $annualRate Annual nominal interest rate in percent (e.g. 12 for 12%)
     * @param int $years Investment tenure in years (>= 0)
     * @param int $compoundingFrequency Compounds per year (1 = Annually, 2 = Semi-Annually, 4 = Quarterly, 12 = Monthly)
     * @return array{
     *     principal: float,
     *     final_amount: float,
     *     total_interest: float,
     *     effective_annual_rate: float,
     *     rule_of_72_years: ?float,
     *     schedule: list<array{
     *         year: int,
     *         opening_balance: float,
     *         interest_earned: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public function calculate(
        float $principal,
        float $annualRate,
        int $years,
        int $compoundingFrequency = 1
    ): array {
        $p = max(0.0, $principal);
        $rPercent = max(0.0, $annualRate);
        $t = max(0, $years);
        $n = max(1, $compoundingFrequency);

        $r = $rPercent / 100.0;

        if ($p === 0.0 || $t === 0) {
            $ear = $r > 0.0 ? (pow(1.0 + ($r / $n), $n) - 1.0) * 100.0 : 0.0;
            return [
                'principal' => round($p, 2),
                'final_amount' => round($p, 2),
                'total_interest' => 0.0,
                'effective_annual_rate' => round($ear, 4),
                'rule_of_72_years' => $rPercent > 0 ? round(72.0 / $rPercent, 2) : null,
                'schedule' => []
            ];
        }

        // Effective Annual Rate (EAR) = (1 + r/n)^n - 1
        $effectiveRateDecimal = pow(1.0 + ($r / $n), $n) - 1.0;
        $effectiveAnnualRate = $effectiveRateDecimal * 100.0;
        $ruleOf72 = $rPercent > 0 ? 72.0 / $rPercent : null;

        $schedule = [];
        $currentBalance = $p;

        for ($year = 1; $year <= $t; $year++) {
            $openingBalance = $currentBalance;
            // Compound for 1 full year using nominal rate with n compounds
            $closingBalance = $openingBalance * pow(1.0 + ($r / $n), $n);
            $interestEarned = $closingBalance - $openingBalance;

            $schedule[] = [
                'year' => $year,
                'opening_balance' => round($openingBalance, 2),
                'interest_earned' => round($interestEarned, 2),
                'closing_balance' => round($closingBalance, 2)
            ];

            $currentBalance = $closingBalance;
        }

        $finalAmount = $currentBalance;
        $totalInterest = max(0.0, $finalAmount - $p);

        return [
            'principal' => round($p, 2),
            'final_amount' => round($finalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'effective_annual_rate' => round($effectiveAnnualRate, 4),
            'rule_of_72_years' => $ruleOf72 !== null ? round($ruleOf72, 2) : null,
            'schedule' => $schedule
        ];
    }
}
