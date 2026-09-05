/**
 * PurchasingPowerController.ts
 * Manages real purchasing power translation, cultural Indian spending benchmarks,
 * and the 0-100 Peace-of-Mind resilience score.
 * Strictly adheres to SOLID, DRY, and WCAG AAA accessibility standards.
 */

import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs } from '../../types';

export interface PurchasingPowerInsight {
    nominalValue: number;
    realValue: number;
    inflationRate: number;
    years: number;
    purchasingPowerLossPct: number;
    anchorDescription: string;
    peaceOfMindScore: number;
    resilienceTier: 'bulletproof' | 'prudent' | 'vulnerable';
    resilienceBadgeText: string;
}

export class PurchasingPowerController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    /**
     * Compute real purchasing power and peace-of-mind score from current state.
     */
    public analyze(nominalCorpus: number, inputs: InvestmentInputs): PurchasingPowerInsight {
        const years = inputs.years || 1;
        const inflationRate = Math.max(0, inputs.inflation ?? 6.0);

        // Real Value PV = FV / (1 + i)^n
        const discountFactor = Math.pow(1 + inflationRate / 100, years);
        const realValue = discountFactor > 0 ? Math.round(nominalCorpus / discountFactor) : nominalCorpus;

        const lossPct = nominalCorpus > 0
            ? Math.max(0, Math.min(100, Math.round(((nominalCorpus - realValue) / nominalCorpus) * 100)))
            : 0;

        const anchor = this.resolveAnchor(realValue);
        const { score, tier, badgeText } = this.calculatePeaceOfMind(inputs);

        return {
            nominalValue: nominalCorpus,
            realValue,
            inflationRate,
            years,
            purchasingPowerLossPct: lossPct,
            anchorDescription: anchor,
            peaceOfMindScore: score,
            resilienceTier: tier,
            resilienceBadgeText: badgeText
        };
    }

    /**
     * Maps real corpus to concrete, relatable Indian living reality anchors.
     */
    public resolveAnchor(realCorpus: number): string {
        if (realCorpus >= 30000000) {
            return 'Perpetual sovereign wealth: provides ₹1,20,000/mo lifetime safe withdrawal with zero capital erosion.';
        }
        if (realCorpus >= 12500000) {
            return 'Complete financial independence: covers luxury living expenses in Pune, Bengaluru, or Hyderabad debt-free.';
        }
        if (realCorpus >= 5000000) {
            return 'A fully paid-off 2BHK/3BHK metro apartment + 5 years of family emergency reserve.';
        }
        if (realCorpus >= 2000000) {
            return 'Comprehensive 4-year overseas Master’s degree tuition + living expenses fully funded.';
        }
        return 'Solid financial foundation: shields household against unexpected medical or employment shocks.';
    }

    /**
     * Computes the 0-100 Peace-of-Mind Index evaluating return realism and plan robustness.
     */
    public calculatePeaceOfMind(inputs: InvestmentInputs): { score: number; tier: 'bulletproof' | 'prudent' | 'vulnerable'; badgeText: string } {
        let score = 70; // baseline

        // 1. Return realism check (historical Nifty 50 CAGR is ~12%)
        if (inputs.rate <= 12) {
            score += 15; // Realistic conservative assumption
        } else if (inputs.rate <= 13.5) {
            score += 5; // Moderate growth assumption
        } else if (inputs.rate >= 16) {
            score -= 20; // Aggressive bull-run assumption
        } else {
            score -= 5;
        }

        // 2. Step-up booster bonus (cushions lifestyle inflation)
        if (inputs.stepup >= 10) {
            score += 15;
        } else if (inputs.stepup >= 5) {
            score += 10;
        }

        // 3. Time horizon compounding resilience
        if (inputs.years >= 15) {
            score += 10;
        } else if (inputs.years >= 10) {
            score += 5;
        } else if (inputs.years <= 3) {
            score -= 10;
        }

        const clamped = Math.max(25, Math.min(100, score));

        if (clamped >= 88) {
            return {
                score: clamped,
                tier: 'bulletproof',
                badgeText: '🛡️ Bulletproof Resilience (Institutional Grade)'
            };
        } else if (clamped >= 72) {
            return {
                score: clamped,
                tier: 'prudent',
                badgeText: '⚖️ Prudent & Realistic Balanced Model'
            };
        }
        return {
            score: clamped,
            tier: 'vulnerable',
            badgeText: '⚠️ High Market Volatility Sensitivity'
        };
    }

    /**
     * Updates the DOM elements for real purchasing power and peace-of-mind score if present.
     */
    public updateDisplay(nominalCorpus: number, inputs: InvestmentInputs): void {
        const insight = this.analyze(nominalCorpus, inputs);

        const realCorpusEl = this.dom.getElement('real-purchasing-corpus');
        if (realCorpusEl) {
            realCorpusEl.textContent = this.formatter.format(insight.realValue);
        }

        const anchorEl = this.dom.getElement('purchasing-power-anchor');
        if (anchorEl) {
            anchorEl.textContent = insight.anchorDescription;
        }

        const scoreEl = this.dom.getElement('peace-of-mind-score');
        if (scoreEl) {
            scoreEl.textContent = `${insight.peaceOfMindScore}/100`;
        }

        const badgeEl = this.dom.getElement('peace-of-mind-badge');
        if (badgeEl) {
            badgeEl.textContent = insight.resilienceBadgeText;
            badgeEl.className = insight.resilienceTier === 'bulletproof'
                ? 'text-ui-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200'
                : insight.resilienceTier === 'prudent'
                ? 'text-ui-xs font-bold px-3 py-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200'
                : 'text-ui-xs font-bold px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200';
        }
    }
}
