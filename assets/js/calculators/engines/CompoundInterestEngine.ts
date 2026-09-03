/**
 * CompoundInterestEngine
 * High-precision TypeScript mathematical engine for Compound Interest simulations.
 * Implements A = P * (1 + r/n)^(n*t) matching PHP parity.
 */

export interface CompoundInterestScheduleRow {
    year: number;
    opening_balance: number;
    interest_earned: number;
    closing_balance: number;
}

export interface CompoundInterestResult {
    principal: number;
    final_amount: number;
    total_interest: number;
    effective_annual_rate: number;
    rule_of_72_years: number | null;
    schedule: CompoundInterestScheduleRow[];
}

export class CompoundInterestEngine {
    public static calculate(
        principal: number,
        annualRate: number,
        years: number,
        compoundingFrequency: number = 1
    ): CompoundInterestResult {
        const p = Math.max(0, principal);
        const rPercent = Math.max(0, annualRate);
        const t = Math.max(0, Math.floor(years));
        const n = Math.max(1, Math.floor(compoundingFrequency));

        const r = rPercent / 100.0;

        if (p === 0 || t === 0) {
            const ear = n > 0 && r > 0 ? (Math.pow(1.0 + (r / n), n) - 1.0) * 100.0 : 0.0;
            return {
                principal: Number(p.toFixed(2)),
                final_amount: Number(p.toFixed(2)),
                total_interest: 0,
                effective_annual_rate: Number(ear.toFixed(4)),
                rule_of_72_years: rPercent > 0 ? Number((72.0 / rPercent).toFixed(2)) : null,
                schedule: []
            };
        }

        const effectiveRateDecimal = Math.pow(1.0 + (r / n), n) - 1.0;
        const effectiveAnnualRate = effectiveRateDecimal * 100.0;
        const ruleOf72 = rPercent > 0 ? 72.0 / rPercent : null;

        const schedule: CompoundInterestScheduleRow[] = [];
        let currentBalance = p;

        for (let year = 1; year <= t; year++) {
            const openingBalance = currentBalance;
            const closingBalance = openingBalance * Math.pow(1.0 + (r / n), n);
            const interestEarned = closingBalance - openingBalance;

            schedule.push({
                year,
                opening_balance: Number(openingBalance.toFixed(2)),
                interest_earned: Number(interestEarned.toFixed(2)),
                closing_balance: Number(closingBalance.toFixed(2))
            });

            currentBalance = closingBalance;
        }

        const finalAmount = currentBalance;
        const totalInterest = Math.max(0, finalAmount - p);

        return {
            principal: Number(p.toFixed(2)),
            final_amount: Number(finalAmount.toFixed(2)),
            total_interest: Number(totalInterest.toFixed(2)),
            effective_annual_rate: Number(effectiveAnnualRate.toFixed(4)),
            rule_of_72_years: ruleOf72 !== null ? Number(ruleOf72.toFixed(2)) : null,
            schedule
        };
    }
}
