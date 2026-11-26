export interface EpfScheduleEntry {
    year: number;
    age: number;
    opening_balance: number;
    employee_contribution: number;
    employer_contribution: number;
    interest_earned: number;
    closing_balance: number;
}

export interface EpfResult {
    basic_salary: number;
    current_age: number;
    retirement_age: number;
    tenure_years: number;
    vpf_percent: number;
    salary_hike: number;
    interest_rate: number;
    current_balance: number;
    cap_eps: boolean;
    total_employee_contribution: number;
    total_employer_contribution: number;
    total_invested: number;
    total_interest: number;
    maturity_corpus: number;
    total_eps_diverted: number;
    exceeds_250k_cap: boolean;
    schedule: EpfScheduleEntry[];
}

export class EpfEngine {
    public static readonly DEFAULT_BASIC_SALARY = 50000.0;
    public static readonly DEFAULT_CURRENT_AGE = 28;
    public static readonly DEFAULT_RETIREMENT_AGE = 58;
    public static readonly DEFAULT_VPF_PERCENT = 0.0;
    public static readonly DEFAULT_SALARY_HIKE = 7.0;
    public static readonly DEFAULT_INTEREST_RATE = 8.25;
    public static readonly DEFAULT_CURRENT_BALANCE = 0.0;
    public static readonly DEFAULT_CAP_EPS = true;

    private static readonly MIN_BASIC_SALARY = 5000.0;
    private static readonly MAX_BASIC_SALARY = 500000.0;
    private static readonly MIN_CURRENT_AGE = 18;
    private static readonly MAX_CURRENT_AGE = 57;
    private static readonly MAX_RETIREMENT_AGE = 60;
    private static readonly MIN_VPF_PERCENT = 0.0;
    private static readonly MAX_VPF_PERCENT = 50.0;
    private static readonly MIN_SALARY_HIKE = 0.0;
    private static readonly MAX_SALARY_HIKE = 20.0;
    private static readonly MIN_INTEREST_RATE = 6.0;
    private static readonly MAX_INTEREST_RATE = 12.0;
    private static readonly MIN_CURRENT_BALANCE = 0.0;
    private static readonly MAX_CURRENT_BALANCE = 50000000.0;

    private static readonly EPS_WAGE_CEILING_CAP = 1250.0;
    private static readonly TAX_FREE_ANNUAL_EMP_CAP = 250000.0;

    public static calculate(
        basicSalary: number,
        currentAge: number,
        retirementAge: number,
        vpfPercent: number = EpfEngine.DEFAULT_VPF_PERCENT,
        salaryHike: number = EpfEngine.DEFAULT_SALARY_HIKE,
        interestRate: number = EpfEngine.DEFAULT_INTEREST_RATE,
        currentBalance: number = EpfEngine.DEFAULT_CURRENT_BALANCE,
        capEps: boolean = EpfEngine.DEFAULT_CAP_EPS
    ): EpfResult {
        const basic = Math.max(this.MIN_BASIC_SALARY, Math.min(this.MAX_BASIC_SALARY, basicSalary));
        const currAge = Math.max(this.MIN_CURRENT_AGE, Math.min(this.MAX_CURRENT_AGE, Math.trunc(currentAge)));
        const retAge = Math.max(currAge + 1, Math.min(this.MAX_RETIREMENT_AGE, Math.trunc(retirementAge)));
        const tenureYears = retAge - currAge;
        const vpfPct = Math.max(this.MIN_VPF_PERCENT, Math.min(this.MAX_VPF_PERCENT, vpfPercent));
        const hikePct = Math.max(this.MIN_SALARY_HIKE, Math.min(this.MAX_SALARY_HIKE, salaryHike));
        const ratePct = Math.max(this.MIN_INTEREST_RATE, Math.min(this.MAX_INTEREST_RATE, interestRate));
        const initBalance = Math.max(this.MIN_CURRENT_BALANCE, Math.min(this.MAX_CURRENT_BALANCE, currentBalance));

        const monthlyRate = (ratePct / 100.0) / 12.0;
        const hikeDecimal = hikePct / 100.0;

        let balance = initBalance;
        let cumEmployee = 0.0;
        let cumEmployer = 0.0;
        let cumInterest = 0.0;
        let cumEps = 0.0;
        let exceeds250kCap = false;
        const schedule: EpfScheduleEntry[] = [];

        for (let y = 1; y <= tenureYears; y++) {
            const openingBalance = balance;
            const basicThisYear = basic * Math.pow(1.0 + hikeDecimal, y - 1);

            const empMonthly = basicThisYear * 0.12;
            const vpfMonthly = basicThisYear * (vpfPct / 100.0);
            const totalEmpMonthly = empMonthly + vpfMonthly;

            const epsMonthly = capEps
                ? Math.min(this.EPS_WAGE_CEILING_CAP, basicThisYear * 0.0833)
                : (basicThisYear * 0.0833);
            const erEpfMonthly = (basicThisYear * 0.12) - epsMonthly;

            const annualEmp = totalEmpMonthly * 12.0;
            const annualEr = erEpfMonthly * 12.0;
            const annualEps = epsMonthly * 12.0;

            if (annualEmp > this.TAX_FREE_ANNUAL_EMP_CAP) {
                exceeds250kCap = true;
            }

            let runningBalance = balance;
            let yearInterest = 0.0;

            for (let m = 1; m <= 12; m++) {
                runningBalance += totalEmpMonthly + erEpfMonthly;
                yearInterest += runningBalance * monthlyRate;
            }

            balance = runningBalance + yearInterest;
            cumEmployee += annualEmp;
            cumEmployer += annualEr;
            cumInterest += yearInterest;
            cumEps += annualEps;

            schedule.push({
                year: y,
                age: currAge + y,
                opening_balance: Math.round(openingBalance),
                employee_contribution: Math.round(annualEmp),
                employer_contribution: Math.round(annualEr),
                interest_earned: Math.round(yearInterest),
                closing_balance: Math.round(balance),
            });
        }

        const totalEmpWithInit = cumEmployee + initBalance;
        const totalInvested = totalEmpWithInit + cumEmployer;

        return {
            basic_salary: basic,
            current_age: currAge,
            retirement_age: retAge,
            tenure_years: tenureYears,
            vpf_percent: vpfPct,
            salary_hike: hikePct,
            interest_rate: ratePct,
            current_balance: initBalance,
            cap_eps: capEps,
            total_employee_contribution: Math.round(totalEmpWithInit),
            total_employer_contribution: Math.round(cumEmployer),
            total_invested: Math.round(totalInvested),
            total_interest: Math.round(cumInterest),
            maturity_corpus: Math.round(balance),
            total_eps_diverted: Math.round(cumEps),
            exceeds_250k_cap: exceeds250kCap,
            schedule,
        };
    }
}
