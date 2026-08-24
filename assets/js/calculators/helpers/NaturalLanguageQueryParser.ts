import { IndianNumberParser } from './IndianNumberParser.ts';

export interface ParsedFinancialQuery {
    isValid: boolean;
    type?: 'sip' | 'swp' | 'target' | 'lumpsum';
    sip?: number;
    years?: number;
    rate?: number;
    stepup?: number;
    lumpsum?: number;
    swp_withdrawal?: number;
    swp_years?: number;
    swp_rate?: number;
    target_corpus?: number;
    summaryText?: string;
}

/**
 * NaturalLanguageQueryParser
 * Parses conversational natural language financial queries (e.g. "sip 25k 15y 12%", "swp 50000 20 yrs 8%")
 * into structured calculator parameter payloads with zero latency.
 */
export class NaturalLanguageQueryParser {
    /**
     * Parses a query string into a structured financial scenario payload if applicable.
     */
    static parse(rawQuery: string): ParsedFinancialQuery {
        if (!rawQuery || typeof rawQuery !== 'string') {
            return { isValid: false };
        }

        const query = rawQuery.trim().toLowerCase();
        if (query.length < 3) {
            return { isValid: false };
        }

        // 1. SWP Intent Check (e.g., "swp 50k 20y 8%", "50000 swp for 15 years")
        if (query.includes('swp') || query.includes('pension') || query.includes('withdrawal')) {
            return this.parseSwpQuery(query);
        }

        // 2. Goal / Target Intent Check (e.g., "target 1cr in 10 years", "goal 50 lakh 15y")
        if (query.includes('target') || query.includes('goal') || query.includes('reach')) {
            return this.parseTargetQuery(query);
        }

        // 3. Lumpsum Intent Check (e.g., "5L lumpsum 15y 12%", "lumpsum 100000 for 10 yrs")
        if (query.includes('lump') || query.includes('one-time') || query.includes('onetime')) {
            return this.parseLumpsumQuery(query);
        }

        // 4. Default SIP Intent Check (e.g., "sip 25k 15y 12%", "10000 sip 20 years", "25k for 15 yrs")
        return this.parseSipQuery(query);
    }

    private static parseSipQuery(query: string): ParsedFinancialQuery {
        // Strip out filler words
        const cleaned = query.replace(/\b(for|in|years?|yrs?|yr|mo|monthly|per month|cagr|returns?|sip|invest|investment)\b/gi, ' ');
        const tokens = cleaned.split(/\s+/).filter(t => t.length > 0);

        let sipAmount: number | null = null;
        let tenureYears: number | null = null;
        let returnRate: number | null = null;
        let stepupRate: number | null = null;

        // Try extracting explicit patterns
        // Check for percentage (e.g. 12%, 14%)
        const rateMatch = query.match(/(\d+(?:\.\d+)?)\s*%/);
        if (rateMatch) {
            returnRate = parseFloat(rateMatch[1]);
        }

        // Check for explicit years (e.g. 15y, 15yr, 15years, 15 yrs)
        const yearMatch = query.match(/(\d+)\s*(?:y|yr|yrs|years)\b/i);
        if (yearMatch) {
            tenureYears = parseInt(yearMatch[1], 10);
        }

        // Parse remaining numeric tokens with IndianNumberParser
        for (const token of tokens) {
            if (token.includes('%')) continue;
            const parsed = IndianNumberParser.parse(token);
            if (parsed > 0) {
                if (sipAmount === null && parsed >= 100) {
                    sipAmount = parsed;
                } else if (tenureYears === null && parsed <= 50 && parsed >= 1) {
                    tenureYears = parsed;
                } else if (returnRate === null && parsed <= 35 && parsed >= 1) {
                    returnRate = parsed;
                } else if (stepupRate === null && parsed <= 25) {
                    stepupRate = parsed;
                }
            }
        }

        if (sipAmount === null || sipAmount < 100) {
            return { isValid: false };
        }

        const years = tenureYears ?? 10;
        const rate = returnRate ?? 12;
        const stepup = stepupRate ?? 0;

        const formattedSip = IndianNumberParser.format(sipAmount);
        const stepupText = stepup > 0 ? ` + ${stepup}% Step-Up` : '';

        return {
            isValid: true,
            type: 'sip',
            sip: sipAmount,
            years,
            rate,
            stepup,
            summaryText: `${formattedSip}/mo SIP • ${years} Yrs @ ${rate}% CAGR${stepupText}`
        };
    }

