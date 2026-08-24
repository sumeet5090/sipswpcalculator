export interface GlossaryTerm {
    term: string;
    title: string;
    description: string;
    example?: string;
}

export const GLOSSARY_TERMS: Record<string, GlossaryTerm> = {
    cagr: {
        term: 'cagr',
        title: 'Compound Annual Growth Rate (CAGR)',
        description: 'The annualized rate of return that represents the geometric progression ratio of an investment over multiple years.',
        example: 'A ₹10 Lakh corpus growing to ₹31 Lakh in 10 years represents a 12% CAGR.'
    },
    xirr: {
        term: 'xirr',
        title: 'Extended Internal Rate of Return (XIRR)',
        description: 'The real rate of return on a series of multiple periodic cashflows occurring at different points in time (such as monthly SIP instalments).',
        example: 'XIRR accounts for exact transaction dates across your investment journey.'
    },
    stepup: {
        term: 'stepup',
        title: 'Annual Step-Up (Top-Up) SIP',
        description: 'A wealth-building strategy where you automatically increase your monthly investment by a fixed percentage (e.g., 10%) every year in line with annual salary hikes.',
        example: 'Increasing a ₹10,000 SIP by 10% each year yields more than 2× the final corpus compared to a flat SIP.'
    },
    swp: {
        term: 'swp',
        title: 'Systematic Withdrawal Plan (SWP)',
        description: 'A facility that allows mutual fund investors to withdraw a pre-determined amount from their corpus at fixed monthly intervals, providing tax-efficient retirement cashflow.',
        example: 'Only the capital gains component of each monthly SWP redemption is subject to capital gains tax, making it far superior to Fixed Deposit interest taxation.'
    },
    ltcg: {
        term: 'ltcg',
        title: 'Long-Term Capital Gains (Section 112A)',
        description: 'Profits made on equity mutual fund units held for more than 12 months. As per Budget 2024, gains above ₹1.25 Lakh per financial year are taxed at 12.5%.',
        example: 'If your total equity profit in a year is ₹3,00,000, only ₹1,75,000 is taxed at 12.5%.'
    },
    inflation: {
        term: 'inflation',
        title: 'Inflation Drag & Purchasing Power',
        description: 'The gradual erosion of the purchasing power of money over time due to the general rise in prices of goods and services.',
        example: 'At 6% annual inflation, ₹1 Crore after 20 years will have the purchasing power of roughly ₹31.18 Lakh today.'
    }
};

import { InvestmentInputs, YearResult } from '../../types';

export class GlossaryController {
    private getInputs?: () => InvestmentInputs;
    private getResults?: () => YearResult[];

    constructor(getInputs?: () => InvestmentInputs, getResults?: () => YearResult[]) {
        this.getInputs = getInputs;
        this.getResults = getResults;
    }

    init(): void {
        const terms = document.querySelectorAll<HTMLElement>('[data-glossary]');
        terms.forEach(el => {
            const key = el.dataset.glossary?.toLowerCase();
            if (!key || !GLOSSARY_TERMS[key]) return;

            const termData = GLOSSARY_TERMS[key];
            el.setAttribute('title', `${termData.title}: ${termData.description}`);
            el.classList.add('cursor-help', 'border-b', 'border-dotted', 'border-emerald-500/60');
        });

        // Initialize formula copy buttons
        const copyBtns = document.querySelectorAll<HTMLButtonElement>('.copy-formula-btn');
        copyBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const formula = btn.dataset.formula || '';
                if (formula && navigator.clipboard) {
                    navigator.clipboard.writeText(formula).then(() => {
                        const originalText = btn.textContent;
                        btn.textContent = '✓ Copied';
                        btn.classList.add('text-emerald-700', 'bg-emerald-50');
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.classList.remove('text-emerald-700', 'bg-emerald-50');
                        }, 1500);
                    });
                }
            });
        });

        // Initialize live formula proof copy buttons
        const liveProofBtns = document.querySelectorAll<HTMLButtonElement>('.copy-live-proof-btn');
        liveProofBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const inputs = this.getInputs?.();
                const results = this.getResults?.();
                if (!inputs) return;

                const sip = inputs.sip ?? 25000;
                const rate = inputs.rate ?? 12;
                const years = inputs.years ?? 15;
                const r = (rate / 12 / 100);
                const n = years * 12;
                const lastRow = results && results.length > 0 ? results[results.length - 1] : null;
                const finalCorpus = lastRow ? lastRow.combined_total : 0;

                const proofText = `FV = ₹${sip.toLocaleString('en-IN')} × [ { (1 + ${r.toFixed(4)})^${n} - 1 } / ${r.toFixed(4)} ] × (1 + ${r.toFixed(4)}) = ₹${Math.round(finalCorpus).toLocaleString('en-IN')}`;

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(proofText).then(() => {
                        const originalText = btn.textContent;
                        btn.textContent = '✓ Copied Live Proof!';
                        btn.classList.add('text-emerald-700', 'bg-emerald-100');
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.classList.remove('text-emerald-700', 'bg-emerald-100');
                        }, 2000);
                    });
                }
            });
        });
    }
}
