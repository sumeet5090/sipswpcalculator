import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';

export class SummaryMetricsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
    }

    /**
     * Adapt text font size inside metrics tiles on screen resize.
     */
    fitSummaryCards(): void {
        const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        const cardElms = ids
            .map(id => this.dom.getElement(id))
            .filter((el): el is HTMLElement => el !== null);

        if (cardElms.length === 0) return;

        // 1. Reset Phase
        cardElms.forEach(el => {
            el.style.whiteSpace = 'nowrap';
            el.style.overflow = 'hidden';
            if (!el.dataset.baseFont) {
                el.dataset.baseFont = getComputedStyle(el).fontSize;
            }
            const basePx = parseFloat(el.dataset.baseFont);
            el.style.fontSize = basePx + 'px';
        });

        // 2. Query Phase (batch all DOM measurements together to prevent layout thrashing)
        const measurements = cardElms.map(el => {
            const parent = el.parentElement;
            if (!parent) return null;
            const cs = getComputedStyle(parent);
            const availableW = parent.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
            const textW = el.scrollWidth;
            const basePx = parseFloat(el.dataset.baseFont || '16');
            return { el, basePx, availableW, textW };
        }).filter((item): item is NonNullable<typeof item> => item !== null);

        // 3. Command Phase (batch all style mutations together)
        measurements.forEach(({ el, basePx, availableW, textW }) => {
            if (textW > availableW && availableW > 0) {
                el.style.fontSize = Math.max((availableW / textW) * basePx, 10) + 'px';
            } else {
                el.style.fontSize = basePx + 'px';
            }
        });
    }

    /**
     * Reset cached base font sizes before re-fitting summary cards.
     */
    resetBaseFontCache(): void {
        const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        ids.forEach(id => {
            const el = this.dom.getElement(id);
            if (el) delete el.dataset.baseFont;
        });
    }

    /**
     * Update summary stats block.
     */
    updateSummaryMetrics(data: YearResult[]): void {
        if (!data || data.length === 0) return;

        const lastRow = data[data.length - 1];
        const totalInvested = lastRow.cumulative_invested;
        const preTaxCorpus = lastRow.combined_total;
        const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
        const preTaxGains = (preTaxCorpus + totalWithdrawn) - totalInvested;

        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;

        let finalCorpus = preTaxCorpus;
        let finalGains = preTaxGains;

        const inputs = this.getInputs();

        // Calculate delay cost
        const delayCost = MathEngine.calculateDelayCost(inputs);
        const delayCostEl = this.dom.getElement('delay-cost-amount');
        const delayCostBanner = this.dom.getElement('delay-cost-banner');

        if (delayCost > 0) {
            if (delayCostBanner) delayCostBanner.style.display = 'flex';
            if (delayCostEl) delayCostEl.textContent = this.formatter.format(delayCost);
        } else {
            if (delayCostBanner) delayCostBanner.style.display = 'none';
        }

        const interestTitle = this.dom.getElement('title-interest');
        const corpusTitle = this.dom.getElement('title-corpus');

        if (showPostTax) {
            const ltcgTax = lastRow.ltcg_tax ?? 0;
            finalCorpus = lastRow.post_tax_total ?? Math.max(0, preTaxCorpus - ltcgTax);
            finalGains = Math.max(0, preTaxGains - ltcgTax);

            if (interestTitle) interestTitle.textContent = 'Total Gains (Post-Tax)';
            if (corpusTitle) corpusTitle.textContent = 'Final Corpus (Post-Tax)';
        } else {
            if (interestTitle) interestTitle.textContent = 'Total Gains';
            if (corpusTitle) corpusTitle.textContent = 'Final Corpus';
        }

        // Apply inflation discounting
        if (inputs.inflation > 0) {
            const totalYears = inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years;
            finalCorpus = MathEngine.calculateInflationDiscount(
                finalCorpus,
                totalYears,
                inputs.inflation
            );
            finalGains = MathEngine.calculateInflationDiscount(
                finalGains,
                totalYears,
                inputs.inflation
            );
            if (corpusTitle) corpusTitle.textContent += ' (Inflation Adjusted)';
            if (interestTitle) interestTitle.textContent += ' (Inflation Adjusted)';
        }

        const setVal = (id: string, val: number) => {
            const el = this.dom.getElement(id);
            if (el) el.textContent = this.formatter.format(val);
        };

        setVal('summary-invested', totalInvested);
        setVal('summary-interest', finalGains);
        setVal('summary-withdrawn', totalWithdrawn);
        setVal('summary-corpus', finalCorpus);

        this.fitSummaryCards();
    }
}
