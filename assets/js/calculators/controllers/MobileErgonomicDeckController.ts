import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { WebHapticEngine } from '../helpers/WebHapticEngine';
import type { YearResult, InvestmentInputs } from '../../types';

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
    private deckInvestedEl: HTMLElement | null = null;
    private deckGainsEl: HTMLElement | null = null;
    private deckCashflowEl: HTMLElement | null = null;
    private deckCashflowLabelEl: HTMLElement | null = null;
    private btnSip: HTMLButtonElement | null = null;
    private btnSwp: HTMLButtonElement | null = null;
    private shareBtn: HTMLButtonElement | null = null;

    private startX: number = 0;
    private startY: number = 0;
    private activeDeckIndex: number = 0;
    private readonly totalDecks: number = 3;
    private lastCorpus: number = 0;

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
        this.initScrollAutoVisibility();
    }

    private initDOM(): void {
        this.deckCorpusEl = this.dom.getElement<HTMLElement>('dock-glance-corpus') || this.dom.getElement<HTMLElement>('mobile-deck-corpus-val');
        this.deckInvestedEl = this.dom.getElement<HTMLElement>('dock-glance-invested');
        this.deckGainsEl = this.dom.getElement<HTMLElement>('dock-glance-gains');
        this.deckCashflowEl = this.dom.getElement<HTMLElement>('dock-glance-cashflow');
        this.deckCashflowLabelEl = this.dom.getElement<HTMLElement>('dock-glance-cashflow-label');
        this.btnSip = this.dom.getElement<HTMLButtonElement>('mobile-deck-sip-btn');
        this.btnSwp = this.dom.getElement<HTMLButtonElement>('mobile-deck-swp-btn');
        this.shareBtn = this.dom.getElement<HTMLButtonElement>('mobile-deck-share-btn');

        const appEl = this.dom.getElement('calculator-app');
        if (appEl?.dataset?.mode === 'swp') {
            this.setActiveTab('swp');
        }
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
     * Auto-hide mobile bottom dock once the user reaches the KPI Scoreboard / Chart section.
     */
    private initScrollAutoVisibility(): void {
        if (typeof window === 'undefined' || typeof IntersectionObserver === 'undefined') return;
        const dock = this.dom.getElement('mobile-action-dock');
        const targetSection = document.getElementById('chart-visualization') || document.getElementById('calculator-section');
        if (!dock || !targetSection) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                // When chart / KPI section enters viewport, slide the dock out smoothly
                if (entry.isIntersecting) {
                    dock.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
                } else {
                    const rect = targetSection.getBoundingClientRect();
                    // If target section is below viewport (user is in the sliders area above)
                    if (rect.top > 0) {
                        dock.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
                    } else {
                        // If scrolled past into bottom tables/content
                        dock.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
                    }
                }
            });
        }, {
            threshold: 0.15
        });

        observer.observe(targetSection);
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
            this.btnSip?.classList.add('bg-emerald-600', 'text-white', 'shadow-flat');
            this.btnSip?.classList.remove('bg-slate-200/80', 'text-slate-600');
            this.btnSwp?.classList.remove('bg-rose-600', 'text-white', 'shadow-flat');
            this.btnSwp?.classList.add('bg-slate-200/80', 'text-slate-600');
        } else {
            this.btnSwp?.classList.add('bg-rose-600', 'text-white', 'shadow-flat');
            this.btnSwp?.classList.remove('bg-slate-200/80', 'text-slate-600');
            this.btnSip?.classList.remove('bg-emerald-600', 'text-white', 'shadow-flat');
            this.btnSip?.classList.add('bg-slate-200/80', 'text-slate-600');
        }
    }

    public update(results: YearResult[], inputs?: InvestmentInputs): void {
        if (!results || results.length === 0) return;
        const appEl = this.dom.getElement('calculator-app');
        const mode = appEl?.dataset?.mode;

        const lastRow = results[results.length - 1];
        const newCorpus = lastRow.combined_total;
        const invested = lastRow.cumulative_invested;
        const gains = Math.max(0, (newCorpus + (lastRow.cumulative_withdrawals ?? 0)) - invested);

        if (mode === 'emi') {
            const totalInterestPaid = results.reduce((acc, r) => acc + (r.interest ?? 0), 0);
            if (this.deckCorpusEl) this.deckCorpusEl.textContent = `${this.formatter.format(lastRow.sip_monthly ?? 0)}/mo`;
            if (this.deckInvestedEl) this.deckInvestedEl.textContent = this.formatter.formatDynamic(invested);
            if (this.deckGainsEl) this.deckGainsEl.textContent = this.formatter.formatDynamic(totalInterestPaid);
        } else if (mode === 'inflation') {
            const costIncrease = Math.max(0, newCorpus - invested);
            if (this.deckCorpusEl) this.deckCorpusEl.textContent = this.formatter.format(newCorpus);
            if (this.deckInvestedEl) this.deckInvestedEl.textContent = this.formatter.formatDynamic(invested);
            if (this.deckGainsEl) this.deckGainsEl.textContent = `+${this.formatter.formatDynamic(costIncrease)}`;
        } else {
            if (this.deckCorpusEl) this.deckCorpusEl.textContent = this.formatter.format(newCorpus);
            if (this.deckInvestedEl) this.deckInvestedEl.textContent = this.formatter.formatDynamic(invested);
            if (this.deckGainsEl) this.deckGainsEl.textContent = `+${this.formatter.formatDynamic(gains)}`;
        }

        if (this.deckCashflowEl) {
            if (mode === 'emi') {
                const emiEl = this.dom.getElement('summary-corpus');
                const emiVal = emiEl?.textContent?.trim() || '₹ --';
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'EMI/mo';
                this.deckCashflowEl.textContent = emiVal;
                this.deckCashflowEl.className = 'text-ui-xs font-black text-rose-700 font-financial-mono tabular-nums truncate';
            } else if (mode === 'ppf') {
                const deposit = parseFloat(this.dom.getValue('ppf_deposit') || '0');
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Deposit';
                this.deckCashflowEl.textContent = deposit > 0 ? `${this.formatter.formatDynamic(deposit)}/yr` : '₹ --';
                this.deckCashflowEl.className = 'text-ui-xs font-black text-emerald-800 font-financial-mono tabular-nums truncate';
            } else if (mode === 'fd') {
                const principal = parseFloat(this.dom.getValue('fd_principal') || '0');
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Deposit';
                this.deckCashflowEl.textContent = principal > 0 ? this.formatter.formatDynamic(principal) : '₹ --';
                this.deckCashflowEl.className = 'text-ui-xs font-black text-slate-800 font-financial-mono tabular-nums truncate';
            } else if (mode === 'cagr') {
                const initial = parseFloat(this.dom.getValue('cagr_initial') || '0');
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Initial';
                this.deckCashflowEl.textContent = initial > 0 ? this.formatter.formatDynamic(initial) : '₹ --';
                this.deckCashflowEl.className = 'text-ui-xs font-black text-emerald-800 font-financial-mono tabular-nums truncate';
            } else if (mode === 'inflation') {
                const cost = parseFloat(this.dom.getValue('inf_amount') || '0');
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Today';
                this.deckCashflowEl.textContent = cost > 0 ? this.formatter.formatDynamic(cost) : '₹ --';
                this.deckCashflowEl.className = 'text-ui-xs font-black text-slate-800 font-financial-mono tabular-nums truncate';
            } else if (mode === 'compound_interest') {
                const ciPrincipal = parseFloat(this.dom.getValue('ci_principal') || '0');
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Principal';
                this.deckCashflowEl.textContent = ciPrincipal > 0 ? this.formatter.formatDynamic(ciPrincipal) : '₹ --';
                this.deckCashflowEl.className = 'text-ui-xs font-black text-slate-800 font-financial-mono tabular-nums truncate';
            } else if (inputs && inputs.enable_swp && inputs.swp_withdrawal > 0) {
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'SWP Out';
                this.deckCashflowEl.textContent = `+${this.formatter.formatDynamic(inputs.swp_withdrawal)}/m`;
                this.deckCashflowEl.className = 'text-ui-xs font-black text-rose-700 font-financial-mono tabular-nums truncate';
            } else if (inputs && inputs.sip > 0) {
                if (this.deckCashflowLabelEl) {
                    this.deckCashflowLabelEl.textContent = (mode === 'target_corpus' || mode === 'target') ? 'Req. SIP' : 'SIP In';
                }
                this.deckCashflowEl.textContent = `${this.formatter.formatDynamic(inputs.sip)}/m`;
                this.deckCashflowEl.className = 'text-ui-xs font-black text-emerald-800 font-financial-mono tabular-nums truncate';
            } else if (inputs && inputs.lumpsum > 0) {
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Lumpsum';
                this.deckCashflowEl.textContent = this.formatter.formatDynamic(inputs.lumpsum);
                this.deckCashflowEl.className = 'text-ui-xs font-black text-slate-800 font-financial-mono tabular-nums truncate';
            } else {
                if (this.deckCashflowLabelEl) this.deckCashflowLabelEl.textContent = 'Cashflow';
                this.deckCashflowEl.textContent = '₹ --';
            }
        }

        if (this.lastCorpus > 0) {
            WebHapticEngine.checkCorpusMilestone(this.lastCorpus, newCorpus);
        }
        this.lastCorpus = newCorpus;
    }
}

