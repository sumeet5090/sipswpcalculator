/**
 * EmiEngine
 * High-precision TypeScript Equated Monthly Installment (EMI) and loan amortization engine.
 * Computes monthly EMI, total interest, and annual amortization schedule matching PHP parity.
 */

export interface EmiScheduleRow {
    year: number;
    opening_balance: number;
    principal_paid: number;
    interest_paid: number;
    total_paid: number;
    closing_balance: number;
}

export interface EmiResult {
    principal: number;
    annual_rate: number;
    tenure_years: number;
    monthly_emi: number;
    total_amount_payable: number;
    total_interest: number;
    interest_ratio_percentage: number;
    schedule: EmiScheduleRow[];
}

export class EmiEngine {
    public static calculate(
        principal: number,
        annualRate: number,
        tenureYears: number
    ): EmiResult {
        if (principal <= 0) {
            throw new Error('Principal amount must be strictly greater than 0.');
        }

        if (tenureYears <= 0) {
            throw new Error('Loan tenure must be at least 1 year.');
        }

        const p = principal;
        const rAnnual = Math.max(0, annualRate);
        const totalMonths = Math.floor(tenureYears * 12);

        let monthlyEmi: number;
        let totalPayable: number;
        let totalInterest: number;
        let interestRatio: number;

        if (rAnnual === 0) {
            monthlyEmi = p / totalMonths;
            totalPayable = p;
            totalInterest = 0;
            interestRatio = 0;
        } else {
            const rMonthly = (rAnnual / 100.0) / 12.0;
            const factor = Math.pow(1.0 + rMonthly, totalMonths);
            monthlyEmi = (p * rMonthly * factor) / (factor - 1.0);
            totalPayable = monthlyEmi * totalMonths;
            totalInterest = totalPayable - p;
            interestRatio = (totalInterest / p) * 100.0;
        }

        const schedule: EmiScheduleRow[] = [];
        let balance = p;
        const rMonthly = (rAnnual / 100.0) / 12.0;

        for (let year = 1; year <= tenureYears; year++) {
            const yearOpening = balance;
            let yearPrincipal = 0.0;
            let yearInterest = 0.0;

            for (let m = 1; m <= 12; m++) {
                const monthInterest = rAnnual > 0 ? (balance * rMonthly) : 0.0;
                let monthPrincipal = monthlyEmi - monthInterest;

                if (monthPrincipal > balance) {
                    monthPrincipal = balance;
                }

                balance = Math.max(0, balance - monthPrincipal);
                yearPrincipal += monthPrincipal;
                yearInterest += monthInterest;
            }

            schedule.push({
                year,
                opening_balance: Number(yearOpening.toFixed(2)),
                principal_paid: Number(yearPrincipal.toFixed(2)),
                interest_paid: Number(yearInterest.toFixed(2)),
                total_paid: Number((yearPrincipal + yearInterest).toFixed(2)),
                closing_balance: Number(balance.toFixed(2))
            });
        }

        return {
            principal: Number(p.toFixed(2)),
            annual_rate: Number(rAnnual.toFixed(2)),
            tenure_years: tenureYears,
            monthly_emi: Number(monthlyEmi.toFixed(2)),
            total_amount_payable: Number(totalPayable.toFixed(2)),
            total_interest: Number(totalInterest.toFixed(2)),
            interest_ratio_percentage: Number(interestRatio.toFixed(2)),
            schedule
        };
    }
}
