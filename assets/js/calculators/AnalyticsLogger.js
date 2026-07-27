/**
 * AnalyticsLogger.js
 * Manages debounced user planning behavior logging.
 * Refactored as an Object-Oriented class.
 */
export class AnalyticsService {
    constructor(debounceMs = 3000) {
        this.debounceMs = debounceMs;
        this.insightTimeout = null;
    }

    /**
     * Log user calculations in a debounced fashion to prevent network spamming.
     * @param {object} inputs 
     * @param {array} results 
     * @param {string} activeGoalMode 
     */
    logInsight(inputs, results = [], activeGoalMode = 'grow') {
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

            const breakdownEl = document.getElementById('yearly-breakdown-section') || document.getElementById('breakdown-body');
            const tableViewed = breakdownEl
                ? (breakdownEl.getBoundingClientRect().top < (window.innerHeight || document.documentElement.clientHeight) ? 1 : 0)
                : 0;

            const deviceType = (window.innerWidth < 768) ? 'mobile' : 'desktop';

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
                swp_step_up: inputs.swp_stepup,
                final_corpus: finalCorpus,
                total_invested: totalInvested,
                wealth_multiplier: wealthMultiplier,
                goal_mode: activeGoalMode || 'grow',
                device_type: deviceType,
                table_viewed: tableViewed
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