    private static parseSwpQuery(query: string): ParsedFinancialQuery {
        const cleaned = query.replace(/\b(swp|pension|withdrawal|withdraw|monthly|for|in|years?|yrs?|yr|cagr|returns?)\b/gi, ' ');
        const tokens = cleaned.split(/\s+/).filter(t => t.length > 0);

        let swpAmount: number | null = null;
        let tenureYears: number | null = null;
        let returnRate: number | null = null;

        const rateMatch = query.match(/(\d+(?:\.\d+)?)\s*%/);
        if (rateMatch) {
            returnRate = parseFloat(rateMatch[1]);
        }

        const yearMatch = query.match(/(\d+)\s*(?:y|yr|yrs|years)\b/i);
        if (yearMatch) {
            tenureYears = parseInt(yearMatch[1], 10);
        }

        for (const token of tokens) {
            if (token.includes('%')) continue;
            const parsed = IndianNumberParser.parse(token);
            if (parsed > 0) {
                if (swpAmount === null && parsed >= 500) {
                    swpAmount = parsed;
                } else if (tenureYears === null && parsed <= 50 && parsed >= 1) {
                    tenureYears = parsed;
                } else if (returnRate === null && parsed <= 25 && parsed >= 1) {
                    returnRate = parsed;
                }
            }
        }

        if (swpAmount === null || swpAmount < 500) {
            return { isValid: false };
        }

        const years = tenureYears ?? 20;
        const rate = returnRate ?? 8;
        const formattedSwp = IndianNumberParser.format(swpAmount);

        return {
            isValid: true,
            type: 'swp',
            swp_withdrawal: swpAmount,
            swp_years: years,
            swp_rate: rate,
            summaryText: `${formattedSwp}/mo SWP Pension • ${years} Yrs @ ${rate}% Return`
        };
    }

    private static parseTargetQuery(query: string): ParsedFinancialQuery {
        const cleaned = query.replace(/\b(target|goal|reach|corpus|for|in|years?|yrs?|yr|to)\b/gi, ' ');
        const tokens = cleaned.split(/\s+/).filter(t => t.length > 0);

        let targetCorpus: number | null = null;
        let tenureYears: number | null = null;
        let returnRate: number | null = null;

        for (const token of tokens) {
            const parsed = IndianNumberParser.parse(token);
            if (parsed > 0) {
                if (targetCorpus === null && parsed >= 10000) {
                    targetCorpus = parsed;
                } else if (tenureYears === null && parsed <= 50 && parsed >= 1) {
                    tenureYears = parsed;
                } else if (returnRate === null && parsed <= 30 && parsed >= 1) {
                    returnRate = parsed;
                }
            }
        }

        if (targetCorpus === null || targetCorpus < 10000) {
            return { isValid: false };
        }

        const years = tenureYears ?? 10;
        const rate = returnRate ?? 12;
        const formattedTarget = IndianNumberParser.format(targetCorpus);

        return {
            isValid: true,
            type: 'target',
            target_corpus: targetCorpus,
            years,
            rate,
            summaryText: `Goal Reverser: Accumulate ${formattedTarget} in ${years} Yrs @ ${rate}%`
        };
    }

    private static parseLumpsumQuery(query: string): ParsedFinancialQuery {
        const cleaned = query.replace(/\b(lumpsum|lump|one-time|onetime|invest|for|in|years?|yrs?|yr|cagr|returns?)\b/gi, ' ');
        const tokens = cleaned.split(/\s+/).filter(t => t.length > 0);

        let lumpsumAmount: number | null = null;
        let tenureYears: number | null = null;
        let returnRate: number | null = null;

        for (const token of tokens) {
            const parsed = IndianNumberParser.parse(token);
            if (parsed > 0) {
                if (lumpsumAmount === null && parsed >= 1000) {
                    lumpsumAmount = parsed;
                } else if (tenureYears === null && parsed <= 50 && parsed >= 1) {
                    tenureYears = parsed;
                } else if (returnRate === null && parsed <= 30 && parsed >= 1) {
                    returnRate = parsed;
                }
            }
        }

        if (lumpsumAmount === null || lumpsumAmount < 1000) {
            return { isValid: false };
        }

        const years = tenureYears ?? 10;
        const rate = returnRate ?? 12;
        const formattedLumpsum = IndianNumberParser.format(lumpsumAmount);

        return {
            isValid: true,
            type: 'lumpsum',
            lumpsum: lumpsumAmount,
            years,
            rate,
            summaryText: `${formattedLumpsum} One-Time Lumpsum • ${years} Yrs @ ${rate}% CAGR`
        };
    }
}
