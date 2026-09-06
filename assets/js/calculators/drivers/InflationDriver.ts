import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { InflationEngine } from '../engines/InflationEngine.ts';
import type { YearResult } from '../../types';

export class InflationDriver implements ISpecializedDriver {
    public readonly mode = 'inflation';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('inf_amount', 'inf_amount_range');
        ctx.sliderManager.sync('inf_rate', 'inf_rate_range');
        ctx.sliderManager.sync('inf_years', 'inf_years_range');
    }

    public setupCardLabels(): void {
        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        if (titleInvested) titleInvested.textContent = "Today's Cost";
        if (subInvested) subInvested.textContent = 'Current Living Expense';
        if (titleInterest) titleInterest.textContent = 'Cost Escalation';
        if (titleCorpus) titleCorpus.textContent = 'Future Living Cost';
    }

    public calculate(ctx: DriverContext): void {
        const amount = Math.max(1, parseFloat(ctx.dom.getValue('inf_amount') || '50000') || 50000);
        const rate = Math.max(0, parseFloat(ctx.dom.getValue('inf_rate') || '6') || 6);
        const years = Math.max(1, parseFloat(ctx.dom.getValue('inf_years') || '15') || 15);

        const res = InflationEngine.calculate(amount, rate, years);

        let prevCost = amount;
        const combined: YearResult[] = res.schedule.map(item => {
            const interestStep = item.future_cost - prevCost;
            prevCost = item.future_cost;
            return {
                year: item.year,
                begin_balance: item.future_cost - interestStep,
                sip_monthly: null,
                annual_contribution: amount,
                cumulative_invested: amount,
                interest: interestStep,
                combined_total: item.future_cost,
                post_tax_total: item.purchasing_power
            };
        });

        ctx.odometer.animateValue('summary-invested', amount);
        ctx.odometer.animateValue('summary-interest', res.cost_increase);
        ctx.odometer.animateValue('summary-corpus', res.future_cost);

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `-${res.purchasing_power_loss_percentage.toFixed(1)}% Value`;
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }
}
