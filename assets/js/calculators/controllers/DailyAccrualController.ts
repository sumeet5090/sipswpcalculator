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
            return '🌴 Full Financial Independence (₹3.0L+/mo passive cashflow)';
        }
        if (dailyInterest >= 5000) {
            return '🏡 Fully covers monthly Home Rent / EMI (₹1.5L/mo passive)';
        }
        if (dailyInterest >= 2000) {
            return '🛒 Complete Monthly Household Groceries & Utilities (₹60k/mo passive)';
        }
        if (dailyInterest >= 800) {
            return '🍽️ Daily Family Dining & Commute funded by interest (₹24k/mo)';
        }
        if (dailyInterest >= 250) {
            return '☕ Daily Gourmet Coffee & Fuel funded purely by interest';
        }
        if (dailyInterest > 0) {
            return '🌱 Early Wealth Seed (Daily compounding acceleration)';
        }
        return 'Daily compounding velocity at maturity';
    }
}
