import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult } from '../../types';

export class DailyAccrualController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    init(): void {
        // Initial setup if needed
    }

    updateResults(results: YearResult[]): void {
        if (!results || results.length === 0) return;

        const lastRow = results[results.length - 1];
        if (!lastRow) return;

        const rawInterest = lastRow.interest ?? 0;
        const annualInterest = Number.isFinite(rawInterest) ? Math.max(0, rawInterest) : 0;
        const dailyInterest = Math.round(annualInterest / 365);

        const amountEl = this.dom.getElement('daily-accrual-amount');
        const equivEl = this.dom.getElement('daily-lifestyle-equiv');
        const badgeEl = this.dom.getElement('daily-lifestyle-multiplier-badge');

        if (amountEl) {
            amountEl.textContent = `${this.formatter.formatDynamic(dailyInterest)} / day`;
        }

        if (equivEl) {
            equivEl.textContent = this.getLifestyleEquivalent(dailyInterest);
        }

        if (badgeEl && dailyInterest > 0) {
            badgeEl.classList.remove('hidden');
            badgeEl.classList.add('inline-flex');
        }
    }

    private getLifestyleEquivalent(dailyInterest: number): string {
        if (dailyInterest >= 10000) {
            return 'Institutional Passive Tier (₹3.0L+/mo sustained yield)';
        }
        if (dailyInterest >= 5000) {
            return 'Primary Housing Coverage (₹1.5L+/mo passive yield)';
        }
        if (dailyInterest >= 2000) {
            return 'Core Household Cashflow Tier (₹60k+/mo passive yield)';
        }
        if (dailyInterest >= 800) {
            return 'Monthly Utility & Living Offset (₹24k+/mo passive yield)';
        }
        if (dailyInterest >= 250) {
            return 'Sustained Daily Accrual (₹7.5k+/mo passive yield)';
        }
        if (dailyInterest > 0) {
            return 'Early Compounding Accrual Base';
        }
        return 'Daily compounding velocity at maturity';
    }
}
