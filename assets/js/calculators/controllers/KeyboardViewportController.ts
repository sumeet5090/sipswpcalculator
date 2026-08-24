import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * KeyboardViewportController
 * Pins a live micro-corpus preview capsule directly above the mobile virtual keyboard
 * to prevent blind typing during manual numerical entry.
 */
export class KeyboardViewportController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    private capsule: HTMLElement | null = null;
    private corpusEl: HTMLElement | null = null;
    private activeInput: HTMLInputElement | null = null;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;

        this.initDOM();
        this.bindEvents();
    }

    private initDOM(): void {
        this.capsule = this.dom.getElement<HTMLElement>('keyboard-docked-preview');
        this.corpusEl = this.dom.getElement<HTMLElement>('keyboard-preview-corpus');
    }

    private bindEvents(): void {
        if (typeof window === 'undefined') return;

        const inputs = document.querySelectorAll<HTMLInputElement>('#calculator-form input[type="text"]');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                // Only trigger on mobile viewports (<768px)
                if (window.innerWidth >= 768) return;
                this.activeInput = input;
                this.showCapsule();
            });

            input.addEventListener('blur', () => {
                this.activeInput = null;
                this.hideCapsule();
            });
        });

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => this.repositionCapsule());
            window.visualViewport.addEventListener('scroll', () => this.repositionCapsule());
        }
    }

    private showCapsule(): void {
        if (!this.capsule) return;
        this.capsule.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
        this.repositionCapsule();
    }

    private hideCapsule(): void {
        if (!this.capsule) return;
        this.capsule.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            if (!this.activeInput && this.capsule) {
                this.capsule.classList.add('hidden');
            }
        }, 200);
    }

    private repositionCapsule(): void {
        if (!this.capsule || !this.activeInput || !window.visualViewport) return;

        // Position the capsule slightly above the virtual keyboard viewport bottom
        const vv = window.visualViewport;
        const bottomOffset = window.innerHeight - (vv.offsetTop + vv.height);
        this.capsule.style.bottom = `${Math.max(12, bottomOffset + 8)}px`;
    }

    public update(results: YearResult[]): void {
        if (!results || results.length === 0 || !this.corpusEl) return;
        const lastRow = results[results.length - 1];
        this.corpusEl.textContent = this.formatter.format(lastRow.combined_total);
    }
}
