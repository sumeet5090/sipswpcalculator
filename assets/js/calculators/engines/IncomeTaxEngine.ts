export interface IncomeTaxScheduleEntry {
    bracket_index: number;
    label: string;
    gross_income: number;
    old_tax: number;
    new_tax: number;
    savings: number;
}

export interface IncomeTaxResult {
    gross_salary: number;
    basic_salary: number;
    hra_received: number;
    rent_paid: number;
    is_metro: boolean;
    hra_exemption: number;
    old_total_deductions: number;
    old_taxable_income: number;
    old_total_tax: number;
    old_effective_rate: number;
    new_taxable_income: number;
    new_total_tax: number;
    new_effective_rate: number;
    recommended_regime: 'old' | 'new';
    tax_savings: number;
    schedule: IncomeTaxScheduleEntry[];
}

export class IncomeTaxEngine {
    public static readonly DEFAULT_GROSS_SALARY = 1500000.0;
    public static readonly DEFAULT_BASIC_SALARY = 750000.0;
    public static readonly DEFAULT_HRA_RECEIVED = 300000.0;
    public static readonly DEFAULT_RENT_PAID = 360000.0;
    public static readonly DEFAULT_IS_METRO = true;
    public static readonly DEFAULT_SEC_80C = 150000.0;
    public static readonly DEFAULT_SEC_80D = 25000.0;
    public static readonly DEFAULT_NPS_80CCD1B = 50000.0;
    public static readonly DEFAULT_HOME_LOAN_24B = 0.0;

    private static readonly MIN_GROSS_SALARY = 300000.0;
    private static readonly MAX_GROSS_SALARY = 50000000.0;
    private static readonly MIN_BASIC_SALARY = 100000.0;
    private static readonly MAX_BASIC_SALARY = 25000000.0;
    private static readonly MAX_HRA_RENT = 10000000.0;
    private static readonly MAX_SEC_80C = 150000.0;
    private static readonly MAX_SEC_80D = 100000.0;
    private static readonly MAX_NPS_80CCD1B = 50000.0;
    private static readonly MAX_HOME_LOAN_24B = 200000.0;

    private static readonly OLD_STD_DEDUCTION = 50000.0;
    private static readonly NEW_STD_DEDUCTION = 75000.0;

