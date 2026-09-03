<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for Indian Bank Fixed Deposits (FD).
 *
 * Implements Reserve Bank of India (RBI) compounding & payout standards:
 * - Standard quarterly compounding for cumulative deposits: A = P(1 + r/400)^(4t)
 * - Senior Citizen interest rate bonus (+0.50% p.a.)
 * - Payout frequency options: Cumulative (reinvestment), Monthly, Quarterly, and Annual
 * - Section 194A TDS estimation (₹40,000 general / ₹50,000 senior threshold at 10% TDS)
 * - Post-tax net yield estimation across income tax slabs (10%, 20%, 30% + cess)
 */
final class FdEngine
{
    public const DEFAULT_ANNUAL_INTEREST_RATE = 7.0;
    public const SENIOR_CITIZEN_RATE_BONUS = 0.50;
    public const TDS_THRESHOLD_GENERAL = 40000.0;
    public const TDS_THRESHOLD_SENIOR = 50000.0;
    public const TDS_RATE_STANDARD = 0.10; // 10% standard TDS under Section 194A

    /**
     * Calculate complete Fixed Deposit maturity proceeds and amortization breakdown.
     *
     * @param float $principal Initial deposit amount in INR
     * @param float $annualRate Annual nominal interest rate in percent (e.g. 7.0)
     * @param float $durationYears Duration in years (e.g. 1.0, 3.0, 5.0)
     * @param bool $isSeniorCitizen True if senior citizen rate bump (+0.50%) applies
     * @param string $payoutFrequency 'cumulative', 'monthly', 'quarterly', or 'annual'
     * @return array{
     *     principal: float,
     *     effective_rate: float,
     *     duration_years: float,
     *     maturity_amount: float,
     *     total_interest: float,
     *     periodic_payout: float,
     *     payout_frequency: string,
     *     is_senior_citizen: bool,
     *     estimated_annual_tds: float,
     *     post_tax_yield_30_percent: float,
     *     yearly_schedule: list<array{
     *         year: int,
     *         opening_balance: float,
     *         interest_earned: float,
     *         payout_withdrawn: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public static function calculate(
        float $principal,
        float $annualRate = self::DEFAULT_ANNUAL_INTEREST_RATE,
        float $durationYears = 1.0,
        bool $isSeniorCitizen = false,
        string $payoutFrequency = 'cumulative'
    ): array {
        $p = max(0.0, $principal);
        $baseRate = max(0.0, min(25.0, $annualRate));
        $effectiveRate = $baseRate + ($isSeniorCitizen ? self::SENIOR_CITIZEN_RATE_BONUS : 0.0);
        $t = max(0.25, min(30.0, $durationYears));

        $quarterlyRate = ($effectiveRate / 100.0) / 4.0;
        $totalQuarters = (int) round($t * 4);

        if ($payoutFrequency === 'cumulative') {
            // Quarterly compounding reinvestment formula: A = P * (1 + r/400)^(4t)
            $maturityAmount = $p * pow(1.0 + $quarterlyRate, $totalQuarters);
            $totalInterest = $maturityAmount - $p;
            $periodicPayout = 0.0;
        } elseif ($payoutFrequency === 'monthly') {
            // Monthly discounted interest payout under RBI rules: P * ((1 + r/400)^(1/3) - 1)
            $monthlyDiscountedRate = pow(1.0 + $quarterlyRate, 1.0 / 3.0) - 1.0;
            $periodicPayout = $p * $monthlyDiscountedRate;
            $totalInterest = $periodicPayout * ($t * 12.0);
            $maturityAmount = $p; // Principal returned on maturity
        } elseif ($payoutFrequency === 'quarterly') {
            // Quarterly simple interest payout: P * r / 400
            $periodicPayout = $p * $quarterlyRate;
            $totalInterest = $periodicPayout * $totalQuarters;
            $maturityAmount = $p;
        } else { // 'annual'
            // Annual interest payout: P * r / 100
            $periodicPayout = $p * ($effectiveRate / 100.0);
            $totalInterest = $periodicPayout * $t;
            $maturityAmount = $p;
        }

        // Section 194A TDS threshold test
        $annualInterestApprox = $t > 0 ? ($totalInterest / $t) : 0.0;
        $tdsThreshold = $isSeniorCitizen ? self::TDS_THRESHOLD_SENIOR : self::TDS_THRESHOLD_GENERAL;
        $estimatedAnnualTds = ($annualInterestApprox > $tdsThreshold)
            ? round($annualInterestApprox * self::TDS_RATE_STANDARD, 2)
            : 0.0;

        // Post-tax net yield at 30% slab + 4% cess = 31.2% tax rate
        $postTaxYield30 = round($effectiveRate * (1.0 - 0.312), 2);

        // Generate Year-by-Year compounding schedule
        $schedule = [];
        $runningBalance = $p;
        $fullYears = (int) ceil($t);

        for ($yr = 1; $yr <= $fullYears; $yr++) {
            $yrOpening = $runningBalance;
            $fraction = ($yr <= (int) floor($t)) ? 1.0 : ($t - floor($t));
            if ($fraction <= 0.0) {
                $fraction = 1.0;
            }

            if ($payoutFrequency === 'cumulative') {
                $quartersInYear = (int) round($fraction * 4);
                $yearEndBalance = $yrOpening * pow(1.0 + $quarterlyRate, $quartersInYear);
                $yrInterest = $yearEndBalance - $yrOpening;
                $yrPayout = 0.0;
                $runningBalance = $yearEndBalance;
            } else {
                $yrInterest = $p * ($effectiveRate / 100.0) * $fraction;
                $yrPayout = $yrInterest; // Fully withdrawn
                $runningBalance = $p;
            }

            $schedule[] = [
                'year' => $yr,
                'opening_balance' => round($yrOpening, 2),
                'interest_earned' => round($yrInterest, 2),
                'payout_withdrawn' => round($yrPayout, 2),
                'closing_balance' => round($runningBalance, 2),
            ];
        }

        return [
            'principal' => round($p, 2),
            'effective_rate' => round($effectiveRate, 2),
            'duration_years' => round($t, 2),
            'maturity_amount' => round($maturityAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'periodic_payout' => round($periodicPayout, 2),
            'payout_frequency' => $payoutFrequency,
            'is_senior_citizen' => $isSeniorCitizen,
            'estimated_annual_tds' => $estimatedAnnualTds,
            'post_tax_yield_30_percent' => $postTaxYield30,
            'yearly_schedule' => $schedule,
        ];
    }
}
