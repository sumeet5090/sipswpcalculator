/**
 * RollingOdometerView.ts
 * Implements high-dopamine mechanical rolling tumbler ribbons for currency metrics.
 * Uses GPU-accelerated translate3d transforms on isolated composited layers
 * with zero layout thrashing and full WCAG AAA accessibility parity.
 */

export class RollingOdometerView {
    private element: HTMLElement;
    private container: HTMLElement | null = null;
    private currentSlots: Array<{ type: 'digit' | 'static'; char: string; slotEl: HTMLElement; ribbonEl?: HTMLElement }> = [];
    private isReducedMotion: boolean = false;

    constructor(element: HTMLElement) {
        this.element = element;
        this.checkReducedMotion();
    }

    private checkReducedMotion(): void {
        if (typeof window !== 'undefined' && window.matchMedia) {
            this.isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
    }

    /**
     * Initializes the container and accessibility attributes.
     */
    private ensureContainer(): HTMLElement {
        if (this.container && this.element.contains(this.container)) {
            return this.container;
        }

        this.element.innerHTML = '';
        const container = document.createElement('span');
        container.className = 'odometer-container font-financial-mono inline-flex items-baseline';
        container.setAttribute('aria-hidden', 'true');
        this.element.appendChild(container);
        this.container = container;
        this.currentSlots = [];
        return container;
    }

    /**
     * Updates the display to the given formatted currency string.
     * e.g. "₹ 1,24,56,789"
     */
    public render(formattedText: string, instant: boolean = false): void {
        // Maintain clean screen reader readout
        this.element.setAttribute('aria-label', formattedText);

        if (this.isReducedMotion) {
            this.element.textContent = formattedText;
            return;
        }

        const container = this.ensureContainer();
        const chars = formattedText.split('');

        // Reconcile slots if character structure or count changes
        const needsFullRebuild = this.currentSlots.length !== chars.length ||
            this.currentSlots.some((slot, idx) => {
                const isDigit = /\d/.test(chars[idx]);
                return (slot.type === 'digit') !== isDigit;
            });

        if (needsFullRebuild) {
            container.innerHTML = '';
            this.currentSlots = [];

            chars.forEach(ch => {
                const isDigit = /\d/.test(ch);
                if (isDigit) {
                    const slotEl = document.createElement('span');
                    slotEl.className = 'odometer-digit-slot relative inline-block overflow-hidden';

                    const ribbonEl = document.createElement('span');
                    ribbonEl.className = 'odometer-ribbon flex flex-col';
                    ribbonEl.style.transition = instant ? 'none' : 'transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';

                    for (let i = 0; i <= 9; i++) {
                        const digitEl = document.createElement('span');
                        digitEl.className = 'inline-block text-center';
                        digitEl.textContent = String(i);
                        ribbonEl.appendChild(digitEl);
                    }

                    const digitVal = parseInt(ch, 10);
                    ribbonEl.style.transform = `translate3d(0, -${digitVal * 10}%, 0)`;

                    slotEl.appendChild(ribbonEl);
                    container.appendChild(slotEl);
                    this.currentSlots.push({ type: 'digit', char: ch, slotEl, ribbonEl });
                } else {
                    const slotEl = document.createElement('span');
                    slotEl.className = 'odometer-static inline-block';
                    slotEl.textContent = ch;
                    container.appendChild(slotEl);
                    this.currentSlots.push({ type: 'static', char: ch, slotEl });
                }
            });
            return;
        }

        // Update existing slots smoothly via transform
        chars.forEach((ch, idx) => {
            const slot = this.currentSlots[idx];
            if (slot.type === 'digit' && slot.ribbonEl) {
                const digitVal = parseInt(ch, 10);
                if (instant) {
                    slot.ribbonEl.style.transition = 'none';
                    slot.ribbonEl.style.transform = `translate3d(0, -${digitVal * 10}%, 0)`;
                    // Re-enable transition after synchronous paint
                    requestAnimationFrame(() => {
                        if (slot.ribbonEl) {
                            slot.ribbonEl.style.transition = 'transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
                        }
                    });
                } else {
                    slot.ribbonEl.style.transform = `translate3d(0, -${digitVal * 10}%, 0)`;
                }
                slot.char = ch;
            } else if (slot.type === 'static' && slot.slotEl) {
                if (slot.char !== ch) {
                    slot.slotEl.textContent = ch;
                    slot.char = ch;
                }
            }
        });
    }

    /**
     * Resets or destroys the odometer view.
     */
    public destroy(): void {
        this.container = null;
        this.currentSlots = [];
    }
}