    public static calculate(
        grossSalary: number,
        basicSalary: number,
        hraReceived: number = IncomeTaxEngine.DEFAULT_HRA_RECEIVED,
        rentPaid: number = IncomeTaxEngine.DEFAULT_RENT_PAID,
        isMetro: boolean = IncomeTaxEngine.DEFAULT_IS_METRO,
        sec80c: number = IncomeTaxEngine.DEFAULT_SEC_80C,
        sec80d: number = IncomeTaxEngine.DEFAULT_SEC_80D,
        nps80ccd1b: number = IncomeTaxEngine.DEFAULT_NPS_80CCD1B,
        homeLoan24b: number = IncomeTaxEngine.DEFAULT_HOME_LOAN_24B
    ): IncomeTaxResult {
        const gross = Math.max(this.MIN_GROSS_SALARY, Math.min(this.MAX_GROSS_SALARY, grossSalary));
        const basic = Math.max(this.MIN_BASIC_SALARY, Math.min(gross, Math.min(this.MAX_BASIC_SALARY, basicSalary)));
        const hraRec = Math.max(0.0, Math.min(gross, Math.min(this.MAX_HRA_RENT, hraReceived)));
        const rent = Math.max(0.0, Math.min(this.MAX_HRA_RENT, rentPaid));
        const c80c = Math.max(0.0, Math.min(this.MAX_SEC_80C, sec80c));
        const c80d = Math.max(0.0, Math.min(this.MAX_SEC_80D, sec80d));
        const nps = Math.max(0.0, Math.min(this.MAX_NPS_80CCD1B, nps80ccd1b));
        const loan = Math.max(0.0, Math.min(this.MAX_HOME_LOAN_24B, homeLoan24b));

        const hraRule1 = hraRec;
        const hraRule2 = Math.max(0.0, rent - (0.10 * basic));
        const hraRule3 = (isMetro ? 0.50 : 0.40) * basic;
        const hraExemption = Math.min(hraRule1, hraRule2, hraRule3);

        const oldTotalDeductions = this.OLD_STD_DEDUCTION + hraExemption + c80c + c80d + nps + loan;
        const oldTaxable = Math.max(0.0, gross - oldTotalDeductions);
        const oldTotalTax = this.computeOldRegimeTax(oldTaxable);

        const newTaxable = Math.max(0.0, gross - this.NEW_STD_DEDUCTION);
        const newTotalTax = this.computeNewRegimeTax(newTaxable);

        const recommendedRegime: 'old' | 'new' = newTotalTax <= oldTotalTax ? 'new' : 'old';
        const taxSavings = Math.abs(oldTotalTax - newTotalTax);

        const oldEffectiveRate = gross > 0.0 ? Math.round(((oldTotalTax / gross) * 100.0) * 100.0) / 100.0 : 0.0;
        const newEffectiveRate = gross > 0.0 ? Math.round(((newTotalTax / gross) * 100.0) * 100.0) / 100.0 : 0.0;

        const tiers: { label: string; gross: number }[] = [
            { label: '₹7.5L', gross: 750000.0 },
            { label: '₹10L', gross: 1000000.0 },
            { label: '₹12.75L', gross: 1275000.0 },
            { label: '₹15L', gross: 1500000.0 },
            { label: '₹20L', gross: 2000000.0 },
            { label: '₹25L', gross: 2500000.0 },
            { label: '₹35L', gross: 3500000.0 },
            { label: '₹50L', gross: 5000000.0 },
        ];

        const schedule: IncomeTaxScheduleEntry[] = tiers.map((tier, idx) => {
            const tOldTaxable = Math.max(0.0, tier.gross - oldTotalDeductions);
            const tNewTaxable = Math.max(0.0, tier.gross - this.NEW_STD_DEDUCTION);
            const tOldTax = this.computeOldRegimeTax(tOldTaxable);
            const tNewTax = this.computeNewRegimeTax(tNewTaxable);
            return {
                bracket_index: idx + 1,
                label: tier.label,
                gross_income: tier.gross,
                old_tax: tOldTax,
                new_tax: tNewTax,
                savings: Math.abs(tOldTax - tNewTax),
            };
        });

        return {
            gross_salary: gross,
            basic_salary: basic,
            hra_received: hraRec,
            rent_paid: rent,
            is_metro: isMetro,
            hra_exemption: Math.round(hraExemption),
            old_total_deductions: Math.round(oldTotalDeductions),
            old_taxable_income: Math.round(oldTaxable),
            old_total_tax: oldTotalTax,
            old_effective_rate: oldEffectiveRate,
            new_taxable_income: Math.round(newTaxable),
            new_total_tax: newTotalTax,
            new_effective_rate: newEffectiveRate,
            recommended_regime: recommendedRegime,
            tax_savings: taxSavings,
            schedule,
        };
    }

    private static computeOldRegimeTax(taxable: number): number {
        if (taxable <= 500000.0) {
            return 0.0;
        }

        let tax = 0.0;
        if (taxable > 1000000.0) {
            tax += (taxable - 1000000.0) * 0.30;
            tax += 500000.0 * 0.20;
            tax += 250000.0 * 0.05;
        } else if (taxable > 500000.0) {
            tax += (taxable - 500000.0) * 0.20;
            tax += 250000.0 * 0.05;
        }

        const surcharge = this.getSurchargeRate(taxable);
        return Math.round(tax * (1.0 + surcharge) * 1.04);
    }

    private static computeNewRegimeTax(taxable: number): number {
        if (taxable <= 1200000.0) {
            return 0.0;
        }

        let tax = 0.0;
        const slabs: [number, number, number][] = [
            [400000.0, 800000.0, 0.05],
            [800000.0, 1200000.0, 0.10],
            [1200000.0, 1600000.0, 0.15],
            [1600000.0, 2000000.0, 0.20],
            [2000000.0, 2400000.0, 0.25],
        ];

        for (const [low, high, rate] of slabs) {
            if (taxable > low) {
                tax += (Math.min(taxable, high) - low) * rate;
            }
        }
        if (taxable > 2400000.0) {
            tax += (taxable - 2400000.0) * 0.30;
        }

        if (taxable <= 1275000.0) {
            tax = Math.min(tax, taxable - 1200000.0);
        }

        const surcharge = this.getSurchargeRate(taxable);
        return Math.round(tax * (1.0 + surcharge) * 1.04);
    }

    private static getSurchargeRate(taxable: number): number {
        if (taxable > 20000000.0) return 0.25;
        if (taxable > 10000000.0) return 0.15;
        if (taxable > 5000000.0) return 0.10;
        return 0.0;
    }
}
