export interface DesktopTableHeaders {
    year: string;
    beginBalance: string;
    sipMonthly: string;
    annualSip: string;
    totalInvested: string;
    swpMonthly: string;
    annualSwp: string;
    totalWithdrawn: string;
    interest: string;
    tax: string;
    endCorpus: string;
}

export interface MobileCompactHeaders {
    year: string;
    invested: string;
    interest: string;
    corpus: string;
}

export interface MobileCardLabels {
    yearPrefix: string;
    finalSuffix: string;
    invested: string;
    interest: string;
    withdrawn: string;
    interestPositivePrefix: boolean;
    isDebtOrCostMode: boolean;
}

export interface ExcelSummarySchema {
    investedLabel: string;
    investedNote: string;
    gainsLabel: string;
    gainsNote: string;
    includeWithdrawnRow: boolean;
    withdrawnLabel: string;
    withdrawnNote: string;
    finalLabel: string;
    finalNote: string;
    allowInflationDiscount: boolean;
    useLastRowInsteadOfSum: boolean;
}

export interface ExcelExportSchema {
    sheetName: string;
    headers: readonly [string, string, string, string, string, string, string, string, string, string];
    summary: ExcelSummarySchema;
}

export interface ModeTableConfig {
    desktop: DesktopTableHeaders;
    mobileCompact: MobileCompactHeaders;
    mobileCard: MobileCardLabels;
    showMultiplier: boolean;
    excel: ExcelExportSchema;
}

const DEFAULT_CONFIG: ModeTableConfig = {
    desktop: {
        year: 'Year',
        beginBalance: 'Start Corpus',
        sipMonthly: 'Monthly SIP',
        annualSip: 'Annual SIP',
        totalInvested: 'Total Invested',
        swpMonthly: 'Monthly SWP',
        annualSwp: 'Annual SWP',
        totalWithdrawn: 'Total Withdrawn',
        interest: 'Annual Gain',
        tax: 'LTCG Tax (§112A)',
        endCorpus: 'End Corpus',
    },
    mobileCompact: {
        year: 'Yr',
        invested: 'Invested',
        interest: 'Gains',
        corpus: 'Corpus',
    },
    mobileCard: {
        yearPrefix: 'Year',
        finalSuffix: 'Maturity',
        invested: 'Total Invested',
        interest: 'Annual Gain',
        withdrawn: 'Total Withdrawn',
        interestPositivePrefix: true,
        isDebtOrCostMode: false,
    },
    showMultiplier: true,
    excel: {
        sheetName: 'Yearly Cashflow Schedule',
        headers: [
            'Year',
            'Opening Balance',
            'Monthly SIP',
            'Annual Contribution',
            'Cumulative Invested',
            'Monthly SWP',
            'Annual Withdrawal',
            'Annual Interest / Gains',
            'Closing Balance',
            'Post-Tax Value (Sec 112A)',
        ],
        summary: {
            investedLabel: 'Total Invested / Principal',
            investedNote: 'Total out-of-pocket capital',
            gainsLabel: 'Total Compounding Gains',
            gainsNote: 'Cumulative interest / returns earned',
            includeWithdrawnRow: true,
            withdrawnLabel: 'Total SWP / Payouts Withdrawn',
            withdrawnNote: 'Cumulative systematic withdrawals',
            finalLabel: 'Final Portfolio Corpus',
            finalNote: 'Closing balance at horizon end',
            allowInflationDiscount: true,
            useLastRowInsteadOfSum: false,
        },
    },
};

