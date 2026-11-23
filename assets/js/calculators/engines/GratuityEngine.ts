/**
 * Pure, stateless TypeScript calculation engine for Indian Statutory Gratuity.
 * Strict mathematical parity twin of src/Core/Math/GratuityEngine.php.
 */

export interface GratuityScheduleEntry {
    year: number;
    accrued_amount: number;
    is_vested: boolean;
    tax_exempt: number;
    taxable: number;
}

export interface GratuityResult {
    monthly_salary: number;
    tenure_years: number;
    tenure_months: number;
    effective_years: number;
    is_covered_by_act: boolean;
    is_eligible: boolean;
    gratuity_amount: number;
    tax_exempt_amount: number;
    taxable_amount: number;
    schedule: GratuityScheduleEntry[];
}

export class GratuityEngine {
    public static readonly DEFAULT_MONTHLY_SALARY = 50000.0;
    public static readonly DEFAULT_TENURE_YEARS = 5;
    public static readonly MIN_MONTHLY_SALARY = 1000.0;
    public static readonly MAX_MONTHLY_SALARY = 10000000.0;
    public static readonly MIN_TENURE_YEARS = 1;
    public static readonly MAX_TENURE_YEARS = 50;
    public static readonly STATUTORY_VESTING_YEARS = 5;
    public static readonly STATUTORY_TAX_EXEMPTION_LIMIT = 2000000.0;

    /**
     * Calculate gratuity payout, tax exemption, and annual accrual progression.
     */
    public static calculate(
        monthlySalary: number,
        tenureYears: number,
        tenureMonths: number = 0,
        isCoveredByAct: boolean = true
    ): GratuityResult {
        const salary = Math.max(GratuityEngine.MIN_MONTHLY_SALARY, Math.min(GratuityEngine.MAX_MONTHLY_SALARY, monthlySalary));
        const years = Math.max(GratuityEngine.MIN_TENURE_YEARS, Math.min(GratuityEngine.MAX_TENURE_YEARS, Math.floor(tenureYears)));
        const months = Math.max(0, Math.min(11, Math.floor(tenureMonths)));

        let effectiveYears = years;
        if (isCoveredByAct && months > 6) {
            effectiveYears += 1;
        }

        const isEligible = effectiveYears >= GratuityEngine.STATUTORY_VESTING_YEARS;
        const divisor = isCoveredByAct ? 26.0 : 30.0;
        const unroundedGratuity = (salary * 15.0 * effectiveYears) / divisor;
        const gratuityAmount = isEligible ? Math.round(unroundedGratuity) : 0.0;

        const taxExemptAmount = Math.min(gratuityAmount, GratuityEngine.STATUTORY_TAX_EXEMPTION_LIMIT);
        const taxableAmount = Math.max(0.0, gratuityAmount - taxExemptAmount);

        const schedule: GratuityScheduleEntry[] = [];
        const scheduleHorizon = Math.max(effectiveYears, GratuityEngine.STATUTORY_VESTING_YEARS);

        for (let y = 1; y <= scheduleHorizon; y++) {
            const accrued = Math.round((salary * 15.0 * y) / divisor);
            const vested = y >= GratuityEngine.STATUTORY_VESTING_YEARS;
            const exempt = Math.min(accrued, GratuityEngine.STATUTORY_TAX_EXEMPTION_LIMIT);
            const taxable = Math.max(0.0, accrued - exempt);

            schedule.push({
                year: y,
                accrued_amount: accrued,
                is_vested: vested,
                tax_exempt: exempt,
                taxable: taxable
            });
        }

        return {
            monthly_salary: salary,
            tenure_years: years,
            tenure_months: months,
            effective_years: effectiveYears,
            is_covered_by_act: isCoveredByAct,
            is_eligible: isEligible,
            gratuity_amount: gratuityAmount,
            tax_exempt_amount: taxExemptAmount,
            taxable_amount: taxableAmount,
            schedule: schedule
        };
    }
}
