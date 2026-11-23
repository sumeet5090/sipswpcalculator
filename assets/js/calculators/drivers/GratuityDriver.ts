import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { GratuityEngine } from '../engines/GratuityEngine.ts';
import type { YearResult } from '../../types';

export class GratuityDriver implements ISpecializedDriver {
    public readonly mode = 'gratuity';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('gratuity_salary', 'gratuity_salary_range');
        ctx.sliderManager.sync('gratuity_years', 'gratuity_years_range');
        ctx.sliderManager.sync('gratuity_months', 'gratuity_months_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const coveredRadios = ctx.dom.getElements<HTMLInputElement>('input[name="gratuity_covered"]');
        coveredRadios.forEach(radio => {
            radio.addEventListener('change', () => ctx.recalculate());
        });
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

        if (titleInvested) titleInvested.textContent = 'Last Drawn Basic + DA';
        if (subInvested) subInvested.textContent = 'Monthly Base Pay';
        if (titleInterest) titleInterest.textContent = 'Tax-Exempt Gratuity';
        if (subInterest) subInterest.textContent = 'Sec 10(10) Cap: ₹20L';
        if (titleWithdrawn) titleWithdrawn.textContent = 'Taxable Gratuity';
        if (subWithdrawn) subWithdrawn.textContent = 'Taxed at Slab Rate';
        if (titleCorpus) titleCorpus.textContent = 'Total Gratuity Payable';
        if (subCorpus) subCorpus.textContent = 'Statutory Settlement';
    }

    public calculate(ctx: DriverContext): void {
        const salary = Math.max(1000, parseFloat(ctx.dom.getValue('gratuity_salary') || '50000') || 50000);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('gratuity_years') || '5') || 5);
        const months = Math.max(0, parseFloat(ctx.dom.getValue('gratuity_months') || '0') || 0);

        const coveredRadio = document.querySelector<HTMLInputElement>('input[name="gratuity_covered"]:checked');
        const isCovered = coveredRadio ? coveredRadio.value === '1' : true;

        const res = GratuityEngine.calculate(salary, years, months, isCovered);

        const combined: YearResult[] = res.schedule.map(item => {
            return {
                year: item.year,
                begin_balance: item.year > 1 ? res.schedule[item.year - 2].accrued_amount : 0,
                sip_monthly: null,
                annual_contribution: 0,
                cumulative_invested: res.monthly_salary,
                interest: item.accrued_amount,
                combined_total: item.accrued_amount,
                post_tax_total: item.tax_exempt + (item.taxable * 0.70)
            };
        });

        ctx.odometer.animateValue('summary-invested', res.monthly_salary);
        ctx.odometer.animateValue('summary-interest', res.tax_exempt_amount);
        ctx.odometer.animateValue('summary-withdrawn', res.taxable_amount);
        ctx.odometer.animateValue('summary-corpus', res.gratuity_amount);

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            if (!res.is_eligible) {
                gainBadge.textContent = '⚠️ Not Vested (<5 Yrs)';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200/80 tabular-nums';
            } else {
                gainBadge.textContent = '✓ Statutory Vested';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
            }
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const salary = Math.max(1000, parseFloat(ctx.dom.getValue('gratuity_salary') || '50000') || 50000);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('gratuity_years') || '5') || 5);
        const months = Math.max(0, parseFloat(ctx.dom.getValue('gratuity_months') || '0') || 0);
        const coveredRadio = document.querySelector<HTMLInputElement>('input[name="gratuity_covered"]:checked');
        const isCovered = coveredRadio ? coveredRadio.value === '1' : true;
        const res = GratuityEngine.calculate(salary, years, months, isCovered);

        return {
            calc_type: 'Gratuity',
            amount: salary,
            duration: res.effective_years,
            interest_rate: 0,
            total_invested: salary,
            future_value: res.gratuity_amount,
            is_eligible: res.is_eligible,
            tax_exempt: res.tax_exempt_amount,
            taxable: res.taxable_amount
        };
    }
}
