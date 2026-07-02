/**
 * CurrencyHelper.js
 * Manages currency formatting according to Indian standards (Lakhs/Crores).
 */

const locale = 'en-IN';
const currency = 'INR';
const symbol = '₹';

/**
 * Format numeric value to INR currency string.
 * @param {number} value 
 * @returns {string}
 */
export function formatCurrency(value) {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency,
        maximumFractionDigits: 0
    }).format(value);
}

/**
 * Format dynamic large amounts with appropriate Lakh/Crore suffix.
 * @param {number} amount 
 * @returns {string}
 */
export function formatDynamicAmount(amount) {
    if (amount >= 10000000) {
        return symbol + (amount / 10000000).toFixed(2).replace(/\.00$/, '') + ' Crore';
    }
    if (amount >= 100000) {
        return symbol + (amount / 100000).toFixed(2).replace(/\.00$/, '') + ' Lakh';
    }
    if (amount >= 1000) {
        return symbol + (amount / 1000).toFixed(2).replace(/\.00$/, '') + 'k';
    }
    return symbol + amount.toLocaleString(locale);
}
