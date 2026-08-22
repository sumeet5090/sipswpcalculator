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
     * Update active currency configuration.
     */
    setCurrency(locale: string, currency: string, symbol: string): void {
        this.locale = locale;
        this.currency = currency;
        this.symbol = symbol;
    }

    /**
     * Format numeric value to currency string.
     */
    format(value: number): string {
        const rounded = Math.round(value) || 0;
        return new Intl.NumberFormat(this.locale, {
            style: 'currency',
            currency: this.currency,
            maximumFractionDigits: 0
        }).format(rounded);
    }

    /**
     * Format dynamic large amounts with appropriate Lakh/Crore or Million/Billion suffix.
     */
    formatDynamic(amount: number): string {
        const rounded = Math.round(amount) || 0;
        const absAmount = Math.abs(rounded);
        const isNegative = absAmount > 0 && rounded < 0;
        const prefix = isNegative ? `-${this.symbol}` : this.symbol;

        if (this.currency === 'INR') {
            if (absAmount >= 10000000) {
                return prefix + (absAmount / 10000000).toFixed(2).replace(/\.00$/, '') + ' Crore';
            }
            if (absAmount >= 100000) {
                return prefix + (absAmount / 100000).toFixed(2).replace(/\.00$/, '') + ' Lakh';
            }
            if (absAmount >= 1000) {
                return prefix + (absAmount / 1000).toFixed(2).replace(/\.00$/, '') + 'k';
            }
        } else {
            if (absAmount >= 1000000000) {
                return prefix + (absAmount / 1000000000).toFixed(2).replace(/\.00$/, '') + 'B';
            }
            if (absAmount >= 1000000) {
                return prefix + (absAmount / 1000000).toFixed(2).replace(/\.00$/, '') + 'M';
            }
            if (absAmount >= 1000) {
                return prefix + (absAmount / 1000).toFixed(2).replace(/\.00$/, '') + 'k';
            }
        }

        return isNegative
            ? `-${this.symbol}${absAmount.toLocaleString(this.locale)}`
            : `${this.symbol}${absAmount.toLocaleString(this.locale)}`;
    }

    /**
     * Format contextual subtext for inputs (e.g., SIP amount, Lumpsum, Target Corpus, SWP).
     */
    formatSubtext(fieldId: string, value: number): string {
        if (isNaN(value) || value <= 0) return '';

        if (fieldId === 'sip') {
            const annual = value * 12;
            const annualFormatted = this.formatDynamic(annual);
            const monthlyFormatted = this.formatDynamic(value);
            return `${monthlyFormatted} / mo • ${annualFormatted} / yr`;
        }

        if (fieldId === 'swp' || fieldId === 'swp_withdrawal' || fieldId === 'swp_amount') {
            const annual = value * 12;
            const annualFormatted = this.formatDynamic(annual);
            const monthlyFormatted = this.formatDynamic(value);
            return `${monthlyFormatted} / mo • ${annualFormatted} / yr`;
        }

        if (fieldId === 'lumpsum' || fieldId === 'corpus' || fieldId === 'initial_corpus' || fieldId === 'target_corpus') {
            return this.formatDynamic(value);
        }

        return '';
    }
}

