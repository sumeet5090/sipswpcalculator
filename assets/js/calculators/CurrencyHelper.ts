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
        if (amount >= 10000000) {
            return this.symbol + (amount / 10000000).toFixed(2).replace(/\.00$/, '') + ' Crore';
        }
        if (amount >= 100000) {
            return this.symbol + (amount / 100000).toFixed(2).replace(/\.00$/, '') + ' Lakh';
        }
        if (amount >= 1000) {
            return this.symbol + (amount / 1000).toFixed(2).replace(/\.00$/, '') + 'k';
        }
        return this.symbol + amount.toLocaleString(this.locale);
    }
}