function mergeConfig(overrides: {
    desktop?: Partial<DesktopTableHeaders>;
    mobileCompact?: Partial<MobileCompactHeaders>;
    mobileCard?: Partial<MobileCardLabels>;
    showMultiplier?: boolean;
    excel?: {
        sheetName?: string;
        headers?: readonly [string, string, string, string, string, string, string, string, string, string];
        summary?: Partial<ExcelSummarySchema>;
    };
}): ModeTableConfig {
    return {
        desktop: { ...DEFAULT_CONFIG.desktop, ...(overrides.desktop ?? {}) },
        mobileCompact: { ...DEFAULT_CONFIG.mobileCompact, ...(overrides.mobileCompact ?? {}) },
        mobileCard: { ...DEFAULT_CONFIG.mobileCard, ...(overrides.mobileCard ?? {}) },
        showMultiplier: overrides.showMultiplier ?? DEFAULT_CONFIG.showMultiplier,
        excel: {
            sheetName: overrides.excel?.sheetName ?? DEFAULT_CONFIG.excel.sheetName,
            headers: overrides.excel?.headers ?? DEFAULT_CONFIG.excel.headers,
            summary: { ...DEFAULT_CONFIG.excel.summary, ...(overrides.excel?.summary ?? {}) },
        },
    };
}

