import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { CurrencyFormatter } from './CurrencyHelper';
import { IndianNumberParser } from './helpers/IndianNumberParser';
import {
    computeElasticMax,
    formatSliderTooltipText,
    updateTrackProgress,
    updateSubtext,
    updateWordBadge,
    updatePresetChips,
    updateSliderAria,
    showFieldError,
    clearFieldError
} from './helpers/SliderUiHelper';

interface SliderPair {
    input: HTMLInputElement;
    range: HTMLInputElement;
    fieldId: string;
    defaultSliderMax: number;
    initialDefaultValue: number;
}

/**
 * SliderManager.ts
 * Coordinates range slider ↔ input synchronization, elastic bounds, keyboard shortcuts, and visual UI helpers.
 */
export class SliderManager {
    private static globalTooltipListenersInitialized = false;

    private static initGlobalTooltipDismissal(): void {
        if (typeof window === 'undefined' || SliderManager.globalTooltipListenersInitialized) return;
        SliderManager.globalTooltipListenersInitialized = true;

        const hideAllTooltips = () => {
            document.querySelectorAll('.calc-slider-tooltip.is-active, .slider-floating-tooltip.is-active').forEach(el => {
                el.classList.remove('is-active');
            });
        };

        window.addEventListener('pointerup', hideAllTooltips);
        window.addEventListener('touchend', hideAllTooltips, { passive: true });
        window.addEventListener('touchcancel', hideAllTooltips, { passive: true });
    }

    private triggerFn: (options?: { isDragging?: boolean }) => void;
    private validator: InputValidator;
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private pairs: SliderPair[] = [];
    private _inputDebounceTimer: ReturnType<typeof setTimeout> | null = null;
    private _tooltipDismissTimer: ReturnType<typeof setTimeout> | null = null;
    private _rafTriggerId: number | null = null;
    private _lastHapticTime: number = 0;
    private isInternalSyncing: boolean = false;
    private _isDragging: boolean = false;
    private _onFieldResetToDefault?: (fieldId: string, defaultVal: number) => void;

    constructor(
        triggerFn: (options?: { isDragging?: boolean }) => void,
        validator: InputValidator,
        dom: DOMAdapter = new DOMAdapter(),
        formatter: CurrencyFormatter = new CurrencyFormatter()
    ) {
        this.triggerFn = triggerFn;
        this.validator = validator;
        this.dom = dom;
        this.formatter = formatter;
    }

    public getIsDragging(): boolean {
        return this._isDragging;
    }

    public setTriggerFn(fn: (options?: { isDragging?: boolean }) => void): void {
        this.triggerFn = fn;
    }

    public setOnFieldResetToDefault(callback: (fieldId: string, defaultVal: number) => void): void {
        this._onFieldResetToDefault = callback;
    }

    public adjustSliderBoundary(fieldId: string, enteredValue: number): void {
        const pair = this.pairs.find(p => p.fieldId === fieldId);
        if (!pair) return;
        const newMax = computeElasticMax(enteredValue, pair.defaultSliderMax);
        pair.range.max = String(newMax);
        pair.range.setAttribute('aria-valuemax', String(newMax));
        updateTrackProgress(pair.range);
    }

    /**
     * Register and wire a single input ↔ range pair.
     */
    sync(inputId: string, rangeId: string): void {
        const input = this.dom.getElement<HTMLInputElement>(inputId);
        const range = this.dom.getElement<HTMLInputElement>(rangeId);
        if (!input || !range) return;

        const defaultSliderMax = parseFloat(range.getAttribute('max') || '100000');
        const initialDefaultValue = parseFloat(input.value) || 0;

        this.pairs.push({ input, range, fieldId: inputId, defaultSliderMax, initialDefaultValue });

        // Double-click & double-tap label reset
        const labelEl = this.dom.getElement(`${inputId}_label`) || document.querySelector(`label[for="${inputId}"]`);
        if (labelEl) {
            labelEl.addEventListener('dblclick', (e) => {
                e.preventDefault();
                this.updateFieldValue(inputId, initialDefaultValue);
                this._onFieldResetToDefault?.(inputId, initialDefaultValue);
            });

            let lastTap = 0;
            labelEl.addEventListener('touchend', (e) => {
                const now = Date.now();
                if (now - lastTap < 350 && now - lastTap > 0) {
                    e.preventDefault();
                    this.updateFieldValue(inputId, initialDefaultValue);
                    this._onFieldResetToDefault?.(inputId, initialDefaultValue);
                    lastTap = 0;
                } else {
                    lastTap = now;
                }
            });
        }

        // Initialize preset chips for this field
        this._initPresetChips(inputId);

        // Initial visual sync
        const initialVal = parseFloat(input.value) || 0;
        if (initialVal > defaultSliderMax) {
            const elasticMax = computeElasticMax(initialVal, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));
        }
        this._refreshPairVisuals(inputId, range, initialVal);

