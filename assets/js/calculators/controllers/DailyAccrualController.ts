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

        const annualInterest = Math.max(0, lastRow.interest || 0);
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
        if (dailyInterest >= 20000) {
            return 'Equal to a 5-Star Luxury Suite every single day';
        }
        if (dailyInterest >= 10000) {
            return 'Equal to a Weekend Getaway funded every day';
        }
        if (dailyInterest >= 4000) {
            return 'Equal to 4 Premium Family Dinners funded every day';
        }
        if (dailyInterest >= 1500) {
            return 'Equal to your Complete Daily Household Expenses covered';
        }
        if (dailyInterest >= 600) {
            return 'Equal to Daily Gourmet Meals & Commute covered';
        }
        if (dailyInterest >= 200) {
            return 'Equal to Daily Premium Coffee & Fuel funded by interest';
        }
        return 'Daily compounding velocity at maturity';
    }
}