const MODE_TABLE_SCHEMAS: Record<string, ModeTableConfig> = {
    compound_interest: mergeConfig({
        desktop: {
            annualSip: 'Initial Deposit',
            totalInvested: 'Principal',
            interest: 'Annual Interest',
            endCorpus: 'Closing Balance',
        },
        mobileCompact: { year: 'Yr', invested: 'Principal', interest: 'Interest', corpus: 'Balance' },
        mobileCard: { invested: 'Principal', interest: 'Annual Interest' },
        excel: {
            headers: [
                'Year',
                'Opening Balance',
                'Monthly Deposit',
                'Initial Deposit',
                'Total Principal',
                '—',
                '—',
                'Annual Interest Earned',
                'Closing Balance',
                'Post-Tax Value',
            ],
            summary: {
                includeWithdrawnRow: false,
                gainsNote: 'Cumulative compound interest accrued',
            },
        },
    }),
    cagr: mergeConfig({
        desktop: {
            annualSip: 'Initial Outlay',
            totalInvested: 'Initial Value',
            interest: 'Annual Appreciation',
            endCorpus: 'Trajectory Value',
        },
        mobileCompact: { year: 'Yr', invested: 'Initial', interest: 'Growth', corpus: 'Value' },
        mobileCard: { finalSuffix: 'End Value', invested: 'Initial Value', interest: 'Annual Growth' },
        excel: {
            headers: [
                'Year',
                'Opening Value',
                '—',
                'Initial Investment',
                'Initial Value',
                '—',
                '—',
                'Annual Appreciation',
                'Trajectory Value',
                'After-Tax Value',
            ],
            summary: {
                investedLabel: 'Initial Investment Value',
                gainsLabel: 'Total Capital Appreciation',
                includeWithdrawnRow: false,
                finalLabel: 'Final Trajectory Value',
            },
        },
    }),
    emi: mergeConfig({
        desktop: {
            beginBalance: 'Opening Loan',
            sipMonthly: 'Monthly EMI',
            annualSip: 'Principal / Yr',
            totalInvested: 'Principal Repaid',
            interest: 'Interest Paid',
            endCorpus: 'Remaining Debt',
        },
        mobileCompact: { year: 'Yr', invested: 'Repaid', interest: 'Interest', corpus: 'Balance' },
        mobileCard: {
            finalSuffix: 'Closure',
            invested: 'Principal Repaid',
            interest: 'Interest Paid',
            interestPositivePrefix: false,
            isDebtOrCostMode: true,
        },
        showMultiplier: false,
        excel: {
            sheetName: 'Loan Amortization Schedule',
            headers: [
                'Year',
                'Opening Debt',
                'Monthly EMI',
                'Annual Principal Paid',
                'Cumulative Principal Repaid',
                '—',
                '—',
                'Annual Interest Paid',
                'Remaining Debt Balance',
                'Remaining Debt Balance',
            ],
            summary: {
                investedLabel: 'Cumulative Principal Repaid',
                investedNote: 'Total loan principal repaid over tenure',
                gainsLabel: 'Total Loan Interest Payable',
                gainsNote: 'Cumulative interest cost over loan tenure',
                includeWithdrawnRow: false,
                finalLabel: 'Closing Loan Balance',
                finalNote: 'Remaining principal balance at horizon end',
                allowInflationDiscount: false,
            },
        },
    }),
    inflation: mergeConfig({
        desktop: {
            beginBalance: 'Prior Year Cost',
            annualSip: 'Base Cost',
            totalInvested: 'Current Cost',
            interest: 'Annual Escalation',
            endCorpus: 'Future Cost',
        },
        mobileCompact: { year: 'Yr', invested: 'Today', interest: 'Surge', corpus: 'Future' },
        mobileCard: { finalSuffix: 'Projected', invested: 'Current Cost', interest: 'Cost Escalation' },
        showMultiplier: false,
        excel: {
            sheetName: 'Inflation Projection',
            headers: [
                'Year',
                'Prior Living Cost',
                '—',
                'Current Base Cost',
                'Current Cost (Today)',
                '—',
                '—',
                'Annual Price Escalation',
                'Inflation-Adjusted Future Cost',
                'Inflation-Adjusted Future Cost',
            ],
            summary: {
                investedLabel: 'Current Cost (Today)',
                investedNote: 'Base expense in today\'s purchasing power',
                gainsLabel: 'Cumulative Inflation Surge',
                gainsNote: 'Total compounding cost escalation',
                includeWithdrawnRow: false,
                finalLabel: 'Projected Future Cost',
                finalNote: 'Nominal future cost after compounding inflation',
                allowInflationDiscount: false,
            },
        },
    }),
    ppf: mergeConfig({
        desktop: {
            annualSip: 'Annual Deposit',
            totalInvested: 'Total Deposited',
            interest: 'Tax-Free Interest',
            endCorpus: 'PPF Balance',
        },
        mobileCompact: { year: 'Yr', invested: 'Deposited', interest: 'Interest', corpus: 'PPF Value' },
        mobileCard: { invested: 'Total Deposited', interest: 'Annual Interest' },
        excel: {
            headers: [
                'Year',
                'Opening PPF Balance',
                '—',
                'Annual Deposit',
                'Cumulative Deposited',
                '—',
                '—',
                'Tax-Free Interest Earned',
                'Closing PPF Balance',
                'EEE Tax-Free Balance',
            ],
            summary: {
                investedLabel: 'Cumulative PPF Deposited',
                investedNote: 'Total EEE contributions deposited',
                gainsLabel: 'Total Tax-Free Interest',
                gainsNote: '100% Section 10 tax-exempt interest',
                includeWithdrawnRow: false,
                finalLabel: 'Tax-Free PPF Maturity Balance',
                allowInflationDiscount: false,
            },
        },
    }),
    fd: mergeConfig({
        desktop: {
            annualSip: 'Deposit',
            totalInvested: 'FD Principal',
            totalWithdrawn: 'Interest Payouts',
            interest: 'Interest Earned',
            endCorpus: 'FD Value',
        },
        mobileCompact: { year: 'Yr', invested: 'Principal', interest: 'Interest', corpus: 'FD Value' },
        mobileCard: {
            invested: 'FD Principal',
            interest: 'Interest Earned',
            withdrawn: 'Interest Payouts',
        },
        excel: {
            headers: [
                'Year',
                'Opening FD Balance',
                '—',
                'Deposit',
                'FD Principal',
                '—',
                'Interest Payout Taken',
                'Annual Interest Earned',
                'Closing FD Value',
                'Post-Tax FD Value',
            ],
            summary: {
                investedLabel: 'Fixed Deposit Principal',
                investedNote: 'Original FD deposit amount',
                gainsLabel: 'Total FD Interest Accrued',
                withdrawnLabel: 'Cumulative Interest Payouts',
                withdrawnNote: 'For periodic non-cumulative payout FDs',
                finalLabel: 'FD Maturity Value',
                allowInflationDiscount: false,
            },
        },
    }),
    gratuity: mergeConfig({
        desktop: {
            year: 'Service Yr',
            beginBalance: 'Prior Gratuity',
            totalInvested: 'Monthly Basic + DA',
            interest: 'Accrued Gratuity',
            endCorpus: 'Gratuity Payable',
        },
        mobileCompact: { year: 'Yr', invested: 'Salary', interest: 'Accrued', corpus: 'Payable' },
        mobileCard: {
            finalSuffix: 'Settlement',
            invested: 'Monthly Basic + DA',
            interest: 'Accrued Gratuity',
            interestPositivePrefix: false,
        },
        showMultiplier: false,
        excel: {
            sheetName: 'Gratuity Accrual Schedule',
            headers: [
                'Service Year',
                'Prior Accrued Gratuity',
                '—',
                '—',
                'Monthly Basic + DA',
                '—',
                '—',
                'Cumulative Accrued Gratuity',
                'Gratuity Payable',
                'Est. Post-Exemption Value',
            ],
            summary: {
                investedLabel: 'Monthly Basic + DA Base',
                investedNote: 'Last drawn eligible salary base',
                gainsLabel: 'Final Statutory Gratuity',
                gainsNote: 'Cumulative gratuity under Payment of Gratuity Act',
                includeWithdrawnRow: false,
                finalLabel: 'Gratuity Payable',
                allowInflationDiscount: false,
                useLastRowInsteadOfSum: true,
            },
        },
    }),
    nps: mergeConfig({
        desktop: {
            sipMonthly: 'Monthly Tier-1',
            annualSip: 'Annual Tier-1',
            totalInvested: 'Total Contributed',
            interest: 'Annual Growth',
            endCorpus: 'NPS Corpus',
        },
        mobileCompact: { year: 'Yr', invested: 'Contrib', interest: 'Growth', corpus: 'NPS Value' },
        mobileCard: { finalSuffix: 'Retirement', invested: 'Total Contributed', interest: 'Annual Growth' },
        excel: {
            headers: [
                'Year',
                'Opening NPS Balance',
                'Monthly Tier-1',
                'Annual Contribution',
                'Cumulative Contributed',
                '—',
                '—',
                'Annual Market Growth',
                'Closing NPS Corpus',
                '60% Tax-Free Lumpsum Portion',
            ],
            summary: {
                investedLabel: 'Cumulative NPS Contributions',
                gainsLabel: 'Total NPS Wealth Gain',
                includeWithdrawnRow: false,
                finalLabel: 'Total Retirement NPS Corpus',
            },
        },
    }),
    post_retirement: mergeConfig({
        desktop: {
            beginBalance: 'Opening Corpus',
            annualSip: 'Annual Pension',
            totalInvested: 'Initial Corpus',
            totalWithdrawn: 'Cumulative SWP',
            interest: 'Portfolio Growth',
            endCorpus: 'Closing Corpus',
        },
        mobileCompact: { year: 'Yr', invested: 'Initial', interest: 'Growth', corpus: 'Balance' },
        mobileCard: {
            finalSuffix: 'Horizon',
            invested: 'Initial Corpus',
            interest: 'Portfolio Growth',
            withdrawn: 'Total SWP Drawn',
        },
        showMultiplier: false,
        excel: {
            sheetName: 'Retirement Drawdown Schedule',
            headers: [
                'Retirement Year',
                'Opening Corpus',
                '—',
                'Annual Fixed Pension',
                'Initial Corpus',
                '—',
                'Annual SWP Drawdown',
                'Annual Portfolio Growth',
                'Closing Balance',
                'Closing Balance',
            ],
            summary: {
                investedLabel: 'Initial Retirement Corpus',
                investedNote: 'Total capital entering retirement drawdown',
                gainsLabel: 'Total Drawdown Portfolio Growth',
                withdrawnLabel: 'Cumulative SWP Drawdown',
                withdrawnNote: 'Inflation-indexed withdrawals across retirement',
                finalLabel: 'End-of-Horizon Portfolio Corpus',
                allowInflationDiscount: false,
            },
        },
    }),
    epf: mergeConfig({
        desktop: {
            sipMonthly: 'Monthly EPF',
            annualSip: 'Annual EPF',
            totalInvested: 'Total Contributed',
            interest: 'Tax-Free Interest',
            endCorpus: 'EPF Corpus',
        },
        mobileCompact: { year: 'Yr', invested: 'Contrib', interest: 'Interest', corpus: 'EPF Value' },
        mobileCard: { finalSuffix: 'Retirement', invested: 'Total Contributed', interest: 'Tax-Free Interest' },
        excel: {
            headers: [
                'Year',
                'Opening EPF Balance',
                'Monthly EPF (EE+ER)',
                'Annual Contribution',
                'Cumulative Contributed',
                '—',
                '—',
                'Tax-Free EPF Interest',
                'Closing EPF Balance',
                'Retirement EPF Corpus',
            ],
            summary: {
                investedLabel: 'Total EPF Contributions',
                gainsLabel: 'Total EPF Compound Interest',
                includeWithdrawnRow: false,
                finalLabel: 'Projected Retirement EPF Corpus',
                allowInflationDiscount: false,
            },
        },
    }),
    tax_harvest: mergeConfig({
        desktop: {
            beginBalance: 'Cost Basis',
            annualSip: 'Gain Harvested',
            totalInvested: 'Reset Cost Basis',
            interest: 'Annual Tax Saved',
            endCorpus: 'Net Wealth',
        },
        mobileCompact: { year: 'Yr', invested: 'Basis', interest: 'Saved', corpus: 'Net Value' },
        mobileCard: { finalSuffix: 'Final', invested: 'Reset Cost Basis', interest: 'Tax Saved' },
        showMultiplier: false,
        excel: {
            sheetName: 'Tax Harvesting Schedule',
            headers: [
                'Year',
                'Effective Cost Basis',
                '—',
                'Gain Harvested Yr',
                'Reset Cost Basis',
                '—',
                '—',
                'Annual LTCG Tax Saved',
                'Net Harvested Wealth',
                'Net Harvested Wealth',
            ],
            summary: {
                investedLabel: 'Reset Cost Basis',
                investedNote: 'Step-up purchase cost basis',
                gainsLabel: 'Cumulative Section 112A Tax Saved',
                includeWithdrawnRow: false,
                finalLabel: 'Net Post-Tax Harvested Wealth',
                allowInflationDiscount: false,
            },
        },
    }),
    income_tax: mergeConfig({
        desktop: {
            year: 'Income Tier',
            beginBalance: 'Gross Income',
            annualSip: 'Old Regime Tax',
            totalInvested: 'Old Regime Tax',
            interest: 'Regime Savings',
            endCorpus: 'New Regime Tax',
        },
        mobileCompact: { year: 'Tier', invested: 'Old Tax', interest: 'Diff', corpus: 'New Tax' },
        mobileCard: {
            yearPrefix: 'Tier',
            finalSuffix: 'Selected CTC',
            invested: 'Old Regime Tax',
            interest: 'Regime Savings',
            interestPositivePrefix: false,
        },
        showMultiplier: false,
        excel: {
            sheetName: 'Income Tax Comparison',
            headers: [
                'Income Tier',
                'Gross Salary Tier',
                '—',
                'Old Regime Tax',
                'Old Regime Tax',
                '—',
                '—',
                'Tax Savings Differential',
                'New Regime Tax',
                'In-Hand Under Best Regime',
            ],
            summary: {
                investedLabel: 'Old Regime Tax (Selected Tier)',
                investedNote: 'Annual tax under Old Regime with deductions',
                gainsLabel: 'Net Regime Tax Advantage',
                gainsNote: 'Annual saving under optimal tax regime',
                includeWithdrawnRow: false,
                finalLabel: 'New Regime Tax (Selected Tier)',
                finalNote: 'Annual tax under FY 2025-26 New Regime slabs',
                allowInflationDiscount: false,
                useLastRowInsteadOfSum: true,
            },
        },
    }),
};

export const ModeTableSchema = {
    get(mode: string | null | undefined): ModeTableConfig {
        if (mode && MODE_TABLE_SCHEMAS[mode]) {
            return MODE_TABLE_SCHEMAS[mode];
        }
        return DEFAULT_CONFIG;
    },
};
