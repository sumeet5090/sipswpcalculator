import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { TaxHarvestEngine } from '../engines/TaxHarvestEngine.ts';
import type { YearResult } from '../../types';

export class TaxHarvestDriver implements ISpecializedDriver {
    public readonly mode = 'tax_harvest';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('tax_portfolio_value', 'tax_portfolio_value_range');
        ctx.sliderManager.sync('tax_invested_cost', 'tax_invested_cost_range');
        ctx.sliderManager.sync('tax_expected_return', 'tax_expected_return_range');
        ctx.sliderManager.sync('tax_holding_years', 'tax_holding_years_range');
        ctx.sliderManager.sync('tax_grandfather_fmv', 'tax_grandfather_fmv_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const cessToggle = ctx.dom.getElement<HTMLInputElement>('tax_include_cess');
        if (cessToggle) {
            cessToggle.addEventListener('change', () => ctx.recalculate());
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

        if (titleInvested) titleInvested.textContent = 'Buy & Hold LTCG Tax';
        if (subInvested) subInvested.textContent = 'Without Harvesting';
        if (titleInterest) titleInterest.textContent = 'Harvested LTCG Tax';
        if (subInterest) subInterest.textContent = 'After Annual ₹1.25L Reset';
        if (titleWithdrawn) titleWithdrawn.textContent = 'Cumulative Tax Saved';
        if (subWithdrawn) subWithdrawn.textContent = 'Section 112A Alpha';
        if (titleCorpus) titleCorpus.textContent = 'Net Post-Tax Wealth';
        if (subCorpus) subCorpus.textContent = 'Take-Home Corpus';
    }

    public calculate(ctx: DriverContext): void {
        const portfolioValue = Math.max(100000, parseFloat(ctx.dom.getValue('tax_portfolio_value') || '2500000') || 2500000);
        const investedCost = Math.max(50000, parseFloat(ctx.dom.getValue('tax_invested_cost') || '1500000') || 1500000);
        const expectedReturn = Math.max(4, Math.min(25, parseFloat(ctx.dom.getValue('tax_expected_return') || '12') || 12));
        const holdingYears = Math.max(1, Math.min(30, parseInt(ctx.dom.getValue('tax_holding_years') || '10', 10) || 10));
        const grandfatherFmv = Math.max(0, parseFloat(ctx.dom.getValue('tax_grandfather_fmv') || '0') || 0);
        const cessToggle = ctx.dom.getElement<HTMLInputElement>('tax_include_cess');
        const includeCess = cessToggle ? cessToggle.checked : true;

        const res = TaxHarvestEngine.calculate(
            portfolioValue,
            investedCost,
            expectedReturn,
            holdingYears,
            grandfatherFmv,
            includeCess
        );

        const combined: YearResult[] = res.schedule.map((item) => {
            return {
                year: item.year,
                begin_balance: res.effective_cost_basis,
                sip_monthly: null,
                annual_contribution: item.harvested_this_year,
                cumulative_invested: res.effective_cost_basis,
                interest: item.tax_saved,
                combined_total: item.net_harvested_wealth,
                ltcg_tax: item.harvested_exit_tax,
                post_tax_total: item.net_harvested_wealth,
            };
        });

        ctx.odometer.animateValue('summary-invested', res.buy_hold_tax);
        ctx.odometer.animateValue('summary-interest', res.harvested_tax);
        ctx.odometer.animateValue('summary-withdrawn', res.cumulative_tax_saved);
        ctx.odometer.animateValue('summary-corpus', res.net_post_tax_wealth);

        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        if (subWithdrawn) {
            subWithdrawn.textContent = `Mar–Apr Split: +${ctx.formatter.format(res.march_april_savings)}`;
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = `${res.effective_tax_rate_pct}% Effective LTCG`;
            gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const portfolioValue = Math.max(100000, parseFloat(ctx.dom.getValue('tax_portfolio_value') || '2500000') || 2500000);
        const investedCost = Math.max(50000, parseFloat(ctx.dom.getValue('tax_invested_cost') || '1500000') || 1500000);
        const expectedReturn = Math.max(4, Math.min(25, parseFloat(ctx.dom.getValue('tax_expected_return') || '12') || 12));
        const holdingYears = Math.max(1, Math.min(30, parseInt(ctx.dom.getValue('tax_holding_years') || '10', 10) || 10));
        const grandfatherFmv = Math.max(0, parseFloat(ctx.dom.getValue('tax_grandfather_fmv') || '0') || 0);
        const cessToggle = ctx.dom.getElement<HTMLInputElement>('tax_include_cess');
        const includeCess = cessToggle ? cessToggle.checked : true;

        const res = TaxHarvestEngine.calculate(
            portfolioValue,
            investedCost,
            expectedReturn,
            holdingYears,
            grandfatherFmv,
            includeCess
        );

        return {
            calc_type: 'TaxHarvest',
            portfolio_value: res.portfolio_value,
            cumulative_tax_saved: res.cumulative_tax_saved,
            march_april_savings: res.march_april_savings,
            goal_mode: 'tax_harvest',
        };
    }
}
