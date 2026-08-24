import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';
import type { YearResult } from '../../types';

export type ScrubCallback = (index: number) => void;

/**
 * ChartScrubbingController
 * Controls the zero-CLS persistent telemetry HUD strip and tactile mobile thumb-scrubbing bar.
 * Strictly adheres to SOLID, DRY, and POLA principles.
 */
export class ChartScrubbingController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private onScrubCallback: ScrubCallback | null = null;

    private ribbonYearEl: HTMLElement | null = null;
    private ribbonInvestedEl: HTMLElement | null = null;
    private ribbonCorpusEl: HTMLElement | null = null;
    private ribbonGainsEl: HTMLElement | null = null;
    private statusDotEl: HTMLElement | null = null;

    private mobileScrubberEl: HTMLInputElement | null = null;
    private scrubberActiveIndicatorEl: HTMLElement | null = null;
    private scrubberMaxIndicatorEl: HTMLElement | null = null;

    private currentResults: YearResult[] = [];
    private isInitialized: boolean = false;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        onScrubCallback: ScrubCallback | null = null
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.onScrubCallback = onScrubCallback;

        this.initDOM();
    }

    private initDOM(): void {
        this.ribbonYearEl = this.dom.getElement<HTMLElement>('ribbon-inspect-year');
        this.ribbonInvestedEl = this.dom.getElement<HTMLElement>('ribbon-inspect-invested');
        this.ribbonCorpusEl = this.dom.getElement<HTMLElement>('ribbon-inspect-corpus');
        this.ribbonGainsEl = this.dom.getElement<HTMLElement>('ribbon-inspect-gains');
        this.statusDotEl = this.dom.getElement<HTMLElement>('hud-status-dot');

        this.mobileScrubberEl = this.dom.getElement<HTMLInputElement>('mobile-chart-scrubber');
        this.scrubberActiveIndicatorEl = this.dom.getElement<HTMLElement>('scrubber-active-indicator');
        this.scrubberMaxIndicatorEl = this.dom.getElement<HTMLElement>('scrubber-max-indicator');

        this.bindMobileScrubber();
    }

    public setOnScrubCallback(cb: ScrubCallback): void {
        this.onScrubCallback = cb;
    }

    /**
     * Binds the mobile tactile range scrubber with haptic feedback.
     */
    private bindMobileScrubber(): void {
        if (!this.mobileScrubberEl || this.isInitialized) return;
        this.isInitialized = true;

        this.mobileScrubberEl.addEventListener('input', (e) => {
            const target = e.target as HTMLInputElement;
            const year = parseInt(target.value, 10);
            if (isNaN(year) || this.currentResults.length === 0) return;

            const index = Math.max(0, Math.min(this.currentResults.length - 1, year - 1));
            const row = this.currentResults[index];
            if (row) {
                this.inspect(row, this.currentResults.length);
                if (this.scrubberActiveIndicatorEl) {
                    this.scrubberActiveIndicatorEl.textContent = `Yr ${row.year}: ${this.formatter.format(row.combined_total)}`;
                }
                if (this.onScrubCallback) {
                    this.onScrubCallback(index);
                }
                if (typeof navigator !== 'undefined' && 'vibrate' in navigator && (row.year % 5 === 0 || row.year === this.currentResults.length)) {
                    try {
                        navigator.vibrate(8);
                    } catch {
                        // Silent ignore
                    }
                }
            }
        });
    }

    /**
     * Syncs the scrubber bounds with latest calculation results.
     */
    public syncResults(results: YearResult[]): void {
        this.currentResults = results;
        const totalYears = results.length;

        if (this.mobileScrubberEl && totalYears > 0) {
            this.mobileScrubberEl.min = '1';
            this.mobileScrubberEl.max = String(totalYears);
            this.mobileScrubberEl.value = String(totalYears);
        }

        if (this.scrubberMaxIndicatorEl && totalYears > 0) {
            this.scrubberMaxIndicatorEl.textContent = `Yr ${totalYears}`;
        }

        if (results.length > 0) {
            const finalRow = results[results.length - 1];
            this.inspect(finalRow, totalYears);
            if (this.statusDotEl) {
                this.statusDotEl.className = 'w-1.5 h-1.5 rounded-full bg-slate-400';
            }
            if (this.scrubberActiveIndicatorEl) {
                this.scrubberActiveIndicatorEl.textContent = `Yr ${finalRow.year}: ${this.formatter.format(finalRow.combined_total)}`;
            }
        }
    }

    public inspect(row: YearResult, totalYears?: number, announce: boolean = false): void {
        if (!row) return;

        const maxYears = totalYears || this.currentResults.length || row.year;

        if (this.ribbonYearEl) {
            this.ribbonYearEl.textContent = `Year ${row.year} of ${maxYears}`;
        }
        const investedStr = this.formatter.format(row.cumulative_invested);
        const corpusStr = this.formatter.format(row.combined_total);
        const gainsVal = Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested);
        const gainsStr = this.formatter.format(gainsVal);

        if (this.ribbonInvestedEl) {
            this.ribbonInvestedEl.textContent = investedStr;
        }
        if (this.ribbonCorpusEl) {
            this.ribbonCorpusEl.textContent = corpusStr;
        }
        if (this.ribbonGainsEl) {
            this.ribbonGainsEl.textContent = `+${gainsStr}`;
        }
        if (this.statusDotEl) {
            this.statusDotEl.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';
        }

        if (announce) {
            A11yAnnouncer.announceYearInspection(row.year, investedStr, corpusStr, gainsStr);
        }
    }

    public clear(finalRow?: YearResult): void {
        if (finalRow) {
            this.inspect(finalRow, this.currentResults.length);
            if (this.statusDotEl) {
                this.statusDotEl.className = 'w-1.5 h-1.5 rounded-full bg-slate-400';
            }
        }
    }
}
