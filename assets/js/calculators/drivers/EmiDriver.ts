import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { EmiEngine } from '../engines/EmiEngine.ts';
import type { YearResult } from '../../types';

export class EmiDriver implements ISpecializedDriver {
    public readonly mode = 'emi';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('emi_principal', 'emi_principal_range');
        ctx.sliderManager.sync('emi_rate', 'emi_rate_range');
        ctx.sliderManager.sync('emi_years', 'emi_years_range');
    }

    public setupCardLabels(): void {
        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        if (titleInvested) titleInvested.textContent = 'Loan Amount';
        if (subInvested) subInvested.textContent = 'Principal Borrowed';
        if (titleInterest) titleInterest.textContent = 'Total Interest';
        if (titleCorpus) titleCorpus.textContent = 'Monthly EMI';
    }

    public calculate(ctx: DriverContext): void {
        const principal = Math.max(1000, parseFloat(ctx.dom.getValue('emi_principal') || '3000000') || 3000000);
        const rate = Math.max(0.1, parseFloat(ctx.dom.getValue('emi_rate') || '8.5') || 8.5);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('emi_years') || '20') || 20);

        const res = EmiEngine.calculate(principal, rate, years);

        let cumulativePrincipalPaid = 0;
        const combined: YearResult[] = res.schedule.map(item => {
            cumulativePrincipalPaid += item.principal_paid;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: Math.round(res.monthly_emi),
                annual_contribution: item.principal_paid,
                cumulative_invested: Math.round(cumulativePrincipalPaid),
                interest: item.interest_paid,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance
            };
        });

        ctx.odometer.animateValue('summary-invested', principal);
        ctx.odometer.animateValue('summary-interest', res.total_interest);

        const corpusEl = ctx.dom.getElement('summary-corpus');
        if (corpusEl) {
            corpusEl.textContent = `${ctx.formatter.format(Math.round(res.monthly_emi))} / mo`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `${res.interest_ratio_percentage.toFixed(1)}% of Loan`;
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }
}
