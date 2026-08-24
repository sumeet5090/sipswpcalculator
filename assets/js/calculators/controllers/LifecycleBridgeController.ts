import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';
import type { InvestmentInputs, YearResult } from '../../types';

/**
 * LifecycleBridgeController
 * Seamlessly connects the SIP wealth accumulation phase to the SWP retirement
 * distribution phase with a 1-click morphing transition.
 */
export class LifecycleBridgeController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private onBridgeToSwp: (maturedCorpus: number, safeMonthlyWithdrawal: number) => void;

    private bridgeContainer: HTMLElement | null = null;
    private bridgeCorpusEl: HTMLElement | null = null;
    private bridgeBtn: HTMLButtonElement | null = null;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs,
        onBridgeToSwp: (maturedCorpus: number, safeMonthlyWithdrawal: number) => void
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.onBridgeToSwp = onBridgeToSwp;

        this.initDOM();
        this.bindEvents();
    }

    private initDOM(): void {
        this.bridgeContainer = this.dom.getElement<HTMLElement>('lifecycle-retirement-bridge');
        this.bridgeCorpusEl = this.dom.getElement<HTMLElement>('lifecycle-bridge-corpus');
        this.bridgeBtn = this.dom.getElement<HTMLButtonElement>('bridge-to-swp-btn');
    }

    private bindEvents(): void {
        if (this.bridgeBtn) {
            this.bridgeBtn.addEventListener('click', () => {
                const corpus = Number(this.bridgeBtn?.dataset.maturedCorpus || 0);
                if (corpus > 0) {
                    const inputs = this.getInputs();
                    const safeWithdrawal = MathEngine.calculateSafeSwpWithdrawal(inputs, corpus);
                    this.onBridgeToSwp(corpus, safeWithdrawal > 0 ? safeWithdrawal : Math.round(corpus * 0.005));
                    A11yAnnouncer.announce(`Bridged ₹${this.formatter.format(corpus)} corpus into Systematic Withdrawal Plan.`);
                }
            });
        }
    }

    public update(results: YearResult[]): void {
        if (!this.bridgeContainer) return;

        const inputs = this.getInputs();
        // Only show bridge prompt when in accumulation-only mode with significant wealth created
        if (inputs.enable_swp || !results || results.length === 0) {
            this.bridgeContainer.classList.add('hidden');
            return;
        }

        const lastRow = results[results.length - 1];
        const maturedCorpus = lastRow.combined_total;

        if (maturedCorpus >= 1000000 && inputs.years >= 3) {
            if (this.bridgeCorpusEl) {
                this.bridgeCorpusEl.textContent = this.formatter.format(maturedCorpus);
            }
            if (this.bridgeBtn) {
                this.bridgeBtn.dataset.maturedCorpus = String(maturedCorpus);
            }
            this.bridgeContainer.classList.remove('hidden');
        } else {
            this.bridgeContainer.classList.add('hidden');
        }
    }
}
