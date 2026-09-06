/**
 * CompoundInterestEngine
 * High-precision TypeScript mathematical engine for Compound Interest simulations.
 * Implements A = P * (1 + r/n)^(n*t) matching PHP parity.
 */

import { MathPrecisionHelper } from '../helpers/MathPrecisionHelper.ts';

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
                principal: MathPrecisionHelper.round2(p),
                final_amount: MathPrecisionHelper.round2(p),
                total_interest: 0,
                effective_annual_rate: MathPrecisionHelper.round4(ear),
                rule_of_72_years: rPercent > 0 ? MathPrecisionHelper.round2(72.0 / rPercent) : null,
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
                opening_balance: MathPrecisionHelper.round2(openingBalance),
                interest_earned: MathPrecisionHelper.round2(interestEarned),
                closing_balance: MathPrecisionHelper.round2(closingBalance)
            });

            currentBalance = closingBalance;
        }

        const finalAmount = currentBalance;
        const totalInterest = Math.max(0, finalAmount - p);

        return {
            principal: MathPrecisionHelper.round2(p),
            final_amount: MathPrecisionHelper.round2(finalAmount),
            total_interest: MathPrecisionHelper.round2(totalInterest),
            effective_annual_rate: MathPrecisionHelper.round4(effectiveAnnualRate),
            rule_of_72_years: ruleOf72 !== null ? MathPrecisionHelper.round2(ruleOf72) : null,
            schedule
        };
    }
}
