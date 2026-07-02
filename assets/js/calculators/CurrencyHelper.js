/**
 * CurrencyHelper.js
 * Manages currency formatting according to Indian standards (Lakhs/Crores).
 * Refactored as an Object-Oriented class.
 */
export class CurrencyFormatter {
    constructor(locale = 'en-IN', currency = 'INR', symbol = '₹') {
        this.locale = locale;
        this.currency = currency;
        this.symbol = symbol;
    }

    /**
     * Format numeric value to currency string.
     * @param {number} value 
     * @returns {string}
     */
    format(value) {
        return new Intl.NumberFormat(this.locale, {
            style: 'currency',
            currency: this.currency,
            maximumFractionDigits: 0
        }).format(value);
    }

    /**
     * Format dynamic large amounts with appropriate Lakh/Crore suffix.
     * @param {number} amount 
     * @returns {string}
     */
    formatDynamic(amount) {
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
