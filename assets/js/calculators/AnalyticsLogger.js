/**
 * AnalyticsLogger.js
 * Manages debounced user planning behavior logging.
 * Refactored as an Object-Oriented class.
 */
export class AnalyticsService {
    constructor(debounceMs = 1500) {
        this.debounceMs = debounceMs;
        this.insightTimeout = null;
    }

    /**
     * Log user calculations in a debounced fashion to prevent network spamming.
     * @param {object} inputs 
     */
    logInsight(inputs) {
        if (this.insightTimeout) {
            clearTimeout(this.insightTimeout);
        }

        // Post to database endpoint after debounceMs of user input inactivity
        this.insightTimeout = setTimeout(() => {
            const payload = {
                calc_type: inputs.enable_swp ? 'SWP' : 'SIP',
                amount: inputs.enable_swp ? inputs.swp_withdrawal : inputs.sip,
                duration: inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years,
                step_up_pct: inputs.enable_swp ? inputs.swp_stepup : inputs.stepup,
                currency: 'INR',
                interest_rate: inputs.rate,
                sip_amount: inputs.sip,
                sip_duration: inputs.years,
                sip_step_up: inputs.stepup,
                swp_enabled: inputs.enable_swp ? 1 : 0,
                swp_withdrawal: inputs.swp_withdrawal,
                swp_duration: inputs.swp_years,
                swp_step_up: inputs.swp_stepup
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
