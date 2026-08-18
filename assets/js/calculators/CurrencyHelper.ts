/**
 * CurrencyHelper.ts
 * Manages currency formatting according to Indian standards (Lakhs/Crores).
 * Refactored as an Object-Oriented class.
 */
export class CurrencyFormatter {
    private locale: string;
    private currency: string;
    private symbol: string;

    constructor(locale: string = 'en-IN', currency: string = 'INR', symbol: string = '₹') {
        this.locale = locale;
        this.currency = currency;
        this.symbol = symbol;
    }

    /**
     * Get currency code string.
     */
    getCurrency(): string {
        return this.currency;
    }

    /**
     * Get currency symbol string.
     */
    getSymbol(): string {
        return this.symbol;
    }

    /**
     * Format numeric value to currency string.
     */
    format(value: number): string {
        return new Intl.NumberFormat(this.locale, {
            style: 'currency',
            currency: this.currency,
            maximumFractionDigits: 0
        }).format(value);
    }

    /**
     * Format dynamic large amounts with appropriate Lakh/Crore suffix.
     */
    formatDynamic(amount: number): string {
        const isNegative = amount < 0;
        const absAmount = Math.abs(amount);
        const prefix = isNegative ? `-${this.symbol}` : this.symbol;

        if (absAmount >= 10000000) {
            return prefix + (absAmount / 10000000).toFixed(2).replace(/\.00$/, '') + ' Crore';
        }
        if (absAmount >= 100000) {
            return prefix + (absAmount / 100000).toFixed(2).replace(/\.00$/, '') + ' Lakh';
        }
        if (absAmount >= 1000) {
            return prefix + (absAmount / 1000).toFixed(2).replace(/\.00$/, '') + 'k';
        }
        return isNegative
            ? `-${this.symbol}${absAmount.toLocaleString(this.locale)}`
            : `${this.symbol}${absAmount.toLocaleString(this.locale)}`;
    }
}
