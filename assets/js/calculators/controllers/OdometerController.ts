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
     * Render formatted text onto target metric element with automatic container-fitting and zero-overflow font scaling.
     */
    private renderFormattedText(el: HTMLElement, text: string): void {
        el.textContent = text;
        const len = text.length;

        // 1. Assign calibrated length classes
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

        // 2. Exact fit verification against parent card bounding box
        const parent = el.parentElement;
        if (parent) {
            const cs = window.getComputedStyle(parent);
            const availableW = parent.clientWidth - parseFloat(cs.paddingLeft || '0') - parseFloat(cs.paddingRight || '0');
            const textW = el.scrollWidth;
            if (textW > availableW && availableW > 0) {
                const currentFontSize = parseFloat(window.getComputedStyle(el).fontSize || '16');
                const scaledFont = Math.floor((availableW / textW) * currentFontSize * 0.96);
                el.style.fontSize = `${Math.max(scaledFont, 9)}px`;
                el.style.letterSpacing = '-0.04em';
            } else {
                el.style.fontSize = '';
                el.style.letterSpacing = '';
            }
        }
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
            this.renderFormattedText(el, this.formatter.format(targetVal));
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

            this.renderFormattedText(el, this.formatter.format(Math.round(val)));
            el.dataset.rawVal = String(targetVal);

            if (progress < 1) {
                const rafId = requestAnimationFrame(frame);
                this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
            } else {
                this.renderFormattedText(el, this.formatter.format(targetVal));
                this.animations.delete(elementId);
            }
        };

        const rafId = requestAnimationFrame(frame);
        this.animations.set(elementId, { startVal, targetVal, startTime, duration: durationMs, rafId });
    }
}
