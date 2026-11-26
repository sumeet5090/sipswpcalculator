import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';
import type { YearResult } from '../../types';
import type { ChartScrubbingController } from '../controllers/ChartScrubbingController';

/**
 * ChartTelemetryController
 * Manages chart HUD metrics, inspection ribbon, lens indicators, axis tick formatting,
 * and assistive screen reader announcements.
 */
export class ChartTelemetryController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    public formatAxisTick(value: number): string {
        if (isNaN(value) || !isFinite(value) || value < 0) return '';

        const symbol = this.formatter.getSymbol();
        const currency = this.formatter.getCurrency();

        if (currency === 'INR') {
            if (value === 0) return `${symbol}0`;
            if (value >= 10000000) {
                const cr = value / 10000000;
                return `${symbol}${Number.isInteger(cr) || cr >= 10 ? cr.toFixed(0) : cr.toFixed(1)} Cr`;
            }
            if (value >= 100000) {
                const l = value / 100000;
                return `${symbol}${Number.isInteger(l) || l >= 10 ? l.toFixed(0) : l.toFixed(1)} L`;
            }
            if (value >= 1000) {
                return `${symbol}${(value / 1000).toFixed(0)}k`;
            }
            return `${symbol}${value.toFixed(0)}`;
        }

        if (value >= 1000000000) {
            return `${symbol}${(value / 1000000000).toFixed(1)}B`;
        }
        if (value >= 1000000) {
            return `${symbol}${(value / 1000000).toFixed(1)}M`;
        }
        if (value >= 1000) {
            return `${symbol}${(value / 1000).toFixed(0)}k`;
        }
        return `${symbol}${value.toFixed(0)}`;
    }

    public updateChartHeaderSummary(results: YearResult[]): void {
        const appMode = this.dom.getElement('calculator-app')?.dataset?.mode || '';
        const finalRow = results[results.length - 1];
        if (!finalRow) return;

        const headerGrossEl = this.dom.getElement('chart-header-gross');
        const headerGainEl = this.dom.getElement('chart-header-gain');

        if (headerGrossEl) {
            if (appMode === 'emi') {
                const totalInterest = results.reduce((acc, r) => acc + (r.interest ?? 0), 0);
                headerGrossEl.textContent = this.formatter.format(finalRow.cumulative_invested + totalInterest);
            } else {
                headerGrossEl.textContent = this.formatter.format(finalRow.combined_total);
            }
        }
        if (headerGainEl) {
            if (appMode === 'emi') {
                const totalInterest = results.reduce((acc, r) => acc + (r.interest ?? 0), 0);
                headerGainEl.textContent = `${this.formatter.format(totalInterest)} Total Interest`;
            } else if (appMode === 'inflation') {
                const costIncrease = Math.max(0, finalRow.combined_total - finalRow.cumulative_invested);
                headerGainEl.textContent = `+${this.formatter.format(costIncrease)} Escalation`;
            } else {
                const netGains = Math.max(0, (finalRow.combined_total + (finalRow.cumulative_withdrawals ?? 0)) - finalRow.cumulative_invested);
                headerGainEl.textContent = `+${this.formatter.format(netGains)} Gains`;
            }
        }
    }

    public updateActiveLensIndicator(): void {
        const indicator = this.dom.getElement('active-lens-indicator');
        if (!indicator) return;

        const showCorridor = this.dom.getElement<HTMLInputElement>('show_historical_corridor')?.checked || false;
        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const showWealthMap = this.dom.getElement<HTMLInputElement>('show_wealth_map')?.checked || false;

        const active: string[] = [];
        if (showPostTax) active.push('Post-Tax');
        if (showCorridor) active.push('Range');
        if (showWealthMap) active.push('Breakdown');

        if (active.length === 0) {
            indicator.textContent = 'Standard View';
            indicator.className = 'inline-flex items-center text-micro font-bold text-slate-500 bg-slate-100/90 px-2 py-0.5 rounded-full border border-slate-200/70 shrink-0';
        } else {
            indicator.textContent = active.join(' + ');
            indicator.className = 'inline-flex items-center text-micro font-bold text-emerald-800 bg-emerald-100/90 px-2 py-0.5 rounded-full border border-emerald-200/80 shadow-flat shrink-0';
        }
    }

    public updateInspectionRibbon(
        row: YearResult,
        lastResults: YearResult[],
        scrubbingController: ChartScrubbingController | null
    ): void {
        if (scrubbingController) {
            scrubbingController.inspect(row, lastResults.length);
            return;
        }

        const rYear = this.dom.getElement('ribbon-inspect-year');
        const rInvested = this.dom.getElement('ribbon-inspect-invested');
        const rGains = this.dom.getElement('ribbon-inspect-gains');
        const rCorpus = this.dom.getElement('ribbon-inspect-corpus');

        const hudYearLabel = this.dom.getElement('hud-year-label');
        const hudInvested = this.dom.getElement('hud-invested-metric');
        const hudGains = this.dom.getElement('hud-gains-metric');
        const hudTotal = this.dom.getElement('hud-total-metric');
        const hudTimelineIndicator = this.dom.getElement('hud-timeline-indicator');

        const totalYears = lastResults.length || row.year;
        const investedStr = this.formatter.format(row.cumulative_invested);
        const corpusStr = this.formatter.format(row.combined_total);
        const gains = Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested);
        const gainsStr = this.formatter.format(gains);

        if (rYear) rYear.textContent = `Year ${row.year} of ${totalYears}`;
        if (rInvested) rInvested.textContent = investedStr;
        if (rCorpus) rCorpus.textContent = corpusStr;
        if (rGains) rGains.textContent = `+${gainsStr}`;

        const statusDot = this.dom.getElement('hud-status-dot');
        if (statusDot) statusDot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';

        const appMode = this.dom.getElement('calculator-app')?.dataset?.mode || '';
        if (hudYearLabel) hudYearLabel.textContent = `Year ${row.year} of ${totalYears}`;

        if (appMode === 'emi') {
            if (hudInvested) hudInvested.textContent = `Principal Paid: ${investedStr}`;
            if (hudGains) hudGains.textContent = `Interest Paid: ${this.formatter.format(row.interest || 0)}`;
            if (hudTotal) hudTotal.textContent = `Loan Balance: ${corpusStr}`;
        } else if (appMode === 'inflation') {
            if (hudInvested) hudInvested.textContent = `Today: ${investedStr}`;
            if (hudGains) hudGains.textContent = `Escalation: +${gainsStr}`;
            if (hudTotal) hudTotal.textContent = `Future Cost: ${corpusStr}`;
        } else {
            if (hudInvested) hudInvested.textContent = `Invested: ${investedStr}`;
            if (hudGains) hudGains.textContent = `Gains: +${gainsStr}`;
            if (hudTotal) hudTotal.textContent = `Total: ${corpusStr}`;
        }

        if (hudTimelineIndicator) {
            hudTimelineIndicator.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0';
        }
    }

    public announceCurrentPoint(
        index: number,
        lastResults: YearResult[],
        scrubbingController: ChartScrubbingController | null
    ): void {
        const row = lastResults[index];
        if (!row) return;
        const invested = this.formatter.format(row.cumulative_invested);
        const corpus = this.formatter.format(row.combined_total);
        const gains = this.formatter.format(Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested));
        A11yAnnouncer.announceYearInspection(row.year, invested, corpus, gains);
        this.updateInspectionRibbon(row, lastResults, scrubbingController);
    }
}
