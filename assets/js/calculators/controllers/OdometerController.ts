import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';

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

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    /**
     * Smoothly animate a number on a target element using cubic ease-out physics.
     */
    animateValue(elementId: string, targetVal: number, durationMs: number = 400): void {
        const el = this.dom.getElement(elementId);
        if (!el) return;

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
            el.textContent = this.formatter.format(targetVal);
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

            el.textContent = this.formatter.format(Math.round(val));
            el.dataset.rawVal = String(targetVal);

            if (progress < 1) {
                const rafId = requestAnimationFrame(frame);
                this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
            } else {
                el.textContent = this.formatter.format(targetVal);
                this.animations.delete(elementId);
            }
        };

        const rafId = requestAnimationFrame(frame);
        this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
    }
}
