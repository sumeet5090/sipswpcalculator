/**
 * CurrencyHelper.ts
 * Manages currency formatting according to Indian standards (Lakhs/Crores)
 * and international formats, with strict negative sign placement and zero suppression.
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
     * Standard currency formatting with negative sign preceding the currency symbol.
     */
    format(value: number): string {
        const rounded = Math.round(value + Number.EPSILON) || 0;
        if (Math.abs(rounded) === 0) {
            return `${this.symbol}0`;
        }

        const isNegative = rounded < 0;
        const absVal = Math.abs(rounded);
        const formattedNumber = new Intl.NumberFormat(this.locale, {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(absVal);

        return isNegative ? `-${this.symbol}${formattedNumber}` : `${this.symbol}${formattedNumber}`;
    }

    /**
     * Dynamic denomination formatting (e.g. ₹10 Lakh, ₹1.5 Crore, ₹50k).
     */
    formatDynamic(amount: number): string {
        const rounded = Math.round(amount + Number.EPSILON) || 0;
        if (Math.abs(rounded) === 0) {
            return `${this.symbol}0`;
        }

        const absAmount = Math.abs(rounded);
        const isNegative = rounded < 0;
        const prefix = isNegative ? `-${this.symbol}` : this.symbol;

        if (this.currency === 'INR') {
            if (absAmount >= 10000000) {
                const cr = (absAmount / 10000000).toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
                return `${prefix}${cr} Crore`;
            }
            if (absAmount >= 100000) {
                const lk = (absAmount / 100000).toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');
                return `${prefix}${lk} Lakh`;
            }
            if (absAmount >= 1000) {
                const k = (absAmount / 1000).toFixed(1).replace(/\.0$/, '');
                return `${prefix}${k}k`;
            }
        } else {
            if (absAmount >= 1000000000) {
                return `${prefix}${(absAmount / 1000000000).toFixed(2).replace(/\.00$/, '')}B`;
            }
            if (absAmount >= 1000000) {
                return `${prefix}${(absAmount / 1000000).toFixed(2).replace(/\.00$/, '')}M`;
            }
            if (absAmount >= 1000) {
                return `${prefix}${(absAmount / 1000).toFixed(1).replace(/\.0$/, '')}k`;
            }
        }

        return isNegative
            ? `-${this.symbol}${absAmount.toLocaleString(this.locale)}`
            : `${this.symbol}${absAmount.toLocaleString(this.locale)}`;
    }

    /**
     * Formats Indian word translation for live input helper badges (e.g. "≈ 25 Lakhs").
     */
    formatWordBadge(amount: number): string {
        const absVal = Math.abs(Math.round(amount + Number.EPSILON) || 0);
        if (absVal < 100000) {
            return '';
        }
        if (absVal >= 10000000) {
            const cr = (absVal / 10000000).toFixed(2).replace(/\.00$/, '');
            return `≈ ${cr} Crore${parseFloat(cr) > 1 ? 's' : ''}`;
        }
        const lk = (absVal / 100000).toFixed(2).replace(/\.00$/, '');
        return `≈ ${lk} Lakh${parseFloat(lk) > 1 ? 's' : ''}`;
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

        if (fieldId === 'years' || fieldId === 'swp_years') {
            const months = value * 12;
            return `${value} Year${value > 1 ? 's' : ''} (${months} Months)`;
        }

        if (fieldId === 'rate' || fieldId === 'swp_rate' || fieldId === 'inflation' || fieldId === 'stepup') {
            return `${value.toFixed(1).replace(/\.0$/, '')}% per annum`;
        }

        return '';
    }
}
