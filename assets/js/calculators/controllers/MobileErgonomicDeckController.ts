import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * MobileErgonomicDeckController
 * Controls the bottom-anchored thumb command deck on mobile (<768px viewports).
 */
export class MobileErgonomicDeckController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private onSwitchMode: (mode: 'sip' | 'swp') => void;
    private onShareProposal: () => void;

    private deckCorpusEl: HTMLElement | null = null;
    private btnSip: HTMLButtonElement | null = null;
    private btnSwp: HTMLButtonElement | null = null;
    private shareBtn: HTMLButtonElement | null = null;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        onSwitchMode: (mode: 'sip' | 'swp') => void,
        onShareProposal: () => void
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.onSwitchMode = onSwitchMode;
        this.onShareProposal = onShareProposal;

        this.initDOM();
        this.bindEvents();
    }

    private initDOM(): void {
        this.deckCorpusEl = this.dom.getElement<HTMLElement>('mobile-deck-corpus-val');
        this.btnSip = this.dom.getElement<HTMLButtonElement>('mobile-deck-sip-btn');
        this.btnSwp = this.dom.getElement<HTMLButtonElement>('mobile-deck-swp-btn');
        this.shareBtn = this.dom.getElement<HTMLButtonElement>('mobile-deck-share-btn');
    }

    private bindEvents(): void {
        if (this.btnSip) {
            this.btnSip.addEventListener('click', () => {
                this.setActiveTab('sip');
                this.onSwitchMode('sip');
            });
        }
        if (this.btnSwp) {
            this.btnSwp.addEventListener('click', () => {
                this.setActiveTab('swp');
                this.onSwitchMode('swp');
            });
        }
        if (this.shareBtn) {
            this.shareBtn.addEventListener('click', () => {
                this.onShareProposal();
            });
        }
    }

    public setActiveTab(mode: 'sip' | 'swp'): void {
        if (mode === 'sip') {
            this.btnSip?.classList.add('bg-white', 'text-emerald-700', 'shadow-2xs');
            this.btnSip?.classList.remove('text-slate-600');
            this.btnSwp?.classList.remove('bg-white', 'text-emerald-700', 'shadow-2xs');
            this.btnSwp?.classList.add('text-slate-600');
        } else {
            this.btnSwp?.classList.add('bg-white', 'text-rose-700', 'shadow-2xs');
            this.btnSwp?.classList.remove('text-slate-600');
            this.btnSip?.classList.remove('bg-white', 'text-emerald-700', 'shadow-2xs');
            this.btnSip?.classList.add('text-slate-600');
        }
    }

    public update(results: YearResult[]): void {
        if (!results || results.length === 0) return;
        const lastRow = results[results.length - 1];
        if (this.deckCorpusEl) {
            this.deckCorpusEl.textContent = this.formatter.format(lastRow.combined_total);
        }
    }
}
