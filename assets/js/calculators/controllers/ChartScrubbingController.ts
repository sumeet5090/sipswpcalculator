import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * ChartScrubbingController
 * Controls the zero-CLS persistent telemetry HUD strip for desktop and mobile chart exploration.
 */
export class ChartScrubbingController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    private ribbonYearEl: HTMLElement | null = null;
    private ribbonInvestedEl: HTMLElement | null = null;
    private ribbonCorpusEl: HTMLElement | null = null;
    private ribbonGainsEl: HTMLElement | null = null;
    private statusDotEl: HTMLElement | null = null;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;

        this.initDOM();
    }

    private initDOM(): void {
        this.ribbonYearEl = this.dom.getElement<HTMLElement>('ribbon-inspect-year');
        this.ribbonInvestedEl = this.dom.getElement<HTMLElement>('ribbon-inspect-invested');
        this.ribbonCorpusEl = this.dom.getElement<HTMLElement>('ribbon-inspect-corpus');
        this.ribbonGainsEl = this.dom.getElement<HTMLElement>('ribbon-inspect-gains');
        this.statusDotEl = this.dom.getElement<HTMLElement>('hud-status-dot');
    }

    public inspect(row: YearResult, totalYears?: number): void {
        if (!row) return;

        if (this.ribbonYearEl) {
            this.ribbonYearEl.textContent = totalYears ? `Year ${row.year} of ${totalYears}` : `Year ${row.year}`;
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
        if (this.statusDotEl) {
            this.statusDotEl.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';
        }
    }

    public clear(finalRow?: YearResult): void {
        if (finalRow) {
            this.inspect(finalRow);
            if (this.statusDotEl) {
                this.statusDotEl.className = 'w-1.5 h-1.5 rounded-full bg-slate-400';
            }
        }
    }
}

