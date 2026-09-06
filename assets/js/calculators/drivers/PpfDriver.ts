import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { PpfEngine } from '../engines/PpfEngine.ts';
import type { YearResult } from '../../types';

export class PpfDriver implements ISpecializedDriver {
    public readonly mode = 'ppf';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('ppf_deposit', 'ppf_deposit_range');
        ctx.sliderManager.sync('ppf_rate', 'ppf_rate_range');
        ctx.sliderManager.sync('ppf_years', 'ppf_years_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const ppfTiming = ctx.dom.getElement('ppf_timing');
        if (ppfTiming) {
            ppfTiming.addEventListener('change', () => ctx.recalculate());
        }
    }

    public setupCardLabels(): void {
        const titleInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) span:nth-child(2)');
        const subInvested = document.querySelector('#summary-cards-grid > div:nth-child(1) div:last-child');
        const titleInterest = document.querySelector('#title-interest span:nth-child(2)');
        const titleCorpus = document.querySelector('#title-corpus span:nth-child(2)');

        if (titleInvested) titleInvested.textContent = 'Total PPF Deposit';
        if (subInvested) subInvested.textContent = 'Statutory Contributions';
        if (titleInterest) titleInterest.textContent = 'Tax-Free Interest';
        if (titleCorpus) titleCorpus.textContent = 'Maturity Corpus';
    }

    public calculate(ctx: DriverContext): void {
        const deposit = Math.max(500, parseFloat(ctx.dom.getValue('ppf_deposit') || '150000') || 150000);
        const rate = Math.max(1, parseFloat(ctx.dom.getValue('ppf_rate') || '7.1') || 7.1);
        const years = Math.max(15, parseFloat(ctx.dom.getValue('ppf_years') || '15') || 15);
        const timingSelect = ctx.dom.getElement<HTMLSelectElement>('ppf_timing');
        const timing = timingSelect?.value === 'monthly' ? 'monthly' : 'beginning';

        const res = PpfEngine.calculate(deposit, rate, years, timing);

        let cumulativeDeposit = 0;
        const combined: YearResult[] = res.schedule.map(item => {
            cumulativeDeposit += item.annual_deposit;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: null,
                annual_contribution: item.annual_deposit,
                cumulative_invested: cumulativeDeposit,
                interest: item.interest_earned,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance
            };
        });

        ctx.odometer.animateValue('summary-invested', res.total_invested);
        ctx.odometer.animateValue('summary-interest', res.total_interest);
        ctx.odometer.animateValue('summary-corpus', res.maturity_amount);

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = '100% Tax-Free (EEE)';
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }
}
