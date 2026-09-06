import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { CompoundInterestEngine } from '../engines/CompoundInterestEngine.ts';
import type { YearResult } from '../../types';

export class CompoundInterestDriver implements ISpecializedDriver {
    public readonly mode = 'compound_interest';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('ci_principal', 'ci_principal_range');
        ctx.sliderManager.sync('ci_rate', 'ci_rate_range');
        ctx.sliderManager.sync('ci_years', 'ci_years_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const ciFreq = ctx.dom.getElement('ci_frequency');
        if (ciFreq) {
            ciFreq.addEventListener('change', () => ctx.recalculate());
        }
    }

    public setupCardLabels(): void {
        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        if (titleInvested) titleInvested.textContent = 'Principal Invested';
        if (subInvested) subInvested.textContent = 'Starting Capital';
        if (titleInterest) titleInterest.textContent = 'Compound Interest';
        if (titleCorpus) titleCorpus.textContent = 'Maturity Value';
    }

    public calculate(ctx: DriverContext): void {
        const principal = Math.max(0, parseFloat(ctx.dom.getValue('ci_principal') || '500000') || 500000);
        const rate = Math.max(0, parseFloat(ctx.dom.getValue('ci_rate') || '12') || 12);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('ci_years') || '10') || 10);
        const freqSelect = ctx.dom.getElement<HTMLSelectElement>('ci_frequency');
        const frequency = freqSelect ? parseInt(freqSelect.value, 10) || 12 : 12;

        const res = CompoundInterestEngine.calculate(principal, rate, years, frequency);

        const combined: YearResult[] = res.schedule.map(item => ({
            year: item.year,
            begin_balance: item.opening_balance,
            sip_monthly: null,
            annual_contribution: item.year === 1 ? principal : 0,
            cumulative_invested: principal,
            interest: item.interest_earned,
            combined_total: item.closing_balance,
            post_tax_total: item.closing_balance
        }));

        ctx.odometer.animateValue('summary-invested', principal);
        ctx.odometer.animateValue('summary-interest', res.total_interest);
        ctx.odometer.animateValue('summary-corpus', res.final_amount);

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            const gainPct = principal > 0 ? Math.round((res.total_interest / principal) * 100) : 0;
            gainBadge.textContent = `+${gainPct}% (EAR: ${res.effective_annual_rate}%)`;
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }
}
