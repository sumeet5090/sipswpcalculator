import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult } from '../../types';

/**
 * FloatingHudController.ts
 * Manages the mobile-only sticky mini-HUD.
 * Uses IntersectionObserver to glide into view beneath the navbar when
 * primary summary KPI cards scroll off-screen, maintaining the zero-latency
 * visual feedback loop during single-handed mobile usage.
 */
export class FloatingHudController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private observer: IntersectionObserver | null = null;

    constructor(
        dom: DOMAdapter = new DOMAdapter(),
        formatter: CurrencyFormatter = new CurrencyFormatter()
    ) {
        this.dom = dom;
        this.formatter = formatter;
    }

    public init(): void {
        if (typeof window === 'undefined' || !('IntersectionObserver' in window)) return;

        const targetCard = this.dom.getElement('summary-corpus')?.closest('.grid');
        const hud = this.dom.getElement('mobile-sticky-mini-hud');
        if (!targetCard || !hud) return;

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                // If top summary cards are scrolled above the viewport on mobile
                const isOffScreenAbove = !entry.isIntersecting && entry.boundingClientRect.top < 80;
                if (isOffScreenAbove) {
                    hud.classList.remove('-translate-y-full', 'opacity-0', 'pointer-events-none');
                    hud.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
                } else {
                    hud.classList.add('-translate-y-full', 'opacity-0', 'pointer-events-none');
                    hud.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
                }
            });
        }, {
            threshold: [0, 0.1],
            rootMargin: '-64px 0px 0px 0px'
        });

        this.observer.observe(targetCard);
    }

    public updateResults(results: YearResult[]): void {
        if (!results || results.length === 0) return;
        const lastRow = results[results.length - 1];
        const corpus = lastRow.combined_total;
        const invested = lastRow.cumulative_invested;
        const gains = Math.max(0, corpus - invested);

        const corpusEl = this.dom.getElement('mini-hud-corpus');
        const gainEl = this.dom.getElement('mini-hud-gain');

        if (corpusEl) {
            corpusEl.textContent = this.formatter.formatDynamic(corpus);
        }
        if (gainEl) {
            gainEl.textContent = `+${this.formatter.formatDynamic(gains)}`;
        }
    }

    public destroy(): void {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }
}
