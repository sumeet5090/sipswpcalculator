import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { PostRetirementEngine } from '../engines/PostRetirementEngine.ts';
import type { YearResult } from '../../types';

export class PostRetirementDriver implements ISpecializedDriver {
    public readonly mode = 'post_retirement';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('post_ret_existing_corpus', 'post_ret_existing_corpus_range');
        ctx.sliderManager.sync('post_ret_gratuity', 'post_ret_gratuity_range');
        ctx.sliderManager.sync('post_ret_nps_lump_sum', 'post_ret_nps_lump_sum_range');
        ctx.sliderManager.sync('post_ret_pension', 'post_ret_pension_range');
        ctx.sliderManager.sync('post_ret_expense', 'post_ret_expense_range');
        ctx.sliderManager.sync('post_ret_return', 'post_ret_return_range');
        ctx.sliderManager.sync('post_ret_inflation', 'post_ret_inflation_range');
        ctx.sliderManager.sync('post_ret_age', 'post_ret_age_range');
    }

    public bindAdditionalControls(_ctx: DriverContext): void {
        // Sliders & inputs synchronize through sliderManager
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

        if (titleInvested) titleInvested.textContent = 'Total Initial Nest Egg';
        if (subInvested) subInvested.textContent = 'Savings + Gratuity + NPS';
        if (titleInterest) titleInterest.textContent = 'Guaranteed Monthly Pension';
        if (subInterest) subInterest.textContent = 'NPS Annuity + Fixed Income';
        if (titleWithdrawn) titleWithdrawn.textContent = 'Net Monthly SWP Drawdown';
        if (subWithdrawn) subWithdrawn.textContent = 'Deficit After Pension';
        if (titleCorpus) titleCorpus.textContent = 'Portfolio Longevity';
        if (subCorpus) subCorpus.textContent = 'Sustainable Horizon';
    }

    public calculate(ctx: DriverContext): void {
        const existing = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_existing_corpus') || '5000000') || 5000000);
        const gratuity = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_gratuity') || '1500000') || 1500000);
        const nps = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_nps_lump_sum') || '4000000') || 4000000);
        const pension = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_pension') || '25000') || 25000);
        const expense = Math.max(10000, parseFloat(ctx.dom.getValue('post_ret_expense') || '75000') || 75000);
        const returnRate = Math.max(4.0, Math.min(14.0, parseFloat(ctx.dom.getValue('post_ret_return') || '8.5') || 8.5));
        const inflation = Math.max(3.0, Math.min(10.0, parseFloat(ctx.dom.getValue('post_ret_inflation') || '6') || 6));
        const age = Math.max(40, Math.min(80, parseFloat(ctx.dom.getValue('post_ret_age') || '60') || 60));

        const res = PostRetirementEngine.calculate(existing, gratuity, nps, pension, expense, returnRate, inflation, age, 30);

        const combined: YearResult[] = res.schedule.map((item) => {
            return {
                year: item.year,
                begin_balance: item.opening_balance,
                sip_monthly: null,
                annual_contribution: item.annual_pension_received,
                cumulative_invested: res.total_initial_corpus,
                interest: item.growth_earned,
                combined_total: item.closing_balance,
                post_tax_total: item.closing_balance,
                swp_annual_payout: item.annual_swp_drawn,
                swp_tax: null,
                corpus: item.closing_balance,
            };
        });

        ctx.odometer.animateValue('summary-invested', res.total_initial_corpus);
        ctx.odometer.animateValue('summary-interest', pension);
        ctx.odometer.animateValue('summary-withdrawn', res.initial_monthly_swp);

        const subCorpus = document.getElementById('subtitle-corpus');
        const headlineCorpus = document.getElementById('final-corpus-headline') || document.getElementById('summary-corpus-headline');
        const exactCorpus = document.getElementById('summary-corpus');

        if (res.is_perpetual) {
            ctx.odometer.animateValue('summary-corpus', 90);
            if (headlineCorpus) headlineCorpus.textContent = 'Age 90+';
            if (exactCorpus) exactCorpus.textContent = 'Perpetual Lifespan';
            if (subCorpus) subCorpus.textContent = `SWR: ${res.initial_swr_percent}% (Sustainable)`;
        } else {
            ctx.odometer.animateValue('summary-corpus', res.depletion_age || 0);
            if (headlineCorpus) headlineCorpus.textContent = `Age ${res.depletion_age}`;
            if (exactCorpus) exactCorpus.textContent = `Depleted in Year ${res.depletion_year}`;
            if (subCorpus) subCorpus.textContent = `SWR: ${res.initial_swr_percent}% (Excess Deficit)`;
        }

        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        if (subWithdrawn) {
            subWithdrawn.textContent = `Year 1 Deficit • SWR: ${res.initial_swr_percent}%`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            if (res.is_perpetual) {
                gainBadge.textContent = '✓ 100% Fully Funded (Age 90+)';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
            } else {
                gainBadge.textContent = `⚠️ Depleted at Age ${res.depletion_age} (Yr ${res.depletion_year})`;
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200/80 tabular-nums';
            }
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const existing = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_existing_corpus') || '5000000') || 5000000);
        const gratuity = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_gratuity') || '1500000') || 1500000);
        const nps = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_nps_lump_sum') || '4000000') || 4000000);
        const pension = Math.max(0, parseFloat(ctx.dom.getValue('post_ret_pension') || '25000') || 25000);
        const expense = Math.max(10000, parseFloat(ctx.dom.getValue('post_ret_expense') || '75000') || 75000);
        const returnRate = Math.max(4.0, Math.min(14.0, parseFloat(ctx.dom.getValue('post_ret_return') || '8.5') || 8.5));
        const inflation = Math.max(3.0, Math.min(10.0, parseFloat(ctx.dom.getValue('post_ret_inflation') || '6') || 6));
        const age = Math.max(40, Math.min(80, parseFloat(ctx.dom.getValue('post_ret_age') || '60') || 60));

        const res = PostRetirementEngine.calculate(existing, gratuity, nps, pension, expense, returnRate, inflation, age, 30);

        return {
            calc_type: 'PostRetirement',
            total_initial_corpus: res.total_initial_corpus,
            initial_monthly_swp: res.initial_monthly_swp,
            initial_swr_percent: res.initial_swr_percent,
            is_perpetual: res.is_perpetual,
            depletion_age: res.depletion_age,
            goal_mode: 'post_retirement',
        };
    }
}
