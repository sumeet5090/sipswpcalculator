import type { InvestmentInputs, YearResult } from '../types/index.ts';

/**
 * MathEngine.ts
 * Core mathematical engine for compounding and Systematic Withdrawal Plan projections.
 * Refactored as an Object-Oriented class.
 */
export class MathEngine {
    private static round2(val: number): number {
        return Math.round((val + Number.EPSILON) * 100) / 100;
    }

    /**
     * Perform month-by-month compounding simulation.
     */
    static calculate(inp: InvestmentInputs): YearResult[] {
        const swpStartYear = inp.years + 1;
        const totalYears = inp.enable_swp ? (inp.years + inp.swp_years) : inp.years;
        const lumpsum = inp.lumpsum || 0;
        const swpRate = inp.swp_rate;

        let netBalance = lumpsum;
        let cumulativeInvested = lumpsum;
        let cumulativeWithdrawals = 0.0;

        if (totalYears <= 0) {
            return [{
                year: 0,
                begin_balance: Math.round(lumpsum),
                sip_monthly: null,
                annual_contribution: 0,
                cumulative_invested: Math.round(lumpsum),
                swp_monthly: null,
                annual_withdrawal: null,
                cumulative_withdrawals: 0,
                interest: 0,
                combined_total: Math.round(lumpsum),
                ltcg_tax: 0,
                post_tax_total: Math.round(lumpsum)
            }];
        }

        const results: YearResult[] = [];

        for (let y = 1; y <= totalYears; y++) {
            const currentRate = (y <= inp.years) ? inp.rate : swpRate;
            const monthlyRate = currentRate / 100 / 12;

            // Monthly SIP amount for this year
            let monthlySip = 0.0;
            if (y <= inp.years) {
                monthlySip = this.round2(inp.sip * Math.pow(1 + inp.stepup / 100, y - 1));
            }

            // Monthly SWP amount for this year
            let monthlySwp = 0.0;
            if (inp.enable_swp && y >= swpStartYear) {
                monthlySwp = this.round2(inp.swp_withdrawal * Math.pow(1 + inp.swp_stepup / 100, y - swpStartYear));
            }

            let actualYearWithdrawn = 0.0;
            let annualContribution = this.round2(monthlySip * 12);
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
            let annualWithdrawal = this.round2(actualYearWithdrawn);
            if (inp.enable_swp && y >= swpStartYear) {
                cumulativeWithdrawals = this.round2(cumulativeWithdrawals + annualWithdrawal);
            }

            let interestEarned = netBalance - (yearBegin + annualContribution - annualWithdrawal);
            
            // Tax Calculation (LTCG rate on gains exceeding exemption threshold)
            let preTaxGains = netBalance + cumulativeWithdrawals - cumulativeInvested;
            const ltcgExemption = inp.ltcg_exemption ?? 125000;
            const ltcgTaxRate = inp.ltcg_tax_rate ?? 0.125;
            let taxableGains = Math.max(0, preTaxGains - ltcgExemption);
            let ltcgTax = taxableGains * ltcgTaxRate;
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
     */
    static calculateDelayCost(inp: InvestmentInputs): number {
        if (inp.years <= 1) return 0;
        const currentResults = this.calculate(inp);
        const delayedInp: InvestmentInputs = { ...inp, years: inp.years - 1 };
        const delayedResults = this.calculate(delayedInp);
        
        const currentFinal = currentResults[currentResults.length - 1]?.combined_total ?? 0;
        const delayedFinal = delayedResults[delayedResults.length - 1]?.combined_total ?? 0;
        
        return Math.max(0, currentFinal - delayedFinal);
    }

    /**
     * Adjust the final corpus for inflation to show purchasing power parity.
     */
    static calculateInflationDiscount(finalCorpus: number, totalYears: number, inflationRate: number): number {
        if (inflationRate <= 0 || totalYears <= 0 || finalCorpus <= 0) return Math.max(0, finalCorpus);
        return Math.max(0, finalCorpus / Math.pow(1 + (inflationRate / 100), totalYears));
    }

    /**
     * Binary Search to find the required starting SIP to reach a target corpus.
     */
    static calculateRequiredSip(inp: InvestmentInputs, targetCorpus: number): number {
        if (targetCorpus <= 0 || inp.years <= 0) return 0;

        // Closed-form linear calculation when rate is 0 and stepup is 0
        if (inp.rate <= 0 && inp.stepup <= 0) {
            const remaining = Math.max(0, targetCorpus - (inp.lumpsum || 0));
            return Math.round(remaining / (inp.years * 12));
        }
        
        const zeroSipResults = this.calculate({ ...inp, sip: 0 });
        if (zeroSipResults.length > 0 && zeroSipResults[zeroSipResults.length - 1].combined_total >= targetCorpus) {
            return 0;
        }
        
        let low = 0;
        let high = Math.max(targetCorpus, (targetCorpus / Math.max(1, inp.years)) * 2, 1000000);
        let bestSip = 0;
        
        // Cap iterations to 40 for max 5ms execution time (zero-latency)
        for (let i = 0; i < 40; i++) {
            const mid = (low + high) / 2;
            const testInp: InvestmentInputs = { ...inp, sip: mid };
            const results = this.calculate(testInp);
            if (results.length === 0) break;
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
     */
    static calculateRequiredStartingCorpusForSwp(inp: InvestmentInputs): number {
        if (!inp.enable_swp || inp.swp_withdrawal <= 0 || inp.swp_years <= 0) return 0;
        
        // Closed-form linear calculation when swp_rate is 0 and swp_stepup is 0
        if (inp.swp_rate <= 0 && inp.swp_stepup <= 0) {
            return Math.round(inp.swp_withdrawal * 12 * inp.swp_years);
        }

        // Dynamically compute exact escalated sum of withdrawals to set a mathematically safe upper bound
        let totalEscalatedWithdrawals = 0;
        for (let k = 0; k < inp.swp_years; k++) {
            totalEscalatedWithdrawals += 12 * inp.swp_withdrawal * Math.pow(1 + inp.swp_stepup / 100, k);
        }

        let low = 0;
        let high = Math.max(totalEscalatedWithdrawals * 1.5, inp.swp_withdrawal * 12 * inp.swp_years * 5, 1000000);
        let bestCorpus = high;
        
        for (let i = 0; i < 40; i++) {
            const mid = (low + high) / 2;
            const testInp: InvestmentInputs = {
                ...inp,
                sip: 0,
                years: (inp.years && inp.years > 0) ? inp.years : 0,
                lumpsum: mid
            };
            const results = this.calculate(testInp);
            if (results.length === 0) break;
            const finalBalance = results[results.length - 1].combined_total;
            const ranOutEarly = results.some((r, idx) => idx < results.length - 1 && r.combined_total <= 0);

            if (!ranOutEarly && finalBalance >= 0) {
                bestCorpus = mid;
                if (Math.abs(finalBalance) < 1) {
                    break;
                }
                high = mid;
            } else {
                low = mid;
            }
        }
        return Math.round(bestCorpus);
    }

    /**
     * Calculate potential Section 112A Tax-Harvesting alpha savings.
     * Systematically realizing up to ₹1,25,000 of LTCG per year tax-free resets the cost basis.
     */
    static calculateTaxHarvestingSavings(inp: InvestmentInputs, results: YearResult[]): {
        standardTax: number;
        harvestedTax: number;
        cumulativeSavings: number;
        totalHarvestedGains: number;
    } {
        if (!results || results.length === 0) {
            return { standardTax: 0, harvestedTax: 0, cumulativeSavings: 0, totalHarvestedGains: 0 };
        }

        const lastRow = results[results.length - 1];
        const standardTax = lastRow.ltcg_tax ?? 0;
        const exemptionPerYear = inp.ltcg_exemption ?? 125000;
        const taxRate = inp.ltcg_tax_rate ?? 0.125;

        // Cumulative exemption harvested across holding years
        const totalYears = results.length;
        const maxHarvestable = totalYears * exemptionPerYear;
        const totalGains = Math.max(0, (lastRow.combined_total + (lastRow.cumulative_withdrawals ?? 0)) - lastRow.cumulative_invested);
        const actualHarvestedGains = Math.min(totalGains, maxHarvestable);

        const remainingTaxable = Math.max(0, totalGains - actualHarvestedGains);
        const harvestedTax = Math.round(remainingTaxable * taxRate);
        const cumulativeSavings = Math.max(0, standardTax - harvestedTax);

        return {
            standardTax,
            harvestedTax,
            cumulativeSavings,
            totalHarvestedGains: actualHarvestedGains
        };
    }
}
