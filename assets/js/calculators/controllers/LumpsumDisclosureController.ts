import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer.ts';

/**
 * LumpsumDisclosureController.ts
 * Manages Slot 1B progressive disclosure for optional seed capital (#lumpsum)
 * below Monthly SIP while enforcing the invariant:
 * Expanded = Active, Collapsed = ₹0.
 */
export class LumpsumDisclosureController {
    private dom: DOMAdapter;
    private onResetToZero: () => void;
    private toggleBtn: HTMLButtonElement | null = null;
    private drawer: HTMLElement | null = null;
    private removeBtn: HTMLButtonElement | null = null;
    private inputEl: HTMLInputElement | null = null;
    private initialized: boolean = false;

    constructor(dom: DOMAdapter, onResetToZero: () => void) {
        this.dom = dom;
        this.onResetToZero = onResetToZero;
    }

    public init(): void {
        if (this.initialized) return;

        this.toggleBtn = this.dom.getElement<HTMLButtonElement>('lumpsum-toggle-btn');
        this.drawer = this.dom.getElement<HTMLElement>('lumpsum-drawer');
        this.removeBtn = this.dom.getElement<HTMLButtonElement>('lumpsum-remove-btn');
        this.inputEl = this.dom.getElement<HTMLInputElement>('lumpsum');

        if (!this.toggleBtn || !this.drawer) {
            return;
        }

        this.initialized = true;

        this.toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.expand(true);
        });

        if (this.removeBtn) {
            this.removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetAndCollapse();
            });
        }

        this.drawer.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key !== 'Escape') return;
            const currentVal = parseFloat(this.inputEl?.value || '0') || 0;
            if (currentVal <= 0 || document.activeElement === this.removeBtn) {
                e.preventDefault();
                this.resetAndCollapse();
            }
        });

        const initialVal = parseFloat(this.inputEl?.value || '0') || 0;
        if (initialVal > 0) {
            this.expand(false);
        } else {
            this.collapse(false);
        }
    }

    public isExpanded(): boolean {
        return Boolean(this.drawer && !this.drawer.classList.contains('hidden'));
    }

    public expand(shouldFocus: boolean = false): void {
        if (!this.toggleBtn || !this.drawer) return;

        const wasCollapsed = this.drawer.classList.contains('hidden');
        this.toggleBtn.classList.add('hidden');
        this.toggleBtn.setAttribute('aria-expanded', 'true');
        this.drawer.classList.remove('hidden');
        this.drawer.setAttribute('aria-hidden', 'false');

        if (wasCollapsed && shouldFocus) {
            if (this.inputEl) {
                requestAnimationFrame(() => {
                    if (!this.inputEl) return;
                    this.inputEl.focus();
                    try {
                        this.inputEl.select();
                    } catch {
                        // Silent fallback when selection is unsupported
                    }
                });
            }
            A11yAnnouncer.announce('Initial Lumpsum seed capital field expanded');
        }
    }

    public collapse(shouldFocusToggle: boolean = false): void {
        if (!this.toggleBtn || !this.drawer) return;

        this.drawer.classList.add('hidden');
        this.drawer.setAttribute('aria-hidden', 'true');
        this.toggleBtn.classList.remove('hidden');
        this.toggleBtn.setAttribute('aria-expanded', 'false');

        if (shouldFocusToggle) {
            requestAnimationFrame(() => {
                this.toggleBtn?.focus();
            });
        }
    }

    public resetAndCollapse(): void {
        this.collapse(true);
        this.onResetToZero();
        A11yAnnouncer.announce('Initial Lumpsum reset to ₹0 and collapsed');
    }

    public syncForValue(value: number, programmaticReset: boolean = false): void {
        if (!this.toggleBtn || !this.drawer) return;

        if (value > 0) {
            this.expand(false);
        } else if (programmaticReset) {
            this.collapse(false);
        }
    }
}

