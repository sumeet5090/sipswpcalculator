import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { RollingOdometerView } from '../views/RollingOdometerView';

interface AnimationState {
    startVal: number;
    targetVal: number;
    startTime: number;
    duration: number;
    rafId: number;
}

export class OdometerController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private animations: Map<string, AnimationState> = new Map();
    private views: Map<string, RollingOdometerView> = new Map();
    private forceInstant: boolean = false;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    /**
     * Force immediate non-animated settling (used for initial hydration and headless snapshots).
     */
    public setInstant(instant: boolean): void {
        this.forceInstant = instant;
    }

    /**
     * Get or create a RollingOdometerView for the given element.
     */
    private getOdometerView(elementId: string, el: HTMLElement): RollingOdometerView {
        let view = this.views.get(elementId);
        if (!view) {
            view = new RollingOdometerView(el);
            this.views.set(elementId, view);
        }
        return view;
    }

    /**
     * Update dynamic light ambient pastel aura based on corpus value.
     */
    private updateAmbientAura(elementId: string, value: number): void {
        if (elementId !== 'summary-corpus') return;

        const card = this.dom.getElement(elementId)?.closest('.fintech-glass-card, [class*="rounded-2xl"], [class*="rounded-3xl"]');
        if (!card) return;

        card.classList.remove('aurora-seed', 'aurora-scale', 'aurora-sovereign');
        if (value >= 10000000) {
            card.classList.add('aurora-sovereign');
        } else if (value >= 2500000) {
            card.classList.add('aurora-scale');
        } else if (value > 0) {
            card.classList.add('aurora-seed');
        }
    }

    /**
     * Render formatted text onto target metric element with automatic container-fitting and zero layout thrashing.
     */
    private renderFormattedText(elementId: string, el: HTMLElement, text: string, instant: boolean = false): void {
        const len = text.length;

        // Only assign hero length classes to standalone widgets, not to subline precision metrics
        if (!elementId.startsWith('summary-')) {
            el.classList.remove('metric-len-normal', 'metric-len-medium', 'metric-len-long', 'metric-len-huge');
            if (len <= 11) {
                el.classList.add('metric-len-normal');
            } else if (len <= 13) {
                el.classList.add('metric-len-medium');
            } else if (len <= 15) {
                el.classList.add('metric-len-long');
            } else {
                el.classList.add('metric-len-huge');
            }
        }

        // 2. Delegate rendering to GPU-accelerated tumbler view
        const view = this.getOdometerView(elementId, el);
        view.render(text, instant);
    }

    /**
     * Smoothly animate a number on a target element using cubic ease-out physics.
     */
    animateValue(elementId: string, targetVal: number, durationMs: number = 400): void {
        const el = this.dom.getElement(elementId);
        if (!el) return;

        // Update ambient aura based on the target value
        this.updateAmbientAura(elementId, targetVal);

        // Update companion compact denomination headline and badge if present (e.g. ₹1.20 Cr, ₹40.74 L)
        const updateCompanionDisplays = (val: number) => {
            const compactText = val > 0 ? this.formatter.formatCompact(val) : this.formatter.format(0);

            const headlineEl = this.dom.getElement(`${elementId}-headline`);
            if (headlineEl) {
                headlineEl.textContent = compactText;
            }

            const compactEl = this.dom.getElement(`${elementId}-compact`);
            if (compactEl) {
                if (val > 0) {
                    compactEl.textContent = compactText;
                    compactEl.style.display = '';
                } else {
                    compactEl.style.display = 'none';
                }
            }
        };

        updateCompanionDisplays(targetVal);

        // Fast-forward immediate rendering for initial hydration, headless screenshots, or reduced motion
        const isHeadlessOrStatic = (typeof window !== 'undefined' && (
            Boolean((window.navigator as unknown as { webdriver?: boolean })?.webdriver) ||
            window.location.search.includes('static=1') ||
            window.location.search.includes('snapshot=1') ||
            this.forceInstant
        ));

        if (durationMs === 0 || isHeadlessOrStatic) {
            this.renderFormattedText(elementId, el, this.formatter.format(targetVal), true);
            updateCompanionDisplays(targetVal);
            el.dataset.rawVal = String(targetVal);
            this.animations.delete(elementId);
            return;
        }

        // Cancel any in-flight animation for this element
        const existing = this.animations.get(elementId);
        let currentVal = targetVal;

        if (existing) {
            cancelAnimationFrame(existing.rafId);
            const elapsed = performance.now() - existing.startTime;
            const progress = Math.min(elapsed / existing.duration, 1);
            const easeProgress = 1 - Math.pow(1 - progress, 4); // easeOutQuart
            currentVal = existing.startVal + (existing.targetVal - existing.startVal) * easeProgress;
        } else {
            const rawStored = el.dataset.rawVal;
            currentVal = rawStored ? parseFloat(rawStored) : targetVal;
        }

        // If delta is negligible, set directly
        if (Math.abs(targetVal - currentVal) < 1) {
            this.renderFormattedText(elementId, el, this.formatter.format(targetVal), true);
            updateCompanionDisplays(targetVal);
            el.dataset.rawVal = String(targetVal);
            this.animations.delete(elementId);
            return;
        }

        const startTime = performance.now();
        const startVal = currentVal;

        const frame = (now: number) => {
            const elapsed = now - startTime;
            const progress = Math.min(Math.max(elapsed / durationMs, 0), 1);
            const easeProgress = 1 - Math.pow(1 - progress, 4); // easeOutQuart
            const val = startVal + (targetVal - startVal) * easeProgress;
            const roundedVal = Math.round(val);

            this.renderFormattedText(elementId, el, this.formatter.format(roundedVal), true);
            updateCompanionDisplays(roundedVal);
            el.dataset.rawVal = String(targetVal);

            if (progress < 1) {
                const rafId = requestAnimationFrame(frame);
                this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
            } else {
                this.renderFormattedText(elementId, el, this.formatter.format(targetVal), false);
                updateCompanionDisplays(targetVal);
                this.animations.delete(elementId);
            }
        };

        const rafId = requestAnimationFrame(frame);
        this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
    }
}

