import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult, InvestmentInputs } from '../../types';

export interface ScenarioSnapshot {
    inputs: InvestmentInputs;
    totalInvested: number;
    finalCorpus: number;
}

export class ScenarioDiffController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private snapshot: ScenarioSnapshot | null = null;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    init(): void {
        const snapshotBtn = this.dom.getElement('snapshot-scenario-btn');
        const clearBtn = this.dom.getElement('scenario-clear-btn');

        if (snapshotBtn) {
            snapshotBtn.addEventListener('click', () => {
                // Handled via external snapshot trigger or direct state capture
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearSnapshot();
            });
        }
    }

    setSnapshot(inputs: InvestmentInputs, results: YearResult[]): void {
        if (!results || results.length === 0) return;
        const last = results[results.length - 1];
        this.snapshot = {
            inputs: { ...inputs },
            totalInvested: last.cumulative_invested,
            finalCorpus: last.combined_total
        };

        const card = this.dom.getElement('scenario-diff-card');
        if (card) {
            card.classList.remove('hidden');
        }
        this.updateDiff(results);
    }

    clearSnapshot(): void {
        this.snapshot = null;
        const card = this.dom.getElement('scenario-diff-card');
        if (card) {
            card.classList.add('hidden');
        }
    }

    updateDiff(currentResults: YearResult[]): void {
        if (!this.snapshot || !currentResults || currentResults.length === 0) return;

        const currentLast = currentResults[currentResults.length - 1];
        const currentCorpus = currentLast?.combined_total ?? 0;
        const deltaCorpus = currentCorpus - this.snapshot.finalCorpus;
        const pct = this.snapshot.finalCorpus > 0 ? (deltaCorpus / this.snapshot.finalCorpus) * 100 : 0;

        const pctEl = this.dom.getElement('scenario-diff-pct');
        const textEl = this.dom.getElement('scenario-diff-text');

        if (pctEl) {
            const sign = pct >= 0 ? '+' : '';
            pctEl.textContent = `${sign}${pct.toFixed(1)}%`;
            if (pct >= 0) {
                pctEl.className = 'text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200';
            } else {
                pctEl.className = 'text-[10px] font-extrabold px-2 py-0.5 rounded bg-rose-100 text-rose-800 border border-rose-200';
            }
        }

        if (textEl) {
            const sign = deltaCorpus >= 0 ? '+' : '-';
            const absDelta = Math.abs(deltaCorpus);
            const deltaFormatted = this.formatter.format(absDelta);

            if (Math.abs(deltaCorpus) < 100) {
                textEl.textContent = 'Current parameters match the saved baseline snapshot.';
            } else if (deltaCorpus > 0) {
                textEl.textContent = `Creates ${deltaFormatted} more wealth (${sign}${pct.toFixed(1)}%) compared to Baseline.`;
            } else {
                textEl.textContent = `Produces ${deltaFormatted} less wealth (${sign}${Math.abs(pct).toFixed(1)}%) compared to Baseline.`;
            }
        }
    }
}
