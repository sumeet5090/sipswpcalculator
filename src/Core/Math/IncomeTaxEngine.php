<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for Indian Income Tax (Old vs. FY 2025-26 New Regime) & HRA.
 *
 * Implements:
 * - Section 10(13A) HRA Exemption (Metro 50% vs. Non-Metro 40%).
 * - Old Tax Regime (₹50,000 Standard Deduction, HRA, 80C, 80D, 80CCD(1B), 24(b), ₹5L Section 87A Rebate).
 * - FY 2025-26 New Tax Regime (₹75,000 Standard Deduction, 0%-30% slabs up to ₹24L, ₹12L Section 87A Rebate + Marginal Relief).
 * - Surcharge (10% > ₹50L, 15% > ₹1Cr, 25% > ₹2Cr) + 4% Health & Education Cess.
 */
final class IncomeTaxEngine
{
    public const DEFAULT_GROSS_SALARY = 1500000.0;
    public const DEFAULT_BASIC_SALARY = 750000.0;
    public const DEFAULT_HRA_RECEIVED = 300000.0;
    public const DEFAULT_RENT_PAID = 360000.0;
    public const DEFAULT_IS_METRO = true;
    public const DEFAULT_SEC_80C = 150000.0;
    public const DEFAULT_SEC_80D = 25000.0;
    public const DEFAULT_NPS_80CCD1B = 50000.0;
    public const DEFAULT_HOME_LOAN_24B = 0.0;

    public const MIN_GROSS_SALARY = 300000.0;
    public const MAX_GROSS_SALARY = 50000000.0;
    public const MIN_BASIC_SALARY = 100000.0;
    public const MAX_BASIC_SALARY = 25000000.0;
    public const MAX_HRA_RENT = 10000000.0;
    public const MAX_SEC_80C = 150000.0;
    public const MAX_SEC_80D = 100000.0;
    public const MAX_NPS_80CCD1B = 50000.0;
    public const MAX_HOME_LOAN_24B = 200000.0;

    private const OLD_STD_DEDUCTION = 50000.0;
    private const NEW_STD_DEDUCTION = 75000.0;

