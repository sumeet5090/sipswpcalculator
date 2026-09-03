/**
 * Pure, stateless TypeScript calculation engine for Indian Bank Fixed Deposits (FD).
 * Strict mathematical parity twin of src/Core/Math/FdEngine.php.
 */

export interface FdScheduleEntry {
    year: number;
    opening_balance: number;
    interest_earned: number;
    payout_withdrawn: number;
    closing_balance: number;
}

export interface FdResult {
    principal: number;
    effective_rate: number;
    duration_years: number;
    maturity_amount: number;
    total_interest: number;
    periodic_payout: number;
    payout_frequency: string;
    is_senior_citizen: boolean;
    estimated_annual_tds: number;
    post_tax_yield_30_percent: number;
    yearly_schedule: FdScheduleEntry[];
}

export class FdEngine {
    public static readonly DEFAULT_ANNUAL_INTEREST_RATE = 7.0;
    public static readonly SENIOR_CITIZEN_RATE_BONUS = 0.50;
    public static readonly TDS_THRESHOLD_GENERAL = 40000.0;
    public static readonly TDS_THRESHOLD_SENIOR = 50000.0;
    public static readonly TDS_RATE_STANDARD = 0.10;

    public static calculate(
        principal: number,
        annualRate: number = FdEngine.DEFAULT_ANNUAL_INTEREST_RATE,
        durationYears: number = 1.0,
        isSeniorCitizen: boolean = false,
        payoutFrequency: 'cumulative' | 'monthly' | 'quarterly' | 'annual' = 'cumulative'
    ): FdResult {
        const p = Math.max(0.0, principal);
        const baseRate = Math.max(0.0, Math.min(25.0, annualRate));
        const effectiveRate = baseRate + (isSeniorCitizen ? FdEngine.SENIOR_CITIZEN_RATE_BONUS : 0.0);
        const t = Math.max(0.25, Math.min(30.0, durationYears));

        const quarterlyRate = (effectiveRate / 100.0) / 4.0;
        const totalQuarters = Math.round(t * 4);

        let maturityAmount = 0.0;
        let totalInterest = 0.0;
        let periodicPayout = 0.0;

        if (payoutFrequency === 'cumulative') {
            maturityAmount = p * Math.pow(1.0 + quarterlyRate, totalQuarters);
            totalInterest = maturityAmount - p;
            periodicPayout = 0.0;
        } else if (payoutFrequency === 'monthly') {
            const monthlyDiscountedRate = Math.pow(1.0 + quarterlyRate, 1.0 / 3.0) - 1.0;
            periodicPayout = p * monthlyDiscountedRate;
            totalInterest = periodicPayout * (t * 12.0);
            maturityAmount = p;
        } else if (payoutFrequency === 'quarterly') {
            periodicPayout = p * quarterlyRate;
            totalInterest = periodicPayout * totalQuarters;
            maturityAmount = p;
        } else {
            periodicPayout = p * (effectiveRate / 100.0);
            totalInterest = periodicPayout * t;
            maturityAmount = p;
        }

        const annualInterestApprox = t > 0 ? (totalInterest / t) : 0.0;
        const tdsThreshold = isSeniorCitizen ? FdEngine.TDS_THRESHOLD_SENIOR : FdEngine.TDS_THRESHOLD_GENERAL;
        const estimatedAnnualTds = (annualInterestApprox > tdsThreshold)
            ? Math.round(annualInterestApprox * FdEngine.TDS_RATE_STANDARD * 100) / 100
            : 0.0;

        const postTaxYield30 = Math.round(effectiveRate * (1.0 - 0.312) * 100) / 100;

        const schedule: FdScheduleEntry[] = [];
        let runningBalance = p;
        const fullYears = Math.ceil(t);

        for (let yr = 1; yr <= fullYears; yr++) {
            const yrOpening = runningBalance;
            let fraction = (yr <= Math.floor(t)) ? 1.0 : (t - Math.floor(t));
            if (fraction <= 0.0) {
                fraction = 1.0;
            }

            let yrInterest = 0.0;
            let yrPayout = 0.0;

            if (payoutFrequency === 'cumulative') {
                const quartersInYear = Math.round(fraction * 4);
                const yearEndBalance = yrOpening * Math.pow(1.0 + quarterlyRate, quartersInYear);
                yrInterest = yearEndBalance - yrOpening;
                yrPayout = 0.0;
                runningBalance = yearEndBalance;
            } else {
                yrInterest = p * (effectiveRate / 100.0) * fraction;
                yrPayout = yrInterest;
                runningBalance = p;
            }

            schedule.push({
                year: yr,
                opening_balance: Math.round(yrOpening * 100) / 100,
                interest_earned: Math.round(yrInterest * 100) / 100,
                payout_withdrawn: Math.round(yrPayout * 100) / 100,
                closing_balance: Math.round(runningBalance * 100) / 100
            });
        }

        return {
            principal: Math.round(p * 100) / 100,
            effective_rate: Math.round(effectiveRate * 100) / 100,
            duration_years: Math.round(t * 100) / 100,
            maturity_amount: Math.round(maturityAmount * 100) / 100,
            total_interest: Math.round(totalInterest * 100) / 100,
            periodic_payout: Math.round(periodicPayout * 100) / 100,
            payout_frequency: payoutFrequency,
            is_senior_citizen: isSeniorCitizen,
            estimated_annual_tds: estimatedAnnualTds,
            post_tax_yield_30_percent: postTaxYield30,
            yearly_schedule: schedule
        };
    }
}