        // Dynamic Floating Thumb Tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'slider-floating-tooltip';
        tooltip.id = `${rangeId}_tooltip`;
        if (range.parentElement) {
            range.parentElement.style.position = 'relative';
            range.parentElement.appendChild(tooltip);
        }

        const showTooltip = (val: number) => {
            const min = parseFloat(range.min) || 0;
            const max = parseFloat(range.max) || 100;
            const pct = max > min ? ((val - min) / (max - min)) * 100 : 0;
            tooltip.style.left = `clamp(32px, ${pct.toFixed(2)}%, calc(100% - 32px))`;
            tooltip.textContent = formatSliderTooltipText(this.formatter, inputId, val);
            tooltip.classList.add('is-active');
        };

        const hideTooltip = () => {
            tooltip.classList.remove('is-active');
        };

        SliderManager.initGlobalTooltipDismissal();
        const startDrag = () => {
            this._isDragging = true;
            showTooltip(parseFloat(range.value) || 0);
        };
        const endDrag = () => {
            this._isDragging = false;
            hideTooltip();
            if (this._rafTriggerId !== null) {
                cancelAnimationFrame(this._rafTriggerId);
                this._rafTriggerId = null;
            }
            this.triggerFn({ isDragging: false });
        };

        range.addEventListener('pointerdown', startDrag);
        range.addEventListener('touchstart', startDrag, { passive: true });
        range.addEventListener('pointerup', endDrag);
        range.addEventListener('touchend', endDrag, { passive: true });
        range.addEventListener('touchcancel', endDrag, { passive: true });

