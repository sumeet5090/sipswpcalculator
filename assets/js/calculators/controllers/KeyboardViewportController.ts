import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';

/**
 * KeyboardViewportController
 * Controls the floating keyboard accessory preview bar on mobile viewports (<768px).
 * Eliminates blind typing, provides iOS numpad dismissal ('Done'), and prevents
 * bottom dock layout thrashing during virtual keyboard transitions.
 */
export class KeyboardViewportController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    private capsule: HTMLElement | null = null;
    private corpusEl: HTMLElement | null = null;
    private doneBtn: HTMLButtonElement | null = null;
    private prevBtn: HTMLButtonElement | null = null;
    private nextBtn: HTMLButtonElement | null = null;
    private actionDock: HTMLElement | null = null;
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
        this.doneBtn = this.dom.getElement<HTMLButtonElement>('keyboard-done-btn');
        this.prevBtn = this.dom.getElement<HTMLButtonElement>('keyboard-prev-input');
        this.nextBtn = this.dom.getElement<HTMLButtonElement>('keyboard-next-input');
        this.actionDock = this.dom.getElement<HTMLElement>('mobile-action-dock');
    }

    private bindEvents(): void {
        if (typeof window === 'undefined') return;

        const inputs = this.getVisibleInputs();
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                if (window.innerWidth >= 768) return;
                this.activeInput = input;
                this.showCapsule();

                // Prevent sticky header occlusion by scrolling input into comfortable center view
                setTimeout(() => {
                    if (this.activeInput === input) {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 280);
            });

            input.addEventListener('blur', () => {
                // Defer hide so clicking 'Done' or 'Next' inside the capsule registers
                setTimeout(() => {
                    if (document.activeElement !== this.activeInput && !document.activeElement?.closest('#keyboard-docked-preview')) {
                        this.activeInput = null;
                        this.hideCapsule();
                    }
                }, 150);
            });
        });

        if (this.doneBtn) {
            this.doneBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.activeInput) {
                    this.activeInput.blur();
                }
                this.activeInput = null;
                this.hideCapsule();
            });
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateInput(1);
            });
        }

        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateInput(-1);
            });
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => this.repositionCapsule());
            window.visualViewport.addEventListener('scroll', () => this.repositionCapsule());
        }
    }

    private getVisibleInputs(): HTMLInputElement[] {
        const inputs = Array.from(document.querySelectorAll<HTMLInputElement>('#calculator-form input[type="text"]'));
        return inputs.filter(inp => {
            const style = window.getComputedStyle(inp);
            return style.display !== 'none' && style.visibility !== 'hidden' && !inp.disabled;
        });
    }

    private navigateInput(direction: number): void {
        const inputs = this.getVisibleInputs();
        if (inputs.length === 0) return;

        let currentIndex = this.activeInput ? inputs.indexOf(this.activeInput) : -1;
        let nextIndex = currentIndex + direction;

        if (nextIndex < 0) nextIndex = inputs.length - 1;
        if (nextIndex >= inputs.length) nextIndex = 0;

        const target = inputs[nextIndex];
        if (target) {
            target.focus();
            target.select();
        }
    }

    private showCapsule(): void {
        if (!this.capsule) return;
        this.capsule.classList.remove('hidden');
        requestAnimationFrame(() => {
            if (this.capsule) {
                this.capsule.classList.remove('opacity-0', 'pointer-events-none');
            }
        });
        this.repositionCapsule();
    }

    private hideCapsule(): void {
        if (!this.capsule) return;
        this.capsule.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            if (!this.activeInput && this.capsule) {
                this.capsule.classList.add('hidden');
            }
        }, 220);

        if (this.actionDock) {
            this.actionDock.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
        }
    }

    private repositionCapsule(): void {
        if (!this.capsule || !this.activeInput || !window.visualViewport) return;

        const vv = window.visualViewport;
        const bottomOffset = Math.max(0, window.innerHeight - (vv.offsetTop + vv.height));
        const isKeyboardOpen = bottomOffset > 120;

        // Position the capsule slightly above the virtual keyboard viewport bottom
        this.capsule.style.bottom = `${Math.max(12, bottomOffset + 8)}px`;
        document.documentElement.style.setProperty('--keyboard-offset', `${bottomOffset}px`);

        if (this.actionDock) {
            if (isKeyboardOpen) {
                this.actionDock.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
            } else {
                this.actionDock.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
            }
        }
    }

    public update(results: YearResult[]): void {
        if (!results || results.length === 0 || !this.corpusEl) return;
        const lastRow = results[results.length - 1];
        this.corpusEl.textContent = this.formatter.format(lastRow.combined_total);
    }
}
