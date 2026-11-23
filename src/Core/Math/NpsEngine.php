<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for National Pension System (NPS) Tier-1.
 *
 * Implements official PFRDA regulations & Indian tax rules:
 * - Accumulation Phase: Monthly compounding annuity due at r_m = r / 12
 * - Superannuation Phase (Age 60+):
 *   - Mandatory Annuity: Minimum 40% (alpha >= 0.40) to purchase life annuity from ASP
 *   - Tax-Free Lump Sum: Up to 60% exempt under Section 10(12A) of the Income Tax Act
 *   - Small Corpus Exemption: If total corpus <= ₹5,00,000, 100% lump sum exit is permitted
 *   - Monthly Pension: Annuity corpus * (annuity_rate / 12)
 */
final class NpsEngine
{
    public const DEFAULT_MONTHLY_CONTRIBUTION = 5000.0;
    public const DEFAULT_CURRENT_AGE = 30;
    public const DEFAULT_RETIREMENT_AGE = 60;
    public const DEFAULT_EXPECTED_RETURN = 10.0;
    public const DEFAULT_ANNUITY_PERCENT = 40.0;
    public const DEFAULT_ANNUITY_RATE = 6.0;

    public const MIN_MONTHLY_CONTRIBUTION = 500.0;
    public const MAX_MONTHLY_CONTRIBUTION = 150000.0;
    public const MIN_CURRENT_AGE = 18;
    public const MAX_CURRENT_AGE = 65;
    public const MIN_RETIREMENT_AGE = 19;
    public const MAX_RETIREMENT_AGE = 75;
    public const MIN_EXPECTED_RETURN = 5.0;
    public const MAX_EXPECTED_RETURN = 15.0;
    public const MIN_ANNUITY_PERCENT = 40.0;
    public const MAX_ANNUITY_PERCENT = 100.0;
    public const MIN_ANNUITY_RATE = 4.0;
    public const MAX_ANNUITY_RATE = 10.0;
    public const SMALL_CORPUS_THRESHOLD = 500000.0;

    /**
     * Calculate NPS accumulation, superannuation exit breakdown, and year-by-year schedule.
     *
     * @param float $monthlyContribution Monthly investment in NPS Tier-1
     * @param int $currentAge Subscriber current age in years (18 to 65)
     * @param int $retirementAge Target retirement/exit age in years (currentAge + 1 to 75)
     * @param float $expectedReturnRate Expected annualized portfolio growth (5% to 15%)
     * @param float $annuityPercent Share of corpus used for annuity purchase (40% to 100%)
     * @param float $annuityReturnRate Expected annual annuity return yield from ASP (4% to 10%)
     * @return array{
     *     monthly_contribution: float,
     *     current_age: int,
     *     retirement_age: int,
     *     tenure_years: int,
     *     expected_return_rate: float,
     *     annuity_percent: float,
     *     annuity_return_rate: float,
     *     total_invested: float,
     *     total_interest: float,
     *     total_corpus: float,
     *     lump_sum_amount: float,
     *     annuity_amount: float,
     *     monthly_pension: float,
     *     is_small_corpus_eligible: bool,
     *     schedule: list<array{
     *         year: int,
     *         age: int,
     *         invested: float,
     *         interest: float,
     *         balance: float,
     *         lump_sum_share: float,
     *         annuity_share: float
     *     }>
     * }
     */
    public static function calculate(
        float $monthlyContribution,
        int $currentAge,
        int $retirementAge,
        float $expectedReturnRate,
        float $annuityPercent = self::DEFAULT_ANNUITY_PERCENT,
        float $annuityReturnRate = self::DEFAULT_ANNUITY_RATE
    ): array {
        $contribution = max(self::MIN_MONTHLY_CONTRIBUTION, min(self::MAX_MONTHLY_CONTRIBUTION, $monthlyContribution));
        $currAge = max(self::MIN_CURRENT_AGE, min(self::MAX_CURRENT_AGE, $currentAge));
        $retAge = max($currAge + 1, min(self::MAX_RETIREMENT_AGE, $retirementAge));
        $returnRate = max(self::MIN_EXPECTED_RETURN, min(self::MAX_EXPECTED_RETURN, $expectedReturnRate));
        $annuityPct = max(self::MIN_ANNUITY_PERCENT, min(self::MAX_ANNUITY_PERCENT, $annuityPercent));
        $annuityRate = max(self::MIN_ANNUITY_RATE, min(self::MAX_ANNUITY_RATE, $annuityReturnRate));

        $tenureYears = $retAge - $currAge;
        $totalMonths = $tenureYears * 12;
        $monthlyRate = ($returnRate / 100.0) / 12.0;

        if ($monthlyRate > 0.0) {
            $compoundedFactor = pow(1.0 + $monthlyRate, $totalMonths);
            $rawCorpus = $contribution * ((1.0 + $monthlyRate) * ($compoundedFactor - 1.0)) / $monthlyRate;
        } else {
            $rawCorpus = $contribution * $totalMonths;
        }

        $totalCorpus = round($rawCorpus);
        $totalInvested = round($contribution * $totalMonths);
        $totalInterest = max(0.0, $totalCorpus - $totalInvested);

        $annuityAmount = round($totalCorpus * ($annuityPct / 100.0));
        $lumpSumAmount = max(0.0, $totalCorpus - $annuityAmount);

        $monthlyAnnuityRate = ($annuityRate / 100.0) / 12.0;
        $monthlyPension = round($annuityAmount * $monthlyAnnuityRate);

        $isSmallCorpusEligible = ($totalCorpus <= self::SMALL_CORPUS_THRESHOLD);

        $schedule = [];
        for ($y = 1; $y <= $tenureYears; $y++) {
            $months = $y * 12;
            $age = $currAge + $y;
            $investedAtYear = round($contribution * $months);

            if ($monthlyRate > 0.0) {
                $factorAtYear = pow(1.0 + $monthlyRate, $months);
                $corpusAtYear = round($contribution * ((1.0 + $monthlyRate) * ($factorAtYear - 1.0)) / $monthlyRate);
            } else {
                $corpusAtYear = $investedAtYear;
            }

            $interestAtYear = max(0.0, $corpusAtYear - $investedAtYear);
            $annuityShareAtYear = round($corpusAtYear * ($annuityPct / 100.0));
            $lumpSumShareAtYear = max(0.0, $corpusAtYear - $annuityShareAtYear);

            $schedule[] = [
                'year' => $y,
                'age' => $age,
                'invested' => (float) $investedAtYear,
                'interest' => (float) $interestAtYear,
                'balance' => (float) $corpusAtYear,
                'lump_sum_share' => (float) $lumpSumShareAtYear,
                'annuity_share' => (float) $annuityShareAtYear,
            ];
        }

        return [
            'monthly_contribution' => $contribution,
            'current_age' => $currAge,
            'retirement_age' => $retAge,
            'tenure_years' => $tenureYears,
            'expected_return_rate' => $returnRate,
            'annuity_percent' => $annuityPct,
            'annuity_return_rate' => $annuityRate,
            'total_invested' => (float) $totalInvested,
            'total_interest' => (float) $totalInterest,
            'total_corpus' => (float) $totalCorpus,
            'lump_sum_amount' => (float) $lumpSumAmount,
            'annuity_amount' => (float) $annuityAmount,
            'monthly_pension' => (float) $monthlyPension,
            'is_small_corpus_eligible' => $isSmallCorpusEligible,
            'schedule' => $schedule,
        ];
    }
}