        // Range Slider Input Sync
        range.addEventListener('input', () => {
            this._isDragging = true;
            if (this.isInternalSyncing) return;
            this.isInternalSyncing = true;
            try {
                input.value = range.value;
                const numericVal = parseFloat(range.value) || 0;
                updateSliderAria(this.formatter, range, range.value);
                this._refreshPairVisuals(inputId, range, numericVal);
                clearFieldError(this.dom, inputId);
                showTooltip(numericVal);

                // Tactile Haptic Vibration at tiered landmark intervals (F6.3)
                if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
                    const now = Date.now();
                    if (now - this._lastHapticTime > 300) {
                        const isLandmark = 
                            (inputId === 'sip' && numericVal % 5000 === 0 && numericVal > 0) ||
                            (inputId === 'lumpsum' && numericVal % 100000 === 0 && numericVal > 0) ||
                            (inputId === 'target_corpus' && numericVal % 500000 === 0 && numericVal > 0) ||
                            (inputId === 'swp_withdrawal' && numericVal % 10000 === 0 && numericVal > 0) ||
                            (inputId === 'years' && numericVal % 5 === 0) ||
                            (inputId === 'swp_years' && numericVal % 5 === 0) ||
                            ((inputId === 'rate' || inputId === 'stepup' || inputId === 'inflation' || inputId === 'swp_rate') && Number.isInteger(numericVal));
                        if (isLandmark) {
                            try {
                                navigator.vibrate(8);
                            } catch {}
                            this._lastHapticTime = now;
                        }
                    }
                }

                // Auto-dismiss tooltip after 800ms of inactivity (F6.1)
                if (this._tooltipDismissTimer !== null) {
                    clearTimeout(this._tooltipDismissTimer);
                }
                this._tooltipDismissTimer = setTimeout(() => {
                    tooltip.classList.remove('is-active');
                }, 800);
            } finally {
                this.isInternalSyncing = false;
            }

            // Batch calculation updates via requestAnimationFrame to maximize INP responsiveness
            if (this._rafTriggerId !== null) {
                cancelAnimationFrame(this._rafTriggerId);
            }
            this._rafTriggerId = requestAnimationFrame(() => {
                this._rafTriggerId = null;
                this.triggerFn({ isDragging: true });
            });
        });

        // Ensure final value commits immediately on interaction completion
        range.addEventListener('change', () => {
            this._isDragging = false;
            if (this._rafTriggerId !== null) {
                cancelAnimationFrame(this._rafTriggerId);
                this._rafTriggerId = null;
            }
            this.triggerFn({ isDragging: false });
        });

        // WAI-ARIA Slider Keyboard Support (PageUp, PageDown, Home, End)
        range.addEventListener('keydown', (e: KeyboardEvent) => {
            const step = parseFloat(range.step) || 1;
            const min = parseFloat(range.min) || 0;
            const max = parseFloat(range.max) || 100;
            let currentVal = parseFloat(range.value) || 0;
            let handled = false;

            if (e.key === 'PageUp') {
                const largeJump = Math.max(step * 5, (max - min) * 0.1);
                currentVal = Math.min(max, currentVal + largeJump);
                handled = true;
            } else if (e.key === 'PageDown') {
                const largeJump = Math.max(step * 5, (max - min) * 0.1);
                currentVal = Math.max(min, currentVal - largeJump);
                handled = true;
            } else if (e.key === 'Home') {
                currentVal = min;
                handled = true;
            } else if (e.key === 'End') {
                currentVal = max;
                handled = true;
            }

            if (handled) {
                e.preventDefault();
                this.updateFieldValue(inputId, currentVal);
            }
        });

        // Text Input Sync with Elastic Autoscaling
        input.addEventListener('input', () => {
            if (this.isInternalSyncing) return;
            this.isInternalSyncing = true;
            let validated: number;
            try {
                const rawVal = IndianNumberParser.parse(input.value);
                const fieldName = inputId;
                validated = this.validator.validate(fieldName, input.value);

                // Show inline error if out of bounds
                if (!isNaN(rawVal) && rawVal !== validated) {
                    const limits = this.validator.getConstraint(fieldName);
                    if (limits) {
                        const msg = rawVal < limits.min
                            ? `Minimum is ${limits.min}`
                            : `Maximum is ${limits.max}`;
                        showFieldError(this.dom, inputId, msg);
                    }
                } else {
                    clearFieldError(this.dom, inputId);
                }

                // Elastic dynamic autoscaling of range max
                const elasticMax = computeElasticMax(validated, defaultSliderMax);
                range.max = String(elasticMax);
                range.setAttribute('aria-valuemax', String(elasticMax));

                range.value = String(validated);
                updateSliderAria(this.formatter, range, validated);
                updateTrackProgress(range);
            } finally {
                this.isInternalSyncing = false;
            }

            // Debounce subtext updates and calculation trigger on raw text input
            if (this._inputDebounceTimer !== null) {
                clearTimeout(this._inputDebounceTimer);
            }
            this._inputDebounceTimer = setTimeout(() => {
                updateSubtext(this.dom, this.formatter, inputId, validated);
                updateWordBadge(this.dom, this.formatter, inputId, validated);
                updatePresetChips(this.dom, inputId, validated);
                this.triggerFn();
            }, 100);
        });

        input.addEventListener('change', () => {
            const rawVal = IndianNumberParser.parse(input.value);
            const validated = this.validator.validate(inputId, isNaN(rawVal) ? range.value : rawVal);
            input.value = String(validated);
            
            const elasticMax = computeElasticMax(validated, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));

            range.value = String(validated);
            updateSliderAria(this.formatter, range, validated);
            this._refreshPairVisuals(inputId, range, validated);
            this.triggerFn();
        });

        // Bi-directional focus halo lighting & Auto-select on focus
        input.addEventListener('focus', () => {
            range.classList.add('ring-2', 'ring-emerald-400/50');
            input.select();
        });
        input.addEventListener('blur', () => {
            range.classList.remove('ring-2', 'ring-emerald-400/50');
        });

        range.addEventListener('mouseenter', () => {
            input.classList.add('border-emerald-400', 'bg-emerald-50/20');
        });
        range.addEventListener('mouseleave', () => {
            if (document.activeElement !== input) {
                input.classList.remove('border-emerald-400', 'bg-emerald-50/20');
            }
        });
        range.addEventListener('pointerdown', () => {
            input.classList.add('border-emerald-500', 'ring-2', 'ring-emerald-500/20');
        });
        window.addEventListener('pointerup', () => {
            if (document.activeElement !== input) {
                input.classList.remove('border-emerald-500', 'ring-2', 'ring-emerald-500/20');
            }
        });

        input.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Enter') {
                if (this._inputDebounceTimer !== null) {
                    clearTimeout(this._inputDebounceTimer);
                    this._inputDebounceTimer = null;
                }
                const val = parseFloat(input.value) || 0;
                this._refreshPairVisuals(inputId, range, val);
                this.triggerFn();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const step = parseFloat(range.step) || 1;
                const multiplier = (e.metaKey || e.ctrlKey) ? 10 : (e.shiftKey ? 5 : 1);
                const current = parseFloat(input.value) || 0;
                this.updateFieldValue(inputId, current + step * multiplier);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                const step = parseFloat(range.step) || 1;
                const multiplier = (e.metaKey || e.ctrlKey) ? 10 : (e.shiftKey ? 5 : 1);
                const current = parseFloat(input.value) || 0;
                this.updateFieldValue(inputId, Math.max(0, current - step * multiplier));
            }
        });
    }

    /**
     * Sync all registered pairs from a config object.
     */
    syncAll(pairMap: Record<string, string>): void {
        for (const [inputId, rangeId] of Object.entries(pairMap)) {
            this.sync(inputId, rangeId);
        }
    }

    /**
     * Programmatically update a field value from external controllers.
     */
    updateFieldValue(fieldId: string, val: number, silent: boolean = false): void {
        const pair = this.pairs.find(p => p.fieldId === fieldId);
        if (!pair) {
            if (!silent) this.triggerFn();
            return;
        }

        const { input, range } = pair;
        const elasticMax = computeElasticMax(val, pair.defaultSliderMax);
        range.max = String(elasticMax);
        range.setAttribute('aria-valuemax', String(elasticMax));

        input.value = String(val);
        range.value = String(val);
        updateSliderAria(this.formatter, range, val);
        this._refreshPairVisuals(fieldId, range, val);
        clearFieldError(this.dom, fieldId);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        if (!silent) {
            this.triggerFn();
        }
    }

    /**
     * Recompute and update all visual elements (track progress, subtexts, chips).
     */
    refreshVisuals(): void {
        this.pairs.forEach(({ input, range, fieldId, defaultSliderMax }) => {
            const val = parseFloat(input.value) || 0;
            const elasticMax = computeElasticMax(val, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));
            this._refreshPairVisuals(fieldId, range, val);
        });
    }

    /**
     * Reset all registered input fields to their initial factory default values.
     */
    resetAllToDefaults(): void {
        this.pairs.forEach(({ fieldId, initialDefaultValue }) => {
            this.updateFieldValue(fieldId, initialDefaultValue);
            this._onFieldResetToDefault?.(fieldId, initialDefaultValue);
        });
    }

    private _refreshPairVisuals(fieldId: string, range: HTMLInputElement, val: number): void {
        updateTrackProgress(range);
        updateSubtext(this.dom, this.formatter, fieldId, val);
        updateWordBadge(this.dom, this.formatter, fieldId, val);
        updatePresetChips(this.dom, fieldId, val);
    }

    private _initPresetChips(fieldId: string): void {
        const chips = this.dom.getElements<HTMLButtonElement>(`button[data-preset-for="${fieldId}"]`);
        chips.forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.preventDefault();
                const presetVal = parseFloat(chip.dataset.presetVal || '');
                if (isNaN(presetVal)) return;

                this.updateFieldValue(fieldId, presetVal);
            });
        });
    }
}
