<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for Indian Statutory Gratuity.
 *
 * Implements official Government of India rules under:
 * - Payment of Gratuity Act, 1972:
 *   - Formula for covered employees: (15 * Last Drawn Salary * Effective Tenure) / 26
 *   - 6-Month Rounding Rule: Final year tenure > 6 months rounds up to the next full year.
 *   - Formula for non-covered employees: (15 * Last Drawn Salary * Completed Years) / 30 (no fractional rounding).
 *   - Minimum continuous service for vesting: 5 years (Section 4(1)).
 * - Income Tax Act, 1961 (Section 10(10)):
 *   - Statutory tax-free exemption ceiling for non-government employees: ₹20,00,000 (₹20 Lakhs).
 *   - Excess above ₹20 Lakhs is taxable under standard salary income slabs.
 */
final class GratuityEngine
{
    public const DEFAULT_MONTHLY_SALARY = 50000.0;
    public const DEFAULT_TENURE_YEARS = 5;
    public const MIN_MONTHLY_SALARY = 1000.0;
    public const MAX_MONTHLY_SALARY = 10000000.0;
    public const MIN_TENURE_YEARS = 1;
    public const MAX_TENURE_YEARS = 50;
    public const STATUTORY_VESTING_YEARS = 5;
    public const STATUTORY_TAX_EXEMPTION_LIMIT = 2000000.0;

    /**
     * Calculate complete gratuity entitlement, statutory tax breakdown, and year-by-year accrual schedule.
     *
     * @param float $monthlySalary Last drawn monthly Basic Salary + Dearness Allowance (DA)
     * @param int $tenureYears Completed continuous years of service
     * @param int $tenureMonths Additional months in the final year of service (0 to 11)
     * @param bool $isCoveredByAct Whether employer is covered under Payment of Gratuity Act, 1972 (default true)
     * @return array{
     *     monthly_salary: float,
     *     tenure_years: int,
     *     tenure_months: int,
     *     effective_years: int,
     *     is_covered_by_act: bool,
     *     is_eligible: bool,
     *     gratuity_amount: float,
     *     tax_exempt_amount: float,
     *     taxable_amount: float,
     *     schedule: list<array{
     *         year: int,
     *         accrued_amount: float,
     *         is_vested: bool,
     *         tax_exempt: float,
     *         taxable: float
     *     }>
     * }
     */
    public static function calculate(
        float $monthlySalary,
        int $tenureYears,
        int $tenureMonths = 0,
        bool $isCoveredByAct = true
    ): array {
        // Clamp inputs within sensible statutory boundaries
        $salary = max(self::MIN_MONTHLY_SALARY, min(self::MAX_MONTHLY_SALARY, $monthlySalary));
        $years = max(self::MIN_TENURE_YEARS, min(self::MAX_TENURE_YEARS, $tenureYears));
        $months = max(0, min(11, $tenureMonths));

        // Determine effective tenure based on Act coverage and 6-month rule
        $effectiveYears = $years;
        if ($isCoveredByAct && $months > 6) {
            $effectiveYears += 1;
        }

        // Vesting check: Minimum 5 continuous years required under Section 4(1)
        $isEligible = ($effectiveYears >= self::STATUTORY_VESTING_YEARS);

        // Calculate gross payable gratuity
        $divisor = $isCoveredByAct ? 26.0 : 30.0;
        $unroundedGratuity = ($salary * 15.0 * $effectiveYears) / $divisor;
        $gratuityAmount = $isEligible ? round($unroundedGratuity) : 0.0;

        // Calculate Section 10(10) tax exemption breakdown
        $taxExemptAmount = min($gratuityAmount, self::STATUTORY_TAX_EXEMPTION_LIMIT);
        $taxableAmount = max(0.0, $gratuityAmount - $taxExemptAmount);

        // Build year-by-year accrual schedule up to effective tenure
        $schedule = [];
        $scheduleHorizon = max($effectiveYears, self::STATUTORY_VESTING_YEARS);
        for ($y = 1; $y <= $scheduleHorizon; $y++) {
            $accrued = round(($salary * 15.0 * $y) / $divisor);
            $vested = ($y >= self::STATUTORY_VESTING_YEARS);
            $exempt = min($accrued, self::STATUTORY_TAX_EXEMPTION_LIMIT);
            $taxable = max(0.0, $accrued - $exempt);

            $schedule[] = [
                'year'           => $y,
                'accrued_amount' => $accrued,
                'is_vested'      => $vested,
                'tax_exempt'     => $exempt,
                'taxable'        => $taxable,
            ];
        }

        return [
            'monthly_salary'    => $salary,
            'tenure_years'      => $years,
            'tenure_months'     => $months,
            'effective_years'   => $effectiveYears,
            'is_covered_by_act' => $isCoveredByAct,
            'is_eligible'       => $isEligible,
            'gratuity_amount'   => $gratuityAmount,
            'tax_exempt_amount' => $taxExemptAmount,
            'taxable_amount'    => $taxableAmount,
            'schedule'          => $schedule,
        ];
    }
}
