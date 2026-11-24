/**
 * Pure, stateless TypeScript calculation engine for Post-Retirement Asset Allocation & Multi-Stream Drawdown.
 * Strict mathematical parity twin of src/Core/Math/PostRetirementEngine.php.
 */

export interface PostRetirementScheduleEntry {
    year: number;
    age: number;
    opening_balance: number;
    annual_living_expense: number;
    annual_pension_received: number;
    annual_swp_drawn: number;
    growth_earned: number;
    closing_balance: number;
    is_depleted: boolean;
}

export interface PostRetirementResult {
    total_initial_corpus: number;
    initial_monthly_swp: number;
    initial_swr_percent: number;
    is_perpetual: boolean;
    depletion_month: number | null;
    depletion_year: number | null;
    depletion_age: number | null;
    final_balance: number;
    bucket_1_cash: number;
    bucket_2_income: number;
    bucket_3_growth: number;
    schedule: PostRetirementScheduleEntry[];
}

export class PostRetirementEngine {
    public static readonly DEFAULT_EXISTING_CORPUS = 5000000.0;
    public static readonly DEFAULT_GRATUITY_LUMP_SUM = 1500000.0;
    public static readonly DEFAULT_NPS_LUMP_SUM = 4000000.0;
    public static readonly DEFAULT_GUARANTEED_PENSION = 25000.0;
    public static readonly DEFAULT_MONTHLY_EXPENSE = 75000.0;
    public static readonly DEFAULT_EXPECTED_RETURN = 8.5;
    public static readonly DEFAULT_INFLATION_RATE = 6.0;
    public static readonly DEFAULT_CURRENT_AGE = 60;
    public static readonly DEFAULT_PLANNING_HORIZON_YEARS = 30;

    public static readonly MIN_EXISTING_CORPUS = 0.0;
    public static readonly MAX_EXISTING_CORPUS = 500000000.0;
    public static readonly MIN_GRATUITY_LUMP_SUM = 0.0;
    public static readonly MAX_GRATUITY_LUMP_SUM = 50000000.0;
    public static readonly MIN_NPS_LUMP_SUM = 0.0;
    public static readonly MAX_NPS_LUMP_SUM = 100000000.0;
    public static readonly MIN_GUARANTEED_PENSION = 0.0;
    public static readonly MAX_GUARANTEED_PENSION = 500000.0;
    public static readonly MIN_MONTHLY_EXPENSE = 10000.0;
    public static readonly MAX_MONTHLY_EXPENSE = 1000000.0;
    public static readonly MIN_EXPECTED_RETURN = 4.0;
    public static readonly MAX_EXPECTED_RETURN = 14.0;
    public static readonly MIN_INFLATION_RATE = 3.0;
    public static readonly MAX_INFLATION_RATE = 10.0;
    public static readonly MIN_CURRENT_AGE = 40;
    public static readonly MAX_CURRENT_AGE = 80;
    public static readonly MIN_PLANNING_HORIZON_YEARS = 10;
    public static readonly MAX_PLANNING_HORIZON_YEARS = 45;

