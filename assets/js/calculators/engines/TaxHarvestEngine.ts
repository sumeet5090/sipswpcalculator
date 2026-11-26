export interface TaxHarvestScheduleEntry {
    year: number;
    gross_value: number;
    buy_hold_tax: number;
    harvested_this_year: number;
    harvested_exit_tax: number;
    tax_saved: number;
    net_harvested_wealth: number;
}

export interface TaxHarvestResult {
    portfolio_value: number;
    invested_cost: number;
    effective_cost_basis: number;
    expected_return: number;
    holding_years: number;
    grandfather_fmv: number;
    include_cess: boolean;
    effective_tax_rate_pct: number;
    buy_hold_tax: number;
    harvested_tax: number;
    cumulative_tax_saved: number;
    total_gains_harvested: number;
    final_gross_corpus: number;
    net_post_tax_wealth: number;
    march_april_savings: number;
    schedule: TaxHarvestScheduleEntry[];
}

export class TaxHarvestEngine {
    public static readonly DEFAULT_PORTFOLIO_VALUE = 2500000.0;
    public static readonly DEFAULT_INVESTED_COST = 1500000.0;
    public static readonly DEFAULT_EXPECTED_RETURN = 12.0;
    public static readonly DEFAULT_HOLDING_YEARS = 10;
    public static readonly DEFAULT_GRANDFATHER_FMV = 0.0;
    public static readonly DEFAULT_INCLUDE_CESS = true;

    private static readonly MIN_PORTFOLIO_VALUE = 100000.0;
    private static readonly MAX_PORTFOLIO_VALUE = 100000000.0;
    private static readonly MIN_INVESTED_COST = 50000.0;
    private static readonly MAX_INVESTED_COST = 100000000.0;
    private static readonly MIN_EXPECTED_RETURN = 4.0;
    private static readonly MAX_EXPECTED_RETURN = 25.0;
    private static readonly MIN_HOLDING_YEARS = 1;
    private static readonly MAX_HOLDING_YEARS = 30;
    private static readonly MIN_GRANDFATHER_FMV = 0.0;
    private static readonly MAX_GRANDFATHER_FMV = 100000000.0;

    private static readonly ANNUAL_EXEMPTION_LIMIT = 125000.0;
    private static readonly BASE_LTCG_RATE = 0.125;
    private static readonly CESS_MULTIPLIER = 1.04;

    public static calculate(
        portfolioValue: number,
        investedCost: number,
        expectedReturn: number = TaxHarvestEngine.DEFAULT_EXPECTED_RETURN,
        holdingYears: number = TaxHarvestEngine.DEFAULT_HOLDING_YEARS,
        grandfatherFmv: number = TaxHarvestEngine.DEFAULT_GRANDFATHER_FMV,
        includeCess: boolean = TaxHarvestEngine.DEFAULT_INCLUDE_CESS
    ): TaxHarvestResult {
        const value0 = Math.max(this.MIN_PORTFOLIO_VALUE, Math.min(this.MAX_PORTFOLIO_VALUE, portfolioValue));
        const cost = Math.max(this.MIN_INVESTED_COST, Math.min(this.MAX_INVESTED_COST, investedCost));
        const returnPct = Math.max(this.MIN_EXPECTED_RETURN, Math.min(this.MAX_EXPECTED_RETURN, expectedReturn));
        const years = Math.max(this.MIN_HOLDING_YEARS, Math.min(this.MAX_HOLDING_YEARS, Math.trunc(holdingYears)));
        const fmv = Math.max(this.MIN_GRANDFATHER_FMV, Math.min(this.MAX_GRANDFATHER_FMV, grandfatherFmv));

        const effectiveCost0 = fmv > 0.0
            ? Math.max(cost, Math.min(fmv, value0))
            : Math.min(cost, value0);

        const taxRate = includeCess
            ? this.BASE_LTCG_RATE * this.CESS_MULTIPLIER
            : this.BASE_LTCG_RATE;
        const r = returnPct / 100.0;

        const currentGain = Math.max(0.0, value0 - effectiveCost0);
        const singleFyTax = Math.round(Math.max(0.0, currentGain - this.ANNUAL_EXEMPTION_LIMIT) * taxRate);
        const splitFyTax = Math.round(Math.max(0.0, currentGain - (2.0 * this.ANNUAL_EXEMPTION_LIMIT)) * taxRate);
        const marchAprilSavings = Math.max(0.0, singleFyTax - splitFyTax);

        let harvestCost = effectiveCost0;
        let totalHarvested = 0.0;
        const schedule: TaxHarvestScheduleEntry[] = [];

        let finalGross = value0;
        let finalHoldTax = 0.0;
        let finalHarvestTax = 0.0;

        for (let y = 1; y <= years; y++) {
            const grossValue = value0 * Math.pow(1.0 + r, y);
            const holdGain = Math.max(0.0, grossValue - effectiveCost0);
            const holdTax = Math.round(Math.max(0.0, holdGain - this.ANNUAL_EXEMPTION_LIMIT) * taxRate);

            const unrealizedForHarvest = Math.max(0.0, grossValue - harvestCost);
            const harvestedThisYear = Math.min(unrealizedForHarvest, this.ANNUAL_EXEMPTION_LIMIT);
            harvestCost += harvestedThisYear;
            totalHarvested += harvestedThisYear;

            const remainingTaxable = Math.max(0.0, grossValue - harvestCost);
            const harvestExitTax = Math.round(remainingTaxable * taxRate);
            const taxSaved = Math.max(0.0, holdTax - harvestExitTax);
            const netHarvestedWealth = grossValue - harvestExitTax;

            schedule.push({
                year: y,
                gross_value: Math.round(grossValue),
                buy_hold_tax: holdTax,
                harvested_this_year: Math.round(harvestedThisYear),
                harvested_exit_tax: harvestExitTax,
                tax_saved: taxSaved,
                net_harvested_wealth: Math.round(netHarvestedWealth),
            });

            if (y === years) {
                finalGross = grossValue;
                finalHoldTax = holdTax;
                finalHarvestTax = harvestExitTax;
            }
        }

        const cumulativeTaxSaved = Math.max(0.0, finalHoldTax - finalHarvestTax);

        return {
            portfolio_value: value0,
            invested_cost: cost,
            effective_cost_basis: Math.round(effectiveCost0),
            expected_return: returnPct,
            holding_years: years,
            grandfather_fmv: fmv,
            include_cess: includeCess,
            effective_tax_rate_pct: Math.round(taxRate * 10000.0) / 100.0,
            buy_hold_tax: finalHoldTax,
            harvested_tax: finalHarvestTax,
            cumulative_tax_saved: cumulativeTaxSaved,
            total_gains_harvested: Math.round(totalHarvested),
            final_gross_corpus: Math.round(finalGross),
            net_post_tax_wealth: Math.round(finalGross - finalHarvestTax),
            march_april_savings: marchAprilSavings,
            schedule,
        };
    }
}
