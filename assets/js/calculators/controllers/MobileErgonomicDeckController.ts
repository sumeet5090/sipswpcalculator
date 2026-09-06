import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * MobileErgonomicDeckController
 * Controls the bottom-anchored thumb command deck on mobile (<768px viewports).
 * Supports directional gesture swipe navigation and tactile haptic feedback.
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

    private startX: number = 0;
    private startY: number = 0;
    private activeDeckIndex: number = 0;
    private readonly totalDecks: number = 3;

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
        this.bindGestureSwiping();
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

    /**
     * Bind horizontal swipe gestures with directional slope lock.
     */
    private bindGestureSwiping(): void {
        const deckContainer = this.dom.getElement('mobile-thumb-cockpit') || document.querySelector('.mobile-cockpit-deck');
        if (!deckContainer) return;

        deckContainer.addEventListener('touchstart', (e: TouchEvent) => {
            const touch = e.touches[0];
            this.startX = touch.clientX;
            this.startY = touch.clientY;
        }, { passive: true });

        deckContainer.addEventListener('touchend', (e: TouchEvent) => {
            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - this.startX;
            const deltaY = touch.clientY - this.startY;

            // Directional slope lock: only trigger if swipe is predominantly horizontal
            if (Math.abs(deltaX) > 45 && Math.abs(deltaX) / (Math.abs(deltaY) || 1) > 1.4) {
                if (deltaX < 0 && this.activeDeckIndex < this.totalDecks - 1) {
                    this.setDeckIndex(this.activeDeckIndex + 1);
                } else if (deltaX > 0 && this.activeDeckIndex > 0) {
                    this.setDeckIndex(this.activeDeckIndex - 1);
                }
            }
        }, { passive: true });
    }

    /**
     * Switch the active horizontal parameter deck.
     */
    public setDeckIndex(index: number): void {
        this.activeDeckIndex = Math.max(0, Math.min(this.totalDecks - 1, index));

        if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
            try {
                navigator.vibrate(8);
            } catch {}
        }

        // Update deck indicator dots if present
        const indicators = this.dom.getElements<HTMLElement>('.deck-indicator-dot');
        indicators.forEach((dot, idx) => {
            if (idx === this.activeDeckIndex) {
                dot.classList.add('bg-emerald-600', 'w-4');
                dot.classList.remove('bg-slate-300', 'w-1.5');
            } else {
                dot.classList.remove('bg-emerald-600', 'w-4');
                dot.classList.add('bg-slate-300', 'w-1.5');
            }
        });
    }

    public getActiveDeckIndex(): number {
        return this.activeDeckIndex;
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