    /**
     * Calculate multi-stream post-retirement drawdown, portfolio longevity, and 3-bucket allocation.
     */
    public static calculate(
        existingCorpus: number,
        gratuityLumpSum: number,
        npsLumpSum: number,
        guaranteedPension: number,
        monthlyExpense: number,
        expectedReturn: number,
        inflationRate: number = PostRetirementEngine.DEFAULT_INFLATION_RATE,
        currentAge: number = PostRetirementEngine.DEFAULT_CURRENT_AGE,
        planningHorizonYears: number = PostRetirementEngine.DEFAULT_PLANNING_HORIZON_YEARS
    ): PostRetirementResult {
        const existing = Math.max(PostRetirementEngine.MIN_EXISTING_CORPUS, Math.min(PostRetirementEngine.MAX_EXISTING_CORPUS, existingCorpus));
        const gratuity = Math.max(PostRetirementEngine.MIN_GRATUITY_LUMP_SUM, Math.min(PostRetirementEngine.MAX_GRATUITY_LUMP_SUM, gratuityLumpSum));
        const nps = Math.max(PostRetirementEngine.MIN_NPS_LUMP_SUM, Math.min(PostRetirementEngine.MAX_NPS_LUMP_SUM, npsLumpSum));
        const pension = Math.max(PostRetirementEngine.MIN_GUARANTEED_PENSION, Math.min(PostRetirementEngine.MAX_GUARANTEED_PENSION, guaranteedPension));
        const expense = Math.max(PostRetirementEngine.MIN_MONTHLY_EXPENSE, Math.min(PostRetirementEngine.MAX_MONTHLY_EXPENSE, monthlyExpense));
        const returnRate = Math.max(PostRetirementEngine.MIN_EXPECTED_RETURN, Math.min(PostRetirementEngine.MAX_EXPECTED_RETURN, expectedReturn));
        const infRate = Math.max(PostRetirementEngine.MIN_INFLATION_RATE, Math.min(PostRetirementEngine.MAX_INFLATION_RATE, inflationRate));
        const age = Math.max(PostRetirementEngine.MIN_CURRENT_AGE, Math.min(PostRetirementEngine.MAX_CURRENT_AGE, Math.floor(currentAge)));
        const horizonYears = Math.max(PostRetirementEngine.MIN_PLANNING_HORIZON_YEARS, Math.min(PostRetirementEngine.MAX_PLANNING_HORIZON_YEARS, Math.floor(planningHorizonYears)));

        const totalInitialCorpus = Math.round(existing + gratuity + nps);
        const initialMonthlySwp = Math.round(Math.max(0.0, expense - pension));

        const initialSwrPercent = totalInitialCorpus > 0.0
            ? Math.round(((initialMonthlySwp * 12.0) / totalInitialCorpus) * 10000.0) / 100.0
            : 0.0;

        // 3-Bucket Asset Allocation
        const bucket1Cash = Math.round(Math.min(totalInitialCorpus, 3.0 * 12.0 * initialMonthlySwp));
        const remainingAfterB1 = Math.max(0.0, totalInitialCorpus - bucket1Cash);
        const bucket2Income = Math.round(Math.min(remainingAfterB1, Math.max(0.0, totalInitialCorpus * 0.40)));
        const bucket3Growth = Math.max(0.0, totalInitialCorpus - bucket1Cash - bucket2Income);

        const monthlyReturn = (returnRate / 100.0) / 12.0;
        const inflationDecimal = infRate / 100.0;

        let balance = totalInitialCorpus;
        let depletionMonth: number | null = null;
        const schedule: PostRetirementScheduleEntry[] = [];

        for (let y = 1; y <= horizonYears; y++) {
            const openingBalance = balance;
            const yIdx = y - 1;

            const annualLivingExpense = Math.round((expense * Math.pow(1.0 + inflationDecimal, yIdx)) * 12.0);
            const annualPensionReceived = Math.round(pension * 12.0);
            const annualSwpDrawn = Math.round(Math.max(0.0, annualLivingExpense - annualPensionReceived));

            const monthlyExpenseThisYear = expense * Math.pow(1.0 + inflationDecimal, yIdx);
            const monthlySwpThisYear = Math.max(0.0, monthlyExpenseThisYear - pension);

            let yearGrowth = 0.0;

            for (let m = 1; m <= 12; m++) {
                const monthGlobal = (y - 1) * 12 + m;

                if (balance <= 0.0) {
                    balance = 0.0;
                    if (depletionMonth === null) {
                        depletionMonth = monthGlobal;
                    }
                    continue;
                }

                const growth = balance * monthlyReturn;
                yearGrowth += growth;
                balance = (balance + growth) - monthlySwpThisYear;

                if (balance <= 0.0) {
                    balance = 0.0;
                    if (depletionMonth === null) {
                        depletionMonth = monthGlobal;
                    }
                }
            }

            schedule.push({
                year: y,
                age: age + y,
                opening_balance: Math.round(openingBalance),
                annual_living_expense: annualLivingExpense,
                annual_pension_received: annualPensionReceived,
                annual_swp_drawn: annualSwpDrawn,
                growth_earned: Math.round(yearGrowth),
                closing_balance: Math.round(balance),
                is_depleted: (balance <= 0.0),
            });
        }

        const isPerpetual = (depletionMonth === null);
        const depletionYear = depletionMonth !== null ? Math.round((depletionMonth / 12.0) * 10.0) / 10.0 : null;
        const depletionAge = depletionMonth !== null ? (age + Math.floor(depletionMonth / 12.0)) : null;

        return {
            total_initial_corpus: totalInitialCorpus,
            initial_monthly_swp: initialMonthlySwp,
            initial_swr_percent: initialSwrPercent,
            is_perpetual: isPerpetual,
            depletion_month: depletionMonth,
            depletion_year: depletionYear,
            depletion_age: depletionAge,
            final_balance: Math.round(balance),
            bucket_1_cash: bucket1Cash,
            bucket_2_income: bucket2Income,
            bucket_3_growth: bucket3Growth,
            schedule: schedule,
        };
    }
}
