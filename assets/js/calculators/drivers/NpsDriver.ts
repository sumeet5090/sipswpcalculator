import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { NpsEngine } from '../engines/NpsEngine.ts';
import type { YearResult } from '../../types';

export class NpsDriver implements ISpecializedDriver {
    public readonly mode = 'nps';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('nps_monthly_contribution', 'nps_monthly_contribution_range');
        ctx.sliderManager.sync('nps_current_age', 'nps_current_age_range');
        ctx.sliderManager.sync('nps_retirement_age', 'nps_retirement_age_range');
        ctx.sliderManager.sync('nps_expected_return', 'nps_expected_return_range');
        ctx.sliderManager.sync('nps_annuity_percent', 'nps_annuity_percent_range');
        ctx.sliderManager.sync('nps_annuity_rate', 'nps_annuity_rate_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        // Enforce age constraint: retirement_age must be > current_age
        const currentAgeInput = ctx.dom.getElement<HTMLInputElement>('nps_current_age');
        const currentAgeRange = ctx.dom.getElement<HTMLInputElement>('nps_current_age_range');
        const retAgeInput = ctx.dom.getElement<HTMLInputElement>('nps_retirement_age');
        const retAgeRange = ctx.dom.getElement<HTMLInputElement>('nps_retirement_age_range');

        const enforceAgeBoundaries = () => {
            const currAge = Math.max(18, Math.min(65, parseFloat(currentAgeInput?.value || '30') || 30));
            const minRetAge = currAge + 1;

            if (retAgeInput && parseFloat(retAgeInput.value) < minRetAge) {
                retAgeInput.value = minRetAge.toString();
            }
            if (retAgeRange && parseFloat(retAgeRange.value) < minRetAge) {
                retAgeRange.value = minRetAge.toString();
            }
            if (retAgeRange) {
                retAgeRange.min = minRetAge.toString();
            }
            if (retAgeInput) {
                retAgeInput.min = minRetAge.toString();
            }
        };

        if (currentAgeInput) {
            currentAgeInput.addEventListener('input', () => {
                enforceAgeBoundaries();
                ctx.recalculate();
            });
        }
        if (currentAgeRange) {
            currentAgeRange.addEventListener('input', () => {
                enforceAgeBoundaries();
                ctx.recalculate();
            });
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

        if (titleInvested) titleInvested.textContent = 'Total Principal Invested';
        if (subInvested) subInvested.textContent = 'Tier-1 Contributions';
        if (titleInterest) titleInterest.textContent = 'Total Compounding Gains';
        if (subInterest) subInterest.textContent = 'PFRDA Accumulation';
        if (titleWithdrawn) titleWithdrawn.textContent = 'Tax-Free Lump Sum (60%)';
        if (subWithdrawn) subWithdrawn.textContent = 'Sec 10(12A) Exempt';
        if (titleCorpus) titleCorpus.textContent = 'Estimated Monthly Pension';
        if (subCorpus) subCorpus.textContent = 'Annuity Cashflow';
    }

    public calculate(ctx: DriverContext): void {
        const contribution = Math.max(500, parseFloat(ctx.dom.getValue('nps_monthly_contribution') || '5000') || 5000);
        const currentAge = Math.max(18, Math.min(65, parseFloat(ctx.dom.getValue('nps_current_age') || '30') || 30));
        const retirementAge = Math.max(currentAge + 1, Math.min(75, parseFloat(ctx.dom.getValue('nps_retirement_age') || '60') || 60));
        const returnRate = Math.max(5.0, Math.min(15.0, parseFloat(ctx.dom.getValue('nps_expected_return') || '10') || 10));
        const annuityPercent = Math.max(40.0, Math.min(100.0, parseFloat(ctx.dom.getValue('nps_annuity_percent') || '40') || 40));
        const annuityRate = Math.max(4.0, Math.min(10.0, parseFloat(ctx.dom.getValue('nps_annuity_rate') || '6') || 6));

        const res = NpsEngine.calculate(contribution, currentAge, retirementAge, returnRate, annuityPercent, annuityRate);

        const combined: YearResult[] = res.schedule.map((item, idx) => {
            return {
                year: item.year,
                begin_balance: idx > 0 ? res.schedule[idx - 1].balance : 0,
                sip_monthly: res.monthly_contribution,
                annual_contribution: res.monthly_contribution * 12,
                cumulative_invested: item.invested,
                interest: item.interest,
                combined_total: item.balance,
                post_tax_total: item.lump_sum_share + item.annuity_share,
                swp_annual_payout: null,
                swp_tax: null,
                corpus: item.balance,
            };
        });

        ctx.odometer.animateValue('summary-invested', res.total_invested);
        ctx.odometer.animateValue('summary-interest', res.total_interest);
        ctx.odometer.animateValue('summary-withdrawn', res.lump_sum_amount);
        ctx.odometer.animateValue('summary-corpus', res.monthly_pension);

        const subCorpus = document.getElementById('subtitle-corpus');
        if (subCorpus) {
            subCorpus.textContent = `@ ${res.annuity_return_rate.toFixed(1)}% Annuity Rate`;
        }

        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        if (subWithdrawn) {
            const lumpPct = Math.round(100 - res.annuity_percent);
            subWithdrawn.textContent = `${lumpPct}% Corpus • Sec 10(12A)`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            if (res.is_small_corpus_eligible) {
                gainBadge.textContent = '100% Lump Sum Eligible (≤₹5L)';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 tabular-nums';
            } else {
                gainBadge.textContent = '✓ 60% Tax-Free Lump Sum';
                gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
            }
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const contribution = Math.max(500, parseFloat(ctx.dom.getValue('nps_monthly_contribution') || '5000') || 5000);
        const currentAge = Math.max(18, Math.min(65, parseFloat(ctx.dom.getValue('nps_current_age') || '30') || 30));
        const retirementAge = Math.max(currentAge + 1, Math.min(75, parseFloat(ctx.dom.getValue('nps_retirement_age') || '60') || 60));
        const returnRate = Math.max(5.0, Math.min(15.0, parseFloat(ctx.dom.getValue('nps_expected_return') || '10') || 10));
        const annuityPercent = Math.max(40.0, Math.min(100.0, parseFloat(ctx.dom.getValue('nps_annuity_percent') || '40') || 40));
        const annuityRate = Math.max(4.0, Math.min(10.0, parseFloat(ctx.dom.getValue('nps_annuity_rate') || '6') || 6));

        const res = NpsEngine.calculate(contribution, currentAge, retirementAge, returnRate, annuityPercent, annuityRate);

        return {
            calc_type: 'NPS',
            amount: contribution,
            duration: res.tenure_years,
            interest_rate: returnRate,
            total_invested: res.total_invested,
            final_corpus: res.total_corpus,
            lump_sum_amount: res.lump_sum_amount,
            monthly_pension: res.monthly_pension,
            annuity_percent: annuityPercent,
            goal_mode: 'nps',
        };
    }
}
