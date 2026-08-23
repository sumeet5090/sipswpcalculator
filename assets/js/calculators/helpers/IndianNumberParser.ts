/**
 * IndianNumberParser.ts
 * Robust, Postel's Law compliant parser for Indian financial denominations,
 * currency symbols, and comma-separated numeric notations.
 */
export class IndianNumberParser {
    /**
     * Parse raw string or number into a standard float.
     * Supports notations like: "25k", "1.5L", "2.5 Crore", "₹1,00,000", "50 lac", "10 Cr".
     *
     * @param val Raw input from user (string or number)
     * @returns Parsed float value, or NaN if completely unparseable
     */
    static parse(val: number | string | null | undefined): number {
        if (val === null || val === undefined) {
            return NaN;
        }

        if (typeof val === 'number') {
            return isNaN(val) ? NaN : val;
        }

        const raw = String(val).trim();
        if (raw === '') {
            return NaN;
        }

        // Clean out currency symbols, whitespace, and commas
        // (₹, Rs, INR, $, commas)
        let cleaned = raw
            .replace(/[₹$]/g, '')
            .replace(/\b(rs\.?|inr)\b/gi, '')
            .replace(/,/g, '')
            .trim()
            .toLowerCase();

        if (cleaned === '') {
            return NaN;
        }

        // 1. Crore notation (cr, crore, crores, crs)
        const croreMatch = cleaned.match(/^([\d.]+)\s*(cr|crore|crores|crs)$/);
        if (croreMatch && croreMatch[1]) {
            const num = parseFloat(croreMatch[1]);
            return isNaN(num) ? NaN : Math.round((num + Number.EPSILON) * 10000000);
        }

        // 2. Lakh notation (l, lakh, lakhs, lac, lacs)
        const lakhMatch = cleaned.match(/^([\d.]+)\s*(l|lakh|lakhs|lac|lacs)$/);
        if (lakhMatch && lakhMatch[1]) {
            const num = parseFloat(lakhMatch[1]);
            return isNaN(num) ? NaN : Math.round((num + Number.EPSILON) * 100000);
        }

        // 3. Thousand notation (k, thousand, thousands)
        const thousandMatch = cleaned.match(/^([\d.]+)\s*(k|thousand|thousands)$/);
        if (thousandMatch && thousandMatch[1]) {
            const num = parseFloat(thousandMatch[1]);
            return isNaN(num) ? NaN : Math.round((num + Number.EPSILON) * 1000);
        }

        // 4. International Billion / Million notation
        const billionMatch = cleaned.match(/^([\d.]+)\s*(b|billion|billions)$/);
        if (billionMatch && billionMatch[1]) {
            const num = parseFloat(billionMatch[1]);
            return isNaN(num) ? NaN : Math.round((num + Number.EPSILON) * 1000000000);
        }

        const millionMatch = cleaned.match(/^([\d.]+)\s*(m|million|millions)$/);
        if (millionMatch && millionMatch[1]) {
            const num = parseFloat(millionMatch[1]);
            return isNaN(num) ? NaN : Math.round((num + Number.EPSILON) * 1000000);
        }

        // 5. Percent suffix (12%, 12.5 %)
        const percentMatch = cleaned.match(/^([\d.]+)\s*%$/);
        if (percentMatch && percentMatch[1]) {
            const num = parseFloat(percentMatch[1]);
            return isNaN(num) ? NaN : num;
        }

        // 6. Year suffix (15Y, 15 yrs, 15 years)
        const yearMatch = cleaned.match(/^([\d.]+)\s*(y|yr|yrs|year|years)$/);
        if (yearMatch && yearMatch[1]) {
            const num = parseFloat(yearMatch[1]);
            return isNaN(num) ? NaN : num;
        }

        // 7. Pure numeric float
        const parsed = parseFloat(cleaned);
        return isNaN(parsed) ? NaN : parsed;
    }
}
