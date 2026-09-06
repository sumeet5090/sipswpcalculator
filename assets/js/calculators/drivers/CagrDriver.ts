import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { CagrEngine } from '../engines/CagrEngine.ts';
import type { YearResult } from '../../types';

export class CagrDriver implements ISpecializedDriver {
    public readonly mode = 'cagr';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('cagr_initial', 'cagr_initial_range');
        ctx.sliderManager.sync('cagr_final', 'cagr_final_range');
        ctx.sliderManager.sync('cagr_years', 'cagr_years_range');
    }

    public setupCardLabels(): void {
        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        if (titleInvested) titleInvested.textContent = 'Initial Value';
        if (subInvested) subInvested.textContent = 'Starting Investment';
        if (titleInterest) titleInterest.textContent = 'Absolute Gain';
        if (titleCorpus) titleCorpus.textContent = 'CAGR Return';
    }

    public calculate(ctx: DriverContext): void {
        const initial = Math.max(1, parseFloat(ctx.dom.getValue('cagr_initial') || '100000') || 100000);
        const finalVal = Math.max(1, parseFloat(ctx.dom.getValue('cagr_final') || '250000') || 250000);
        const years = Math.max(0.1, parseFloat(ctx.dom.getValue('cagr_years') || '5') || 5);

        const res = CagrEngine.calculate(initial, finalVal, years);

        const combined: YearResult[] = [];
        const fullYears = Math.max(1, Math.ceil(years));
        const annualGrowthRate = res.cagr_percentage / 100.0;
        let prevVal = initial;
        for (let yr = 1; yr <= fullYears; yr++) {
            const tFrac = Math.min(yr, years);
            const currentVal = Math.round(initial * Math.pow(1.0 + annualGrowthRate, tFrac));
            combined.push({
                year: yr,
                begin_balance: prevVal,
                sip_monthly: null,
                annual_contribution: yr === 1 ? initial : 0,
                cumulative_invested: initial,
                interest: currentVal - prevVal,
                combined_total: currentVal,
                post_tax_total: currentVal
            });
            prevVal = currentVal;
        }

        ctx.odometer.animateValue('summary-invested', initial);
        ctx.odometer.animateValue('summary-interest', res.total_gain);

        const corpusEl = ctx.dom.getElement('summary-corpus');
        if (corpusEl) {
            corpusEl.textContent = `${res.cagr_percentage.toFixed(2)}% p.a.`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `+${res.absolute_return_percentage.toFixed(1)}% (${res.multiplier.toFixed(2)}x)`;
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }
}
