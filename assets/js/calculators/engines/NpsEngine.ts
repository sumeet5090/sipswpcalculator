/**
 * Pure, stateless TypeScript calculation engine for National Pension System (NPS) Tier-1.
 * Strict mathematical parity twin of src/Core/Math/NpsEngine.php.
 */

export interface NpsScheduleEntry {
    year: number;
    age: number;
    invested: number;
    interest: number;
    balance: number;
    lump_sum_share: number;
    annuity_share: number;
}

export interface NpsResult {
    monthly_contribution: number;
    current_age: number;
    retirement_age: number;
    tenure_years: number;
    expected_return_rate: number;
    annuity_percent: number;
    annuity_return_rate: number;
    total_invested: number;
    total_interest: number;
    total_corpus: number;
    lump_sum_amount: number;
    annuity_amount: number;
    monthly_pension: number;
    is_small_corpus_eligible: boolean;
    schedule: NpsScheduleEntry[];
}

export class NpsEngine {
    public static readonly DEFAULT_MONTHLY_CONTRIBUTION = 5000.0;
    public static readonly DEFAULT_CURRENT_AGE = 30;
    public static readonly DEFAULT_RETIREMENT_AGE = 60;
    public static readonly DEFAULT_EXPECTED_RETURN = 10.0;
    public static readonly DEFAULT_ANNUITY_PERCENT = 40.0;
    public static readonly DEFAULT_ANNUITY_RATE = 6.0;

    public static readonly MIN_MONTHLY_CONTRIBUTION = 500.0;
    public static readonly MAX_MONTHLY_CONTRIBUTION = 150000.0;
    public static readonly MIN_CURRENT_AGE = 18;
    public static readonly MAX_CURRENT_AGE = 65;
    public static readonly MIN_RETIREMENT_AGE = 19;
    public static readonly MAX_RETIREMENT_AGE = 75;
    public static readonly MIN_EXPECTED_RETURN = 5.0;
    public static readonly MAX_EXPECTED_RETURN = 15.0;
    public static readonly MIN_ANNUITY_PERCENT = 40.0;
    public static readonly MAX_ANNUITY_PERCENT = 100.0;
    public static readonly MIN_ANNUITY_RATE = 4.0;
    public static readonly MAX_ANNUITY_RATE = 10.0;
    public static readonly SMALL_CORPUS_THRESHOLD = 500000.0;

    /**
     * Calculate NPS accumulation, superannuation exit breakdown, and year-by-year schedule.
     */
    public static calculate(
        monthlyContribution: number,
        currentAge: number,
        retirementAge: number,
        expectedReturnRate: number,
        annuityPercent: number = NpsEngine.DEFAULT_ANNUITY_PERCENT,
        annuityReturnRate: number = NpsEngine.DEFAULT_ANNUITY_RATE
    ): NpsResult {
        const contribution = Math.max(NpsEngine.MIN_MONTHLY_CONTRIBUTION, Math.min(NpsEngine.MAX_MONTHLY_CONTRIBUTION, monthlyContribution));
        const currAge = Math.max(NpsEngine.MIN_CURRENT_AGE, Math.min(NpsEngine.MAX_CURRENT_AGE, Math.floor(currentAge)));
        const retAge = Math.max(currAge + 1, Math.min(NpsEngine.MAX_RETIREMENT_AGE, Math.floor(retirementAge)));
        const returnRate = Math.max(NpsEngine.MIN_EXPECTED_RETURN, Math.min(NpsEngine.MAX_EXPECTED_RETURN, expectedReturnRate));
        const annuityPct = Math.max(NpsEngine.MIN_ANNUITY_PERCENT, Math.min(NpsEngine.MAX_ANNUITY_PERCENT, annuityPercent));
        const annuityRate = Math.max(NpsEngine.MIN_ANNUITY_RATE, Math.min(NpsEngine.MAX_ANNUITY_RATE, annuityReturnRate));

        const tenureYears = retAge - currAge;
        const totalMonths = tenureYears * 12;
        const monthlyRate = (returnRate / 100.0) / 12.0;

        let rawCorpus = 0.0;
        if (monthlyRate > 0.0) {
            const compoundedFactor = Math.pow(1.0 + monthlyRate, totalMonths);
            rawCorpus = contribution * ((1.0 + monthlyRate) * (compoundedFactor - 1.0)) / monthlyRate;
        } else {
            rawCorpus = contribution * totalMonths;
        }

        const totalCorpus = Math.round(rawCorpus);
        const totalInvested = Math.round(contribution * totalMonths);
        const totalInterest = Math.max(0.0, totalCorpus - totalInvested);

        const annuityAmount = Math.round(totalCorpus * (annuityPct / 100.0));
        const lumpSumAmount = Math.max(0.0, totalCorpus - annuityAmount);

        const monthlyAnnuityRate = (annuityRate / 100.0) / 12.0;
        const monthlyPension = Math.round(annuityAmount * monthlyAnnuityRate);

        const isSmallCorpusEligible = (totalCorpus <= NpsEngine.SMALL_CORPUS_THRESHOLD);

        const schedule: NpsScheduleEntry[] = [];
        for (let y = 1; y <= tenureYears; y++) {
            const months = y * 12;
            const age = currAge + y;
            const investedAtYear = Math.round(contribution * months);

            let corpusAtYear = 0.0;
            if (monthlyRate > 0.0) {
                const factorAtYear = Math.pow(1.0 + monthlyRate, months);
                corpusAtYear = Math.round(contribution * ((1.0 + monthlyRate) * (factorAtYear - 1.0)) / monthlyRate);
            } else {
                corpusAtYear = investedAtYear;
            }

            const interestAtYear = Math.max(0.0, corpusAtYear - investedAtYear);
            const annuityShareAtYear = Math.round(corpusAtYear * (annuityPct / 100.0));
            const lumpSumShareAtYear = Math.max(0.0, corpusAtYear - annuityShareAtYear);

            schedule.push({
                year: y,
                age: age,
                invested: investedAtYear,
                interest: interestAtYear,
                balance: corpusAtYear,
                lump_sum_share: lumpSumShareAtYear,
                annuity_share: annuityShareAtYear,
            });
        }

        return {
            monthly_contribution: contribution,
            current_age: currAge,
            retirement_age: retAge,
            tenure_years: tenureYears,
            expected_return_rate: returnRate,
            annuity_percent: annuityPct,
            annuity_return_rate: annuityRate,
            total_invested: totalInvested,
            total_interest: totalInterest,
            total_corpus: totalCorpus,
            lump_sum_amount: lumpSumAmount,
            annuity_amount: annuityAmount,
            monthly_pension: monthlyPension,
            is_small_corpus_eligible: isSmallCorpusEligible,
            schedule: schedule,
        };
    }
}