    /**
     * @return array{
     *     gross_salary: float,
     *     basic_salary: float,
     *     hra_received: float,
     *     rent_paid: float,
     *     is_metro: bool,
     *     hra_exemption: float,
     *     old_total_deductions: float,
     *     old_taxable_income: float,
     *     old_total_tax: float,
     *     old_effective_rate: float,
     *     new_taxable_income: float,
     *     new_total_tax: float,
     *     new_effective_rate: float,
     *     recommended_regime: string,
     *     tax_savings: float,
     *     schedule: list<array{
     *         bracket_index: int,
     *         label: string,
     *         gross_income: float,
     *         old_tax: float,
     *         new_tax: float,
     *         savings: float
     *     }>
     * }
     */
    public static function calculate(
        float $grossSalary,
        float $basicSalary,
        float $hraReceived = self::DEFAULT_HRA_RECEIVED,
        float $rentPaid = self::DEFAULT_RENT_PAID,
        bool $isMetro = self::DEFAULT_IS_METRO,
        float $sec80c = self::DEFAULT_SEC_80C,
        float $sec80d = self::DEFAULT_SEC_80D,
        float $nps80ccd1b = self::DEFAULT_NPS_80CCD1B,
        float $homeLoan24b = self::DEFAULT_HOME_LOAN_24B
    ): array {
        $gross = max(self::MIN_GROSS_SALARY, min(self::MAX_GROSS_SALARY, $grossSalary));
        $basic = max(self::MIN_BASIC_SALARY, min($gross, min(self::MAX_BASIC_SALARY, $basicSalary)));
        $hraRec = max(0.0, min($gross, min(self::MAX_HRA_RENT, $hraReceived)));
        $rent = max(0.0, min(self::MAX_HRA_RENT, $rentPaid));
        $c80c = max(0.0, min(self::MAX_SEC_80C, $sec80c));
        $c80d = max(0.0, min(self::MAX_SEC_80D, $sec80d));
        $nps = max(0.0, min(self::MAX_NPS_80CCD1B, $nps80ccd1b));
        $loan = max(0.0, min(self::MAX_HOME_LOAN_24B, $homeLoan24b));

        // Section 10(13A) HRA Exemption
        $hraRule1 = $hraRec;
        $hraRule2 = max(0.0, $rent - (0.10 * $basic));
        $hraRule3 = ($isMetro ? 0.50 : 0.40) * $basic;
        $hraExemption = min($hraRule1, $hraRule2, $hraRule3);

        // Old Regime
        $oldTotalDeductions = self::OLD_STD_DEDUCTION + $hraExemption + $c80c + $c80d + $nps + $loan;
        $oldTaxable = max(0.0, $gross - $oldTotalDeductions);
        $oldTotalTax = self::computeOldRegimeTax($oldTaxable);

        // New Regime (FY 2025-26)
        $newTaxable = max(0.0, $gross - self::NEW_STD_DEDUCTION);
        $newTotalTax = self::computeNewRegimeTax($newTaxable);

        $recommendedRegime = $newTotalTax <= $oldTotalTax ? 'new' : 'old';
        $taxSavings = abs($oldTotalTax - $newTotalTax);

        $oldEffectiveRate = $gross > 0.0 ? round(($oldTotalTax / $gross) * 100.0, 2) : 0.0;
        $newEffectiveRate = $gross > 0.0 ? round(($newTotalTax / $gross) * 100.0, 2) : 0.0;

        // Progressive comparison schedule across salary tiers up to user's gross salary
        $tiers = [
            ['label' => '₹7.5L', 'gross' => 750000.0],
            ['label' => '₹10L', 'gross' => 1000000.0],
            ['label' => '₹12.75L', 'gross' => 1275000.0],
            ['label' => '₹15L', 'gross' => 1500000.0],
            ['label' => '₹20L', 'gross' => 2000000.0],
            ['label' => '₹25L', 'gross' => 2500000.0],
            ['label' => '₹35L', 'gross' => 3500000.0],
            ['label' => '₹50L', 'gross' => 5000000.0],
        ];

        $schedule = [];
        foreach ($tiers as $idx => $tier) {
            $tGross = $tier['gross'];
            $tOldTaxable = max(0.0, $tGross - $oldTotalDeductions);
            $tNewTaxable = max(0.0, $tGross - self::NEW_STD_DEDUCTION);
            $tOldTax = self::computeOldRegimeTax($tOldTaxable);
            $tNewTax = self::computeNewRegimeTax($tNewTaxable);
            $schedule[] = [
                'bracket_index' => $idx + 1,
                'label' => $tier['label'],
                'gross_income' => $tGross,
                'old_tax' => $tOldTax,
                'new_tax' => $tNewTax,
                'savings' => abs($tOldTax - $tNewTax),
            ];
        }

        return [
            'gross_salary' => (float) $gross,
            'basic_salary' => (float) $basic,
            'hra_received' => (float) $hraRec,
            'rent_paid' => (float) $rent,
            'is_metro' => $isMetro,
            'hra_exemption' => (float) round($hraExemption),
            'old_total_deductions' => (float) round($oldTotalDeductions),
            'old_taxable_income' => (float) round($oldTaxable),
            'old_total_tax' => (float) $oldTotalTax,
            'old_effective_rate' => $oldEffectiveRate,
            'new_taxable_income' => (float) round($newTaxable),
            'new_total_tax' => (float) $newTotalTax,
            'new_effective_rate' => $newEffectiveRate,
            'recommended_regime' => $recommendedRegime,
            'tax_savings' => (float) $taxSavings,
            'schedule' => $schedule,
        ];
    }

    private static function computeOldRegimeTax(float $taxable): float
    {
        if ($taxable <= 500000.0) {
            return 0.0;
        }

        $tax = 0.0;
        if ($taxable > 1000000.0) {
            $tax += ($taxable - 1000000.0) * 0.30;
            $tax += 500000.0 * 0.20;
            $tax += 250000.0 * 0.05;
        } elseif ($taxable > 500000.0) {
            $tax += ($taxable - 500000.0) * 0.20;
            $tax += 250000.0 * 0.05;
        }

        $surcharge = self::getSurchargeRate($taxable);
        return (float) round($tax * (1.0 + $surcharge) * 1.04);
    }

    private static function computeNewRegimeTax(float $taxable): float
    {
        if ($taxable <= 1200000.0) {
            return 0.0;
        }

        $tax = 0.0;
        $slabs = [
            [400000.0, 800000.0, 0.05],
            [800000.0, 1200000.0, 0.10],
            [1200000.0, 1600000.0, 0.15],
            [1600000.0, 2000000.0, 0.20],
            [2000000.0, 2400000.0, 0.25],
        ];

        foreach ($slabs as [$low, $high, $rate]) {
            if ($taxable > $low) {
                $tax += (min($taxable, $high) - $low) * $rate;
            }
        }
        if ($taxable > 2400000.0) {
            $tax += ($taxable - 2400000.0) * 0.30;
        }

        // Marginal relief near ₹12L Section 87A rebate threshold
        if ($taxable <= 1275000.0) {
            $tax = min($tax, $taxable - 1200000.0);
        }

        $surcharge = self::getSurchargeRate($taxable);
        return (float) round($tax * (1.0 + $surcharge) * 1.04);
    }

    private static function getSurchargeRate(float $taxable): float
    {
        if ($taxable > 20000000.0) {
            return 0.25;
        }
        if ($taxable > 10000000.0) {
            return 0.15;
        }
        if ($taxable > 5000000.0) {
            return 0.10;
        }
        return 0.0;
    }
}
