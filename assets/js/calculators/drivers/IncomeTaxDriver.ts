import type { ISpecializedDriver, DriverContext } from './ISpecializedDriver.ts';
import { IncomeTaxEngine } from '../engines/IncomeTaxEngine.ts';
import type { YearResult } from '../../types';

export class IncomeTaxDriver implements ISpecializedDriver {
    public readonly mode = 'income_tax';

    public bindSliders(ctx: DriverContext): void {
        ctx.sliderManager.sync('it_gross_salary', 'it_gross_salary_range');
        ctx.sliderManager.sync('it_basic_salary', 'it_basic_salary_range');
        ctx.sliderManager.sync('it_hra_received', 'it_hra_received_range');
        ctx.sliderManager.sync('it_rent_paid', 'it_rent_paid_range');
        ctx.sliderManager.sync('it_sec_80c', 'it_sec_80c_range');
        ctx.sliderManager.sync('it_sec_80d', 'it_sec_80d_range');
        ctx.sliderManager.sync('it_nps_80ccd1b', 'it_nps_80ccd1b_range');
        ctx.sliderManager.sync('it_home_loan_24b', 'it_home_loan_24b_range');
    }

    public bindAdditionalControls(ctx: DriverContext): void {
        const metroToggle = ctx.dom.getElement<HTMLInputElement>('it_is_metro');
        if (metroToggle) {
            metroToggle.addEventListener('change', () => ctx.recalculate());
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

        if (titleInvested) titleInvested.textContent = 'HRA Exemption';
        if (subInvested) subInvested.textContent = 'Sec 10(13A) Tax-Free';
        if (titleInterest) titleInterest.textContent = 'Old Regime Tax';
        if (subInterest) subInterest.textContent = 'Incl. 4% Cess';
        if (titleWithdrawn) titleWithdrawn.textContent = 'New Regime Tax';
        if (subWithdrawn) subWithdrawn.textContent = 'FY 2025-26 Slabs';
        if (titleCorpus) titleCorpus.textContent = 'Recommended Savings';
        if (subCorpus) subCorpus.textContent = 'Best Regime Advantage';
    }

    public calculate(ctx: DriverContext): void {
        const gross = Math.max(300000, parseFloat(ctx.dom.getValue('it_gross_salary') || '1500000') || 1500000);
        const basic = Math.max(100000, Math.min(gross, parseFloat(ctx.dom.getValue('it_basic_salary') || '750000') || 750000));
        const hraRec = Math.max(0, Math.min(gross, parseFloat(ctx.dom.getValue('it_hra_received') || '300000') || 300000));
        const rentPaid = Math.max(0, parseFloat(ctx.dom.getValue('it_rent_paid') || '360000') || 360000);
        const metroToggle = ctx.dom.getElement<HTMLInputElement>('it_is_metro');
        const isMetro = metroToggle ? metroToggle.checked : true;

        const sec80c = Math.max(0, Math.min(150000, parseFloat(ctx.dom.getValue('it_sec_80c') || '150000') || 0));
        const sec80d = Math.max(0, Math.min(100000, parseFloat(ctx.dom.getValue('it_sec_80d') || '25000') || 0));
        const nps80ccd1b = Math.max(0, Math.min(50000, parseFloat(ctx.dom.getValue('it_nps_80ccd1b') || '50000') || 0));
        const homeLoan24b = Math.max(0, Math.min(200000, parseFloat(ctx.dom.getValue('it_home_loan_24b') || '0') || 0));

        const res = IncomeTaxEngine.calculate(
            gross,
            basic,
            hraRec,
            rentPaid,
            isMetro,
            sec80c,
            sec80d,
            nps80ccd1b,
            homeLoan24b
        );

        const combined: YearResult[] = res.schedule.map((item) => {
            return {
                year: item.bracket_index,
                begin_balance: item.gross_income,
                sip_monthly: null,
                annual_contribution: item.old_tax,
                cumulative_invested: item.old_tax,
                interest: item.savings,
                combined_total: item.new_tax,
                post_tax_total: item.gross_income - Math.min(item.old_tax, item.new_tax),
            };
        });

        ctx.odometer.animateValue('summary-invested', res.hra_exemption);
        ctx.odometer.animateValue('summary-interest', res.old_total_tax);
        ctx.odometer.animateValue('summary-withdrawn', res.new_total_tax);
        ctx.odometer.animateValue('summary-corpus', res.tax_savings);

        const subInterest = document.getElementById('subtitle-interest');
        if (subInterest) {
            subInterest.textContent = `Effective: ${res.old_effective_rate}%`;
        }
        const subWithdrawn = document.getElementById('subtitle-withdrawn');
        if (subWithdrawn) {
            subWithdrawn.textContent = `Effective: ${res.new_effective_rate}%`;
        }
        const subCorpus = document.getElementById('subtitle-corpus');
        if (subCorpus) {
            subCorpus.textContent = res.recommended_regime === 'new'
                ? '✓ New Regime Saves More'
                : '✓ Old Regime Saves More';
        }

        const gainBadge = ctx.dom.getElement('summary-gain-badge');
        if (gainBadge) {
            gainBadge.textContent = res.recommended_regime === 'new'
                ? 'Winner: New Regime'
                : 'Winner: Old Regime';
            gainBadge.className = 'inline-flex items-center text-micro font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 tabular-nums';
        }

        ctx.resultsController.updateTable(combined, false);
        ctx.chartManager.updateChart(combined, false);
        ctx.summaryMetricsController.fitSummaryCards();
    }

    public getTelemetryPayload(ctx: DriverContext): Record<string, unknown> {
        const gross = Math.max(300000, parseFloat(ctx.dom.getValue('it_gross_salary') || '1500000') || 1500000);
        const basic = Math.max(100000, Math.min(gross, parseFloat(ctx.dom.getValue('it_basic_salary') || '750000') || 750000));
        const hraRec = Math.max(0, Math.min(gross, parseFloat(ctx.dom.getValue('it_hra_received') || '300000') || 300000));
        const rentPaid = Math.max(0, parseFloat(ctx.dom.getValue('it_rent_paid') || '360000') || 360000);
        const metroToggle = ctx.dom.getElement<HTMLInputElement>('it_is_metro');
        const isMetro = metroToggle ? metroToggle.checked : true;
        const sec80c = Math.max(0, Math.min(150000, parseFloat(ctx.dom.getValue('it_sec_80c') || '150000') || 0));
        const sec80d = Math.max(0, Math.min(100000, parseFloat(ctx.dom.getValue('it_sec_80d') || '25000') || 0));
        const nps80ccd1b = Math.max(0, Math.min(50000, parseFloat(ctx.dom.getValue('it_nps_80ccd1b') || '50000') || 0));
        const homeLoan24b = Math.max(0, Math.min(200000, parseFloat(ctx.dom.getValue('it_home_loan_24b') || '0') || 0));

        const res = IncomeTaxEngine.calculate(gross, basic, hraRec, rentPaid, isMetro, sec80c, sec80d, nps80ccd1b, homeLoan24b);

        return {
            calc_type: 'IncomeTax',
            gross_salary: res.gross_salary,
            hra_exemption: res.hra_exemption,
            recommended_regime: res.recommended_regime,
            tax_savings: res.tax_savings,
            goal_mode: 'income_tax',
        };
    }
}
