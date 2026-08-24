import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * ChartScrubbingController
 * Floating pinned inspection ribbon and magnetic scrubbing rail for mobile chart exploration.
 */
export class ChartScrubbingController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    private ribbonEl: HTMLElement | null = null;
    private ribbonYearEl: HTMLElement | null = null;
    private ribbonInvestedEl: HTMLElement | null = null;
    private ribbonCorpusEl: HTMLElement | null = null;
    private ribbonGainsEl: HTMLElement | null = null;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;

        this.initDOM();
    }

    private initDOM(): void {
        this.ribbonEl = this.dom.getElement<HTMLElement>('chart-inspection-ribbon');
        this.ribbonYearEl = this.dom.getElement<HTMLElement>('ribbon-inspect-year');
        this.ribbonInvestedEl = this.dom.getElement<HTMLElement>('ribbon-inspect-invested');
        this.ribbonCorpusEl = this.dom.getElement<HTMLElement>('ribbon-inspect-corpus');
        this.ribbonGainsEl = this.dom.getElement<HTMLElement>('ribbon-inspect-gains');
    }

    public inspect(row: YearResult): void {
        if (!this.ribbonEl || !row) return;

        if (this.ribbonYearEl) {
            this.ribbonYearEl.textContent = `Year ${row.year}`;
        }
        if (this.ribbonInvestedEl) {
            this.ribbonInvestedEl.textContent = this.formatter.format(row.cumulative_invested);
        }
        if (this.ribbonCorpusEl) {
            this.ribbonCorpusEl.textContent = this.formatter.format(row.combined_total);
        }
        if (this.ribbonGainsEl) {
            const gains = Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested);
            this.ribbonGainsEl.textContent = `+${this.formatter.format(gains)}`;
        }

        this.ribbonEl.classList.remove('hidden');
    }

    public clear(): void {
        if (this.ribbonEl) {
            this.ribbonEl.classList.add('hidden');
        }
    }
}
