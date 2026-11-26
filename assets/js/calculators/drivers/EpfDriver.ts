import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { EpfEngine } from '../engines/EpfEngine.ts';
import type { YearResult } from '../../types';

export class EpfDriver implements ISpecializedDriver {
    public readonly mode = 'epf';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('epf_basic_salary', 'epf_basic_salary_range');
        ctx.sliderManager.sync('epf_current_age', 'epf_current_age_range');
        ctx.sliderManager.sync('epf_retirement_age', 'epf_retirement_age_range');
        ctx.sliderManager.sync('epf_vpf_percent', 'epf_vpf_percent_range');
        ctx.sliderManager.sync('epf_salary_hike', 'epf_salary_hike_range');
        ctx.sliderManager.sync('epf_interest_rate', 'epf_interest_rate_range');
        ctx.sliderManager.sync('epf_current_balance', 'epf_current_balance_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const capEpsToggle = ctx.dom.getElement<HTMLInputElement>('epf_cap_eps');
        if (capEpsToggle) {
            capEpsToggle.addEventListener('change', () => ctx.recalculate());
        }
    }

    public setupCardLabels(): void {
        const titleInvested = document.getElementById('title-invested');
        const subInvested = document.getElementById('subtitle-invested');
        const titleInterest = document.getElementById('title-interest');
        const subInterest = document.getElementById('subtitle-interest');
        const titleWithdrawn = document.getElementById('title-withdrawn');
        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        const titleCorpus = document.getElementById('title-corpus-text');
        const subCorpus = document.getElementById('subtitle-corpus');

        if (titleInvested) titleInvested.textContent = 'Employee + VPF';
        if (subInvested) subInvested.textContent = 'Your Contributions';
        if (titleInterest) titleInterest.textContent = 'Employer EPF Match';
        if (subInterest) subInterest.textContent = 'After EPS Diversion';
        if (titleWithdrawn) titleWithdrawn.textContent = 'EPFO Interest';
        if (subWithdrawn) subWithdrawn.textContent = '8.25% Compounded Annually';
        if (titleCorpus) titleCorpus.textContent = 'EPF Maturity Corpus';
        if (subCorpus) subCorpus.textContent = 'Retirement Lump Sum';
    }

    public calculate(ctx: DriverContext): void {
        const basicSalary = Math.max(5000, parseFloat(ctx.dom.getValue('epf_basic_salary') || '50000') || 50000);
        const currentAge = Math.max(18, Math.min(57, parseInt(ctx.dom.getValue('epf_current_age') || '28', 10) || 28));
        let retirementAge = Math.max(currentAge + 1, Math.min(60, parseInt(ctx.dom.getValue('epf_retirement_age') || '58', 10) || 58));

        const retInput = ctx.dom.getElement<HTMLInputElement>('epf_retirement_age');
        if (retInput && parseInt(retInput.value, 10) <= currentAge) {
            retirementAge = Math.min(60, currentAge + 1);
            retInput.value = String(retirementAge);
        }

        const vpfPercent = Math.max(0, Math.min(50, parseFloat(ctx.dom.getValue('epf_vpf_percent') || '0') || 0));
        const salaryHike = Math.max(0, Math.min(20, parseFloat(ctx.dom.getValue('epf_salary_hike') || '7') || 7));
        const interestRate = Math.max(6, Math.min(12, parseFloat(ctx.dom.getValue('epf_interest_rate') || '8.25') || 8.25));
        const currentBalance = Math.max(0, parseFloat(ctx.dom.getValue('epf_current_balance') || '0') || 0);

        const capEpsToggle = ctx.dom.getElement<HTMLInputElement>('epf_cap_eps');
        const capEps = capEpsToggle ? capEpsToggle.checked : true;

        const res = EpfEngine.calculate(
            basicSalary,
            currentAge,
            retirementAge,
            vpfPercent,
            salaryHike,
            interestRate,
            currentBalance,
            capEps
        );

        let cumInvested = currentBalance;
        const combined: YearResult[] = res.schedule.map((item) => {
            const annualTotal = item.employee_contribution + item.employer_contribution;
            cumInvested += annualTotal;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: Math.round(annualTotal / 12),
                annual_contribution: annualTotal,
                cumulative_invested: cumInvested,
                interest: item.interest_earned,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance,
            };
        });

        ctx.odometer.animateValue('summary-invested', res.total_employee_contribution);
        ctx.odometer.animateValue('summary-interest', res.total_employer_contribution);
        ctx.odometer.animateValue('summary-withdrawn', res.total_interest);
        ctx.odometer.animateValue('summary-corpus', res.maturity_corpus);

        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        if (subWithdrawn) {
            subWithdrawn.textContent = `${res.interest_rate}% p.a. (${res.tenure_years} Yrs)`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            if (res.exceeds_250k_cap) {
                gainBadge.textContent = '⚠️ Exceeds ₹2.5L Tax-Free Cap';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200/80 tabular-nums';
            } else {
                gainBadge.textContent = '✓ 100% Tax-Free (EEE)';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
            }
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const basicSalary = Math.max(5000, parseFloat(ctx.dom.getValue('epf_basic_salary') || '50000') || 50000);
        const currentAge = Math.max(18, Math.min(57, parseInt(ctx.dom.getValue('epf_current_age') || '28', 10) || 28));
        const retirementAge = Math.max(currentAge + 1, Math.min(60, parseInt(ctx.dom.getValue('epf_retirement_age') || '58', 10) || 58));
        const vpfPercent = Math.max(0, Math.min(50, parseFloat(ctx.dom.getValue('epf_vpf_percent') || '0') || 0));
        const salaryHike = Math.max(0, Math.min(20, parseFloat(ctx.dom.getValue('epf_salary_hike') || '7') || 7));
        const interestRate = Math.max(6, Math.min(12, parseFloat(ctx.dom.getValue('epf_interest_rate') || '8.25') || 8.25));
        const currentBalance = Math.max(0, parseFloat(ctx.dom.getValue('epf_current_balance') || '0') || 0);
        const capEpsToggle = ctx.dom.getElement<HTMLInputElement>('epf_cap_eps');
        const capEps = capEpsToggle ? capEpsToggle.checked : true;

        const res = EpfEngine.calculate(
            basicSalary,
            currentAge,
            retirementAge,
            vpfPercent,
            salaryHike,
            interestRate,
            currentBalance,
            capEps
        );

        return {
            calc_type: 'EPF',
            basic_salary: res.basic_salary,
            tenure_years: res.tenure_years,
            vpf_percent: res.vpf_percent,
            maturity_corpus: res.maturity_corpus,
            exceeds_250k_cap: res.exceeds_250k_cap,
            goal_mode: 'epf',
        };
    }
}
