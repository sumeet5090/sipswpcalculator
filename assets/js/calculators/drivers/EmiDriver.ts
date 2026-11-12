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
        const titleInvested = document.getElementById('title-invested');
        const subInvested = document.getElementById('subtitle-invested');
        const titleInterest = document.getElementById('title-interest');
        const titleCorpus = document.getElementById('title-corpus-text');

        if (titleInvested) titleInvested.textContent = 'Loan Amount';
        if (subInvested) subInvested.textContent = 'Principal Borrowed';
        if (titleInterest) titleInterest.textContent = 'Total Interest';
        if (titleCorpus) titleCorpus.textContent = 'Monthly EMI';

        const cardWithdrawn = document.getElementById('card-withdrawn');
        const summaryGrid = document.getElementById('summary-cards-grid');
        const cardCorpus = document.getElementById('card-corpus');
        if (cardWithdrawn && summaryGrid) {
            cardWithdrawn.classList.remove('hidden');
            summaryGrid.className = 'grid grid-cols-2 xl:grid-cols-4 gap-2 sm:gap-2.5 transition-all duration-300';
            if (cardCorpus) {
                cardCorpus.classList.remove('col-span-2', 'sm:col-span-1', 'xl:col-span-1', 'order-first');
                cardCorpus.classList.add('col-span-1');
            }
            const titleWithdrawn = document.getElementById('title-withdrawn');
            const subWithdrawn = document.getElementById('subtitle-withdrawn');
            if (titleWithdrawn) titleWithdrawn.textContent = 'Total Payment';
            if (subWithdrawn) subWithdrawn.textContent = 'Principal + Interest';
        }
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

        const totalPayment = principal + res.total_interest;
        ctx.odometer.animateValue('summary-invested', principal);
        ctx.odometer.animateValue('summary-interest', res.total_interest);
        ctx.odometer.animateValue('summary-withdrawn', totalPayment);

        const corpusEl = ctx.dom.getElement('summary-corpus');
        if (corpusEl) {
            corpusEl.textContent = `${ctx.formatter.format(Math.round(res.monthly_emi))} / mo`;
        }
        const headlineEl = ctx.dom.getElement('summary-corpus-headline');
        if (headlineEl) {
            headlineEl.textContent = `${ctx.formatter.formatCompact(Math.round(res.monthly_emi))} / mo`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `${res.interest_ratio_percentage.toFixed(1)}% of Loan`;
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const principal = Math.max(1000, parseFloat(ctx.dom.getValue('emi_principal') || '3000000') || 3000000);
        const rate = Math.max(0.1, parseFloat(ctx.dom.getValue('emi_rate') || '8.5') || 8.5);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('emi_years') || '20') || 20);
        const res = EmiEngine.calculate(principal, rate, years);

        return {
            calc_type: 'EMI',
            amount: principal,
            duration: Math.round(years),
            interest_rate: rate,
            sip_amount: Math.round(res.monthly_emi),
            total_invested: principal,
            final_corpus: principal + res.total_interest,
            wealth_multiplier: principal > 0 ? parseFloat(((principal + res.total_interest) / principal).toFixed(2)) : 1.0,
            goal_mode: 'emi'
        };
    }
}
