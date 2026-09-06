/**
 * Pure, stateless TypeScript calculation engine for the Public Provident Fund (PPF).
 * Strict mathematical parity twin of src/Core/Math/PpfEngine.php.
 */

import { MathPrecisionHelper } from '../helpers/MathPrecisionHelper.ts';

export interface PpfScheduleEntry {
    year: number;
    opening_balance: number;
    annual_deposit: number;
    interest_earned: number;
    closing_balance: number;
}

export interface PpfResult {
    total_invested: number;
    total_interest: number;
    maturity_amount: number;
    interest_rate: number;
    tenure_years: number;
    schedule: PpfScheduleEntry[];
}

export class PpfEngine {
    public static readonly DEFAULT_ANNUAL_INTEREST_RATE = 7.1;
    public static readonly MIN_ANNUAL_DEPOSIT = 500.0;
    public static readonly MAX_ANNUAL_DEPOSIT = 150000.0;
    public static readonly STATUTORY_TENURE_YEARS = 15;

    public static calculate(
        yearlyDeposit: number,
        interestRate: number = PpfEngine.DEFAULT_ANNUAL_INTEREST_RATE,
        tenureYears: number = PpfEngine.STATUTORY_TENURE_YEARS,
        depositTiming: 'beginning' | 'monthly' = 'beginning'
    ): PpfResult {
        const deposit = Math.max(0.0, Math.min(PpfEngine.MAX_ANNUAL_DEPOSIT, yearlyDeposit));
        const rate = Math.max(0.0, Math.min(20.0, interestRate));
        const years = Math.max(1, Math.min(50, Math.floor(tenureYears)));
        const monthlyRate = (rate / 100.0) / 12.0;

        let openingBalance = 0.0;
        let totalInvested = 0.0;
        let totalInterest = 0.0;
        const schedule: PpfScheduleEntry[] = [];

        for (let year = 1; year <= years; year++) {
            const yearOpening = openingBalance;
            const yearDeposit = deposit;
            totalInvested += yearDeposit;

            let annualInterest = 0.0;
            let closingBalance = 0.0;

            if (depositTiming === 'monthly') {
                const monthlyDeposit = yearDeposit / 12.0;
                let runningBalance = yearOpening;

                for (let month = 1; month <= 12; month++) {
                    runningBalance += monthlyDeposit;
                    const monthlyInterest = runningBalance * monthlyRate;
                    annualInterest += monthlyInterest;
                }
                closingBalance = yearOpening + yearDeposit + annualInterest;
            } else {
                const balanceForInterest = yearOpening + yearDeposit;
                annualInterest = balanceForInterest * (rate / 100.0);
                closingBalance = balanceForInterest + annualInterest;
            }

            const roundedInterest = MathPrecisionHelper.round2(annualInterest);
            const roundedClosing = MathPrecisionHelper.round2(closingBalance);

            schedule.push({
                year,
                opening_balance: MathPrecisionHelper.round2(yearOpening),
                annual_deposit: MathPrecisionHelper.round2(yearDeposit),
                interest_earned: roundedInterest,
                closing_balance: roundedClosing
            });

            totalInterest += roundedInterest;
            openingBalance = roundedClosing;
        }

        return {
            total_invested: MathPrecisionHelper.round2(totalInvested),
            total_interest: MathPrecisionHelper.round2(totalInterest),
            maturity_amount: MathPrecisionHelper.round2(openingBalance),
            interest_rate: rate,
            tenure_years: years,
            schedule
        };
    }
}
