import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { FdEngine } from '../engines/FdEngine.ts';
import type { YearResult } from '../../types';

export class FdDriver implements ISpecializedDriver {
    public readonly mode = 'fd';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('fd_principal', 'fd_principal_range');
        ctx.sliderManager.sync('fd_rate', 'fd_rate_range');
        ctx.sliderManager.sync('fd_years', 'fd_years_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const fdSenior = ctx.dom.getElement('fd_senior');
        if (fdSenior) {
            fdSenior.addEventListener('change', () => ctx.recalculate());
        }

        const fdFreq = ctx.dom.getElement('fd_frequency');
        if (fdFreq) {
            fdFreq.addEventListener('change', () => ctx.recalculate());
        }
    }

    public setupCardLabels(): void {
        const titleInvested = document.getElementById('title-invested');
        const subInvested = document.getElementById('subtitle-invested');
        const titleInterest = document.getElementById('title-interest');
        const titleCorpus = document.getElementById('title-corpus-text');

        if (titleInvested) titleInvested.textContent = 'Deposit Amount';
        if (subInvested) subInvested.textContent = 'Bank FD Principal';
        if (titleInterest) titleInterest.textContent = 'Interest Earned';
        if (titleCorpus) titleCorpus.textContent = 'Maturity Value';
    }

    public calculate(ctx: DriverContext): void {
        const principal = Math.max(1000, parseFloat(ctx.dom.getValue('fd_principal') || '500000') || 500000);
        const rate = Math.max(0.1, parseFloat(ctx.dom.getValue('fd_rate') || '7.0') || 7.0);
        const years = Math.max(0.25, parseFloat(ctx.dom.getValue('fd_years') || '3.0') || 3.0);
        const seniorCheck = ctx.dom.getElement<HTMLInputElement>('fd_senior');
        const isSenior = seniorCheck ? seniorCheck.checked : false;
        const freqSelect = ctx.dom.getElement<HTMLSelectElement>('fd_frequency');
        const frequency = (freqSelect?.value || 'cumulative') as 'cumulative' | 'monthly' | 'quarterly' | 'annual';

        const res = FdEngine.calculate(principal, rate, years, isSenior, frequency);

        let cumulativePayout = 0;
        const combined: YearResult[] = res.yearly_schedule.map(item => {
            cumulativePayout += item.payout_withdrawn;
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: null,
                annual_contribution: item.year === 1 ? principal : 0,
                cumulative_invested: principal,
                interest: item.interest_earned,
                annual_withdrawal: item.payout_withdrawn,
                cumulative_withdrawals: Math.round(cumulativePayout),
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance
            };
        });

        ctx.odometer.animateValue('summary-invested', principal);
        ctx.odometer.animateValue('summary-interest', res.total_interest);

        const finalVal = frequency === 'cumulative' ? res.maturity_amount : res.periodic_payout;
        ctx.odometer.animateValue('summary-corpus', finalVal);

        const titleCorpus = document.getElementById('title-corpus-text');
        if (titleCorpus) {
            titleCorpus.textContent = frequency === 'cumulative' ? 'Maturity Amount' : 'Periodic Payout';
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = isSenior ? '+0.5% Senior Card' : `${res.effective_rate}% Card Rate`;
        }

        const hasWithdrawals = cumulativePayout > 0;
        ctx.resultsController.updateTable(combined, hasWithdrawals);
        ctx.chartManager.updateChart(combined, hasWithdrawals);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const principal = Math.max(1000, parseFloat(ctx.dom.getValue('fd_principal') || '500000') || 500000);
        const rate = Math.max(0.1, parseFloat(ctx.dom.getValue('fd_rate') || '7.0') || 7.0);
        const years = Math.max(0.25, parseFloat(ctx.dom.getValue('fd_years') || '3.0') || 3.0);
        const seniorCheck = ctx.dom.getElement<HTMLInputElement>('fd_senior');
        const isSenior = seniorCheck ? seniorCheck.checked : false;
        const freqSelect = ctx.dom.getElement<HTMLSelectElement>('fd_frequency');
        const frequency = (freqSelect?.value || 'cumulative') as 'cumulative' | 'monthly' | 'quarterly' | 'annual';
        const res = FdEngine.calculate(principal, rate, years, isSenior, frequency);
        const wealthMultiplier = principal > 0 ? parseFloat((res.maturity_amount / principal).toFixed(2)) : 1.0;

        return {
            calc_type: 'Fixed Deposit',
            amount: principal,
            duration: Math.max(1, Math.round(years)),
            interest_rate: isSenior ? rate + 0.5 : rate,
            total_invested: principal,
            final_corpus: res.maturity_amount,
            wealth_multiplier: wealthMultiplier,
            goal_mode: 'fd'
        };
    }
}
