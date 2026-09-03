<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * EmiEngine
 * High-precision Equated Monthly Installment (EMI) and loan amortization engine.
 * Computes monthly EMI, total interest, principal payoff, and annual amortization schedule.
 */
final class EmiEngine
{
    /**
     * Compute loan EMI and complete amortization schedule.
     *
     * @param float $principal Principal loan amount (> 0)
     * @param float $annualRate Annual interest rate in percent (>= 0)
     * @param int $tenureYears Loan duration in years (> 0)
     * @return array{
     *     principal: float,
     *     annual_rate: float,
     *     tenure_years: int,
     *     monthly_emi: float,
     *     total_amount_payable: float,
     *     total_interest: float,
     *     interest_ratio_percentage: float,
     *     schedule: list<array{
     *         year: int,
     *         opening_balance: float,
     *         principal_paid: float,
     *         interest_paid: float,
     *         total_paid: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public function calculate(float $principal, float $annualRate, int $tenureYears): array
    {
        if ($principal <= 0.0) {
            throw new \InvalidArgumentException('Principal amount must be strictly greater than 0.');
        }

        if ($tenureYears <= 0) {
            throw new \InvalidArgumentException('Loan tenure must be at least 1 year.');
        }

        $p = $principal;
        $rAnnual = max(0.0, $annualRate);
        $totalMonths = $tenureYears * 12;

        if ($rAnnual === 0.0) {
            $monthlyEmi = $p / $totalMonths;
            $totalPayable = $p;
            $totalInterest = 0.0;
            $interestRatio = 0.0;
        } else {
            $rMonthly = ($rAnnual / 100.0) / 12.0;
            $factor = pow(1.0 + $rMonthly, $totalMonths);
            $monthlyEmi = ($p * $rMonthly * $factor) / ($factor - 1.0);
            $totalPayable = $monthlyEmi * $totalMonths;
            $totalInterest = $totalPayable - $p;
            $interestRatio = ($totalInterest / $p) * 100.0;
        }

        $schedule = [];
        $balance = $p;
        $rMonthly = ($rAnnual / 100.0) / 12.0;

        for ($year = 1; $year <= $tenureYears; $year++) {
            $yearOpening = $balance;
            $yearPrincipal = 0.0;
            $yearInterest = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                $monthInterest = $rAnnual > 0.0 ? ($balance * $rMonthly) : 0.0;
                $monthPrincipal = $monthlyEmi - $monthInterest;

                if ($monthPrincipal > $balance) {
                    $monthPrincipal = $balance;
                    $monthInterest = max(0.0, $monthlyEmi - $monthPrincipal);
                }

                $balance = max(0.0, $balance - $monthPrincipal);
                $yearPrincipal += $monthPrincipal;
                $yearInterest += $monthInterest;
            }

            $schedule[] = [
                'year' => $year,
                'opening_balance' => round($yearOpening, 2),
                'principal_paid' => round($yearPrincipal, 2),
                'interest_paid' => round($yearInterest, 2),
                'total_paid' => round($yearPrincipal + $yearInterest, 2),
                'closing_balance' => round($balance, 2)
            ];
        }

        return [
            'principal' => round($p, 2),
            'annual_rate' => round($rAnnual, 2),
            'tenure_years' => $tenureYears,
            'monthly_emi' => round($monthlyEmi, 2),
            'total_amount_payable' => round($totalPayable, 2),
            'total_interest' => round($totalInterest, 2),
            'interest_ratio_percentage' => round($interestRatio, 2),
            'schedule' => $schedule
        ];
    }
}
