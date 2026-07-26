/**
 * MathEngine.js
 * Core mathematical engine for compounding and Systematic Withdrawal Plan projections.
 * Refactored as an Object-Oriented class.
 */
export class MathEngine {
    /**
     * Perform month-by-month compounding simulation.
     * @param {object} inp - Inputs containing sip, years, rate, stepup, enable_swp, swp_withdrawal, swp_years, swp_stepup.
     * @returns {Array} List of yearly breakdown records.
     */
    static calculateCorpus(inp) {
        const swpStartYear = inp.years + 1;
        const totalYears = inp.enable_swp ? (inp.years + inp.swp_years) : inp.years;
        const lumpsum = inp.lumpsum || 0;
        const swpRate = inp.swp_rate || 8;

        let netBalance = lumpsum;
        let cumulativeInvested = lumpsum;
        let cumulativeWithdrawals = 0.0;

        const results = [];

        for (let y = 1; y <= totalYears; y++) {
            const currentRate = (y <= inp.years) ? inp.rate : swpRate;
            const monthlyRate = currentRate / 100 / 12;

            // Monthly SIP amount for this year
            let monthlySip = 0.0;
            if (y <= inp.years) {
                monthlySip = Math.round((inp.sip * Math.pow(1 + inp.stepup / 100, y - 1)) * 100) / 100;
            }

            // Monthly SWP amount for this year
            let monthlySwp = 0.0;
            if (inp.enable_swp && y >= swpStartYear) {
                monthlySwp = Math.round((inp.swp_withdrawal * Math.pow(1 + inp.swp_stepup / 100, y - swpStartYear)) * 100) / 100;
            }

            let actualYearWithdrawn = 0.0;
            let annualContribution = Math.round((monthlySip * 12) * 100) / 100;
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

            cumulativeInvested = Math.round((cumulativeInvested + annualContribution) * 100) / 100;
            let annualWithdrawal = Math.round(actualYearWithdrawn * 100) / 100;
            if (inp.enable_swp && y >= swpStartYear) {
                cumulativeWithdrawals = Math.round((cumulativeWithdrawals + annualWithdrawal) * 100) / 100;
            }

            let interestEarned = netBalance - (yearBegin + annualContribution - annualWithdrawal);
            
            // Tax Calculation (LTCG 12.5% on gains exceeding 1.25 Lakh)
            let preTaxGains = netBalance + cumulativeWithdrawals - cumulativeInvested;
            let taxableGains = Math.max(0, preTaxGains - 125000);
            let ltcgTax = taxableGains * 0.125;
            let postTaxCorpus = Math.max(0, netBalance - ltcgTax);

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
                combined_total: Math.round(netBalance),
                ltcg_tax: Math.round(ltcgTax),
                post_tax_total: Math.round(postTaxCorpus)
            });
        }

        return results;
    }

    /**
     * Calculate the cost of delaying the investment by 1 year.
     * Assumes a fixed retirement age, so delaying by 1 year means investing for 1 less year.
     * @param {object} inp - Inputs
     * @returns {number} The difference in the final combined_total
     */
    static calculateDelayCost(inp) {
        if (inp.years <= 1) return 0;
        const currentResults = this.calculateCorpus(inp);
        const delayedInp = { ...inp, years: inp.years - 1 };
        const delayedResults = this.calculateCorpus(delayedInp);
        
        const currentFinal = currentResults[currentResults.length - 1].combined_total;
        const delayedFinal = delayedResults[delayedResults.length - 1].combined_total;
        
        return Math.max(0, currentFinal - delayedFinal);
    }

    /**
     * Adjust the final corpus for inflation to show purchasing power parity.
     * @param {number} finalCorpus 
     * @param {number} totalYears 
     * @param {number} inflationRate 
     * @returns {number} 
     */
    static calculateInflationDiscount(finalCorpus, totalYears, inflationRate) {
        if (inflationRate <= 0) return finalCorpus;
        return finalCorpus / Math.pow(1 + (inflationRate / 100), totalYears);
    }

    /**
     * Binary Search to find the required starting SIP to reach a target corpus.
     * @param {object} inp - Inputs
     * @param {number} targetCorpus - The desired final corpus
     * @returns {number} The required monthly SIP amount
     */
    static calculateRequiredSip(inp, targetCorpus) {
        if (targetCorpus <= 0) return 0;
        
        const zeroSipResults = this.calculateCorpus({ ...inp, sip: 0 });
        if (zeroSipResults[zeroSipResults.length - 1].combined_total >= targetCorpus) {
            return 0;
        }
        
        let low = 0;
        let high = targetCorpus;
        let bestSip = 0;
        
        // Cap iterations to 40 for max 5ms execution time (zero-latency)
        for (let i = 0; i < 40; i++) {
            const mid = (low + high) / 2;
            const testInp = { ...inp, sip: mid };
            const results = this.calculateCorpus(testInp);
            const finalCorpus = results[results.length - 1].combined_total;
            
            if (Math.abs(finalCorpus - targetCorpus) < 1) {
                bestSip = mid;
                break;
            } else if (finalCorpus < targetCorpus) {
                low = mid;
            } else {
                high = mid;
            }
            bestSip = mid;
        }
        
        return Math.round(bestSip);
    }

    /**
     * Binary Search to find the required initial corpus to sustain a specific SWP plan.
     * @param {object} inp - SWP inputs
     * @returns {number} The required starting corpus at the beginning of the SWP phase
     */
    static calculateRequiredStartingCorpusForSwp(inp) {
        if (!inp.enable_swp || inp.swp_withdrawal <= 0 || inp.swp_years <= 0) return 0;
        
        let low = 0;
        let high = inp.swp_withdrawal * 12 * inp.swp_years * 3; // safe upper bound
        let bestCorpus = 0;
        
        for (let i = 0; i < 40; i++) {
            const mid = (low + high) / 2;
            const testInp = {
                ...inp,
                sip: 0,
                years: 0,
                lumpsum: mid
            };
            const results = this.calculateCorpus(testInp);
            const finalBalance = results[results.length - 1].combined_total;
            
            if (Math.abs(finalBalance) < 1) {
                bestCorpus = mid;
                break;
            } else if (finalBalance <= 0) {
                // If it ran out, we need more starting corpus
                low = mid;
            } else {
                // If we ended with a surplus, we can start with less
                high = mid;
            }
            bestCorpus = mid;
        }
        return Math.round(bestCorpus);
    }
}
