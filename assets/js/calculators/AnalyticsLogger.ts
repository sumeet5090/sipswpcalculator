import { InvestmentInputs, YearResult } from '../types';

export interface ExtraSignals {
    pdf_downloaded?: boolean;
    pdf_has_custom_name?: boolean;
    inflation_enabled?: boolean;
    interaction_count?: number;
    preset_clicked?: string;
    exit_action?: string;
    table_viewed?: number;
    device_type?: string;
    currency?: string;
}

/**
 * AnalyticsLogger.ts
 * Manages debounced user planning behavior logging.
 * Refactored as an Object-Oriented class.
 */
export class AnalyticsService {
    private debounceMs: number;
    private insightTimeout: ReturnType<typeof setTimeout> | null = null;

    constructor(debounceMs: number = 3000) {
        this.debounceMs = debounceMs;
    }

    /**
     * Log user calculations in a debounced fashion to prevent network spamming.
     */
    logInsight(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
        }

        // Post to database endpoint after debounceMs of user input inactivity
        this.insightTimeout = setTimeout(() => {
            const lastRow = (Array.isArray(results) && results.length > 0) ? results[results.length - 1] : null;
            const finalCorpus = lastRow ? (lastRow.combined_total || 0) : null;
            const totalInvested = lastRow ? (lastRow.cumulative_invested || 0) : null;
            const wealthMultiplier = (finalCorpus !== null && totalInvested && totalInvested > 0)
                ? parseFloat((finalCorpus / totalInvested).toFixed(2))
                : null;

            const tableViewed = extraSignals.table_viewed ?? 0;
            const deviceType = extraSignals.device_type ?? 'desktop';
            const currencyCode = extraSignals.currency || 'INR';

            const isLumpsumOnly = !inputs.enable_swp && inputs.lumpsum > 0 && inputs.sip === 0;
            const calcType = inputs.enable_swp ? 'SWP' : (isLumpsumOnly ? 'Lumpsum' : 'SIP');
            const primaryAmount = inputs.enable_swp
                ? inputs.swp_withdrawal
                : (isLumpsumOnly ? inputs.lumpsum : inputs.sip);

            const payload = {
                calc_type: calcType,
                amount: primaryAmount,
                duration: inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years,
                step_up_pct: inputs.enable_swp ? inputs.swp_stepup : inputs.stepup,
                currency: currencyCode,
                interest_rate: inputs.rate,
                sip_amount: inputs.sip,
                sip_duration: inputs.years,
                sip_step_up: inputs.stepup,
                swp_enabled: inputs.enable_swp ? 1 : 0,
                swp_withdrawal: inputs.swp_withdrawal,
                swp_duration: inputs.swp_years,
                swp_step_up: inputs.swp_stepup,
                final_corpus: finalCorpus,
                total_invested: totalInvested,
                wealth_multiplier: wealthMultiplier,
                goal_mode: activeGoalMode || 'grow',
                device_type: deviceType,
                table_viewed: tableViewed,
                pdf_downloaded: extraSignals.pdf_downloaded ? 1 : 0,
                pdf_has_custom_name: extraSignals.pdf_has_custom_name ? 1 : 0,
                inflation_enabled: inputs.inflation > 0 ? 1 : (extraSignals.inflation_enabled ? 1 : 0),
                interaction_count: extraSignals.interaction_count || 1,
                preset_clicked: extraSignals.preset_clicked || 'none',
                exit_action: extraSignals.exit_action || 'calc_only'
            };

            fetch('/log_insight', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(() => {});
        }, this.debounceMs);
    }
}
