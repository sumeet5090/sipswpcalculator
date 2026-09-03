<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for the Public Provident Fund (PPF).
 *
 * Implements official Government of India PPF scheme rules:
 * - Compounded annually, credited at the close of each financial year (March 31).
 * - Calculated monthly on the minimum balance between the 5th and the last day of the calendar month.
 * - Standard 15-year statutory tenure with 5-year block extensions.
 * - Annual contribution limits: Min ₹500, Max ₹1,50,000.
 */
final class PpfEngine
{
    public const DEFAULT_ANNUAL_INTEREST_RATE = 7.1;
    public const MIN_ANNUAL_DEPOSIT = 500.0;
    public const MAX_ANNUAL_DEPOSIT = 150000.0;
    public const STATUTORY_TENURE_YEARS = 15;

    /**
     * Calculate complete PPF projection and yearly compounding ledger.
     *
     * @param float $yearlyDeposit Annual amount deposited (₹500 to ₹1,50,000)
     * @param float $interestRate Annual interest rate in percent (e.g. 7.1)
     * @param int $tenureYears Investment horizon in years (15, 20, 25, 30, etc.)
     * @param string $depositTiming 'beginning' (before April 5th, full 12-month interest) or 'monthly' (₹deposit/12 each month before 5th)
     * @return array{
     *     total_invested: float,
     *     total_interest: float,
     *     maturity_amount: float,
     *     interest_rate: float,
     *     tenure_years: int,
     *     schedule: list<array{
     *         year: int,
     *         opening_balance: float,
     *         annual_deposit: float,
     *         interest_earned: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public static function calculate(
        float $yearlyDeposit,
        float $interestRate = self::DEFAULT_ANNUAL_INTEREST_RATE,
        int $tenureYears = self::STATUTORY_TENURE_YEARS,
        string $depositTiming = 'beginning'
    ): array {
        // Clamp inputs per statutory bounds
        $deposit = max(0.0, min(self::MAX_ANNUAL_DEPOSIT, $yearlyDeposit));
        $rate = max(0.0, min(20.0, $interestRate));
        $years = max(1, min(50, $tenureYears));
        $monthlyRate = ($rate / 100.0) / 12.0;

        $openingBalance = 0.0;
        $totalInvested = 0.0;
        $totalInterest = 0.0;
        $schedule = [];

        for ($year = 1; $year <= $years; $year++) {
            $yearOpening = $openingBalance;
            $yearDeposit = $deposit;
            $totalInvested += $yearDeposit;

            if ($depositTiming === 'monthly') {
                // Monthly deposit before 5th of each month (12 equal installments)
                $monthlyDeposit = $yearDeposit / 12.0;
                $runningBalance = $yearOpening;
                $annualInterest = 0.0;

                for ($month = 1; $month <= 12; $month++) {
                    $runningBalance += $monthlyDeposit;
                    $monthlyInterest = $runningBalance * $monthlyRate;
                    $annualInterest += $monthlyInterest;
                }
                // Under PPF rules, interest is credited at end of financial year
                $closingBalance = $yearOpening + $yearDeposit + $annualInterest;
            } else {
                // Lump sum deposited on or before April 5th: earns full 12-month interest
                $balanceForInterest = $yearOpening + $yearDeposit;
                $annualInterest = $balanceForInterest * ($rate / 100.0);
                $closingBalance = $balanceForInterest + $annualInterest;
            }

            // Banker's rounding for ledger precision
            $roundedInterest = round($annualInterest, 2);
            $roundedClosing = round($closingBalance, 2);

            $schedule[] = [
                'year' => $year,
                'opening_balance' => round($yearOpening, 2),
                'annual_deposit' => round($yearDeposit, 2),
                'interest_earned' => $roundedInterest,
                'closing_balance' => $roundedClosing,
            ];

            $totalInterest += $roundedInterest;
            $openingBalance = $roundedClosing;
        }

        return [
            'total_invested' => round($totalInvested, 2),
            'total_interest' => round($totalInterest, 2),
            'maturity_amount' => round($openingBalance, 2),
            'interest_rate' => $rate,
            'tenure_years' => $years,
            'schedule' => $schedule,
        ];
    }
}
