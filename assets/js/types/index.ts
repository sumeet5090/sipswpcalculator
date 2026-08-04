export interface InvestmentInputs {
    sip: number;
    years: number;
    rate: number;
    stepup: number;
    lumpsum: number;
    enable_swp: boolean;
    swp_withdrawal: number;
    swp_stepup: number;
    swp_years: number;
    swp_rate: number;
    inflation: number;
    corpus?: number;
}

export interface YearResult {
    year: number;
    begin_balance: number;
    sip_monthly: number | null;
    annual_contribution: number;
    cumulative_invested: number;
    swp_monthly?: number | null;
    annual_withdrawal?: number | null;
    cumulative_withdrawals?: number;
    interest: number;
    combined_total: number;
    ltcg_tax?: number;
    post_tax_total?: number;
}

export interface CalculatorConfig {
    [key: string]: {
        min: number;
        max: number;
        default: number;
        step: number;
    };
}
