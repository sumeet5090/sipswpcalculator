/**
 * MathEngine.js
 * Core mathematical engine for compounding and Systematic Withdrawal Plan projections.
 * Fully decoupled from browser DOM.
 */

/**
 * Perform month-by-month compounding simulation.
 * @param {object} inp - Inputs containing sip, years, rate, stepup, enable_swp, swp_withdrawal, swp_years, swp_stepup.
 * @returns {Array} List of yearly breakdown records.
 */
export function calculateCorpus(inp) {
    const monthlyRate = inp.rate / 100 / 12;
    const swpStartYear = inp.years + 1;
    const totalYears = inp.enable_swp ? (inp.years + inp.swp_years) : inp.years;

    let netBalance = 0.0;
    let cumulativeInvested = 0.0;
    let cumulativeWithdrawals = 0.0;

    const results = [];

    for (let y = 1; y <= totalYears; y++) {
        // Monthly SIP amount for this year
        let monthlySip = 0.0;
        if (y <= inp.years) {
            monthlySip = inp.sip * Math.pow(1 + inp.stepup / 100, y - 1);
        }

        // Monthly SWP amount for this year
        let monthlySwp = 0.0;
        if (inp.enable_swp && y >= swpStartYear) {
            monthlySwp = inp.swp_withdrawal * Math.pow(1 + inp.swp_stepup / 100, y - swpStartYear);
        }

        let actualYearWithdrawn = 0.0;
        let annualContribution = monthlySip * 12;
        let yearBegin = netBalance;

        for (let m = 1; m <= 12; m++) {
            let contrib = (y <= inp.years) ? monthlySip : 0.0;
            let potentialBalance = netBalance + contrib;

            let withdraw = 0.0;
            if (inp.enable_swp && y >= swpStartYear && monthlySwp > 0) {
                withdraw = Math.min(monthlySwp, potentialBalance);
            }

            actualYearWithdrawn += withdraw;
            netBalance = (netBalance + contrib - withdraw) * (1 + monthlyRate);
            if (netBalance < 0) netBalance = 0;
        }

        cumulativeInvested += annualContribution;
        if (inp.enable_swp && y >= swpStartYear) {
            cumulativeWithdrawals += actualYearWithdrawn;
        }

        let annualWithdrawal = actualYearWithdrawn;
        let interestEarned = netBalance - (yearBegin + annualContribution - annualWithdrawal);

        results.push({
            year: y,
            begin_balance: Math.round(yearBegin),
            sip_monthly: (y <= inp.years) ? monthlySip : null,
            annual_contribution: annualContribution,
            cumulative_invested: cumulativeInvested,
            swp_monthly: (inp.enable_swp && y >= swpStartYear) ? monthlySwp : null,
            annual_withdrawal: (inp.enable_swp && y >= swpStartYear) ? annualWithdrawal : null,
            cumulative_withdrawals: (inp.enable_swp && y >= swpStartYear) ? cumulativeWithdrawals : 0,
            interest: Math.round(interestEarned),
            combined_total: Math.round(netBalance)
        });
    }

    return results;
}
