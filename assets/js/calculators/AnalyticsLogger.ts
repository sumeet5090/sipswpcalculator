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

export interface AnalyticsTransport {
    send(payload: Record<string, unknown>): void;
}

/**
 * BeaconFetchTransport
 * Transmits telemetry payloads via navigator.sendBeacon with fetch fallback.
 */
export class BeaconFetchTransport implements AnalyticsTransport {
    send(payload: Record<string, unknown>): void {
        try {
            const body = JSON.stringify(payload);
            if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
                const blob = new Blob([body], { type: 'application/json' });
                const queued = navigator.sendBeacon('/log_insight', blob);
                if (queued) {
                    return;
                }
            }
            fetch('/log_insight', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body,
                keepalive: true
            }).catch(() => {});
        } catch {
            // Silently ignore network or serialization errors to protect UX
        }
    }
}

/**
 * AnalyticsLogger.ts
 * Manages debounced user planning behavior logging.
 * Decouples telemetry schema building and lifecycle observers from network transport.
 */
export class AnalyticsService {
    private debounceMs: number;
    private transport: AnalyticsTransport;
    private insightTimeout: ReturnType<typeof setTimeout> | null = null;
    private pendingPayload: Record<string, unknown> | null = null;

    constructor(
        debounceMs: number = 3000,
        transport: AnalyticsTransport = new BeaconFetchTransport()
    ) {
        this.debounceMs = debounceMs;
        this.transport = transport;
        this.registerLifecycleListeners();
    }

    private registerLifecycleListeners(): void {
        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.flushPendingInsight();
                }
            });
        }
        if (typeof window !== 'undefined') {
            window.addEventListener('pagehide', () => {
                this.flushPendingInsight();
            });
        }
    }

    /**
     * Constructs the structured insight payload from current calculation state.
     */
    public buildPayload(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): Record<string, unknown> {
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

        return {
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
    }

    /**
     * Immediately send insight without debouncing (e.g. for conversion actions like PDF download).
     */
    public sendImmediateInsight(
        inputs: InvestmentInputs,
        results: YearResult[] = [],
        activeGoalMode: string = 'grow',
        extraSignals: ExtraSignals = {}
    ): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
            this.insightTimeout = null;
        }
        this.pendingPayload = null;
        const payload = this.buildPayload(inputs, results, activeGoalMode, extraSignals);
        this.transport.send(payload);
    }

    /**
     * Flush any currently pending debounced insight.
     */
    public flushPendingInsight(): void {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
            this.insightTimeout = null;
        }
        if (this.pendingPayload) {
            const payload = this.pendingPayload;
            this.pendingPayload = null;
            this.transport.send(payload);
        }
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

        this.pendingPayload = this.buildPayload(inputs, results, activeGoalMode, extraSignals);

        // Post to database endpoint after debounceMs of user input inactivity
        this.insightTimeout = setTimeout(() => {
            this.flushPendingInsight();
        }, this.debounceMs);
    }
}
