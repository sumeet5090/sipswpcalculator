import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { CurrencyFormatter } from './CurrencyHelper';
import { IndianNumberParser } from './helpers/IndianNumberParser';
import { A11yAnnouncer } from './helpers/A11yAnnouncer';

interface SliderPair {
    input: HTMLInputElement;
    range: HTMLInputElement;
    fieldId: string;
    defaultSliderMax: number;
    initialDefaultValue: number;
}

/**
 * SliderManager.ts
 * Encapsulates all range slider ↔ input synchronization logic,
 * elastic dynamic scale expansion, WAI-ARIA extended keyboard controls,
 * dynamic progress track styling, quick-preset chips, and live subtext indicators.
 * Strictly adheres to SOLID, DRY, and POLA principles.
 */
export class SliderManager {
    private static globalTooltipListenersInitialized = false;

    private static initGlobalTooltipDismissal(): void {
        if (typeof window === 'undefined' || SliderManager.globalTooltipListenersInitialized) return;
        SliderManager.globalTooltipListenersInitialized = true;

        const hideAllTooltips = () => {
            document.querySelectorAll('.calc-slider-tooltip.is-active').forEach(el => {
                el.classList.remove('is-active');
            });
        };

        window.addEventListener('pointerup', hideAllTooltips);
        window.addEventListener('touchend', hideAllTooltips, { passive: true });
        window.addEventListener('touchcancel', hideAllTooltips, { passive: true });
    }

    private triggerFn: () => void;
    private validator: InputValidator;
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private pairs: SliderPair[] = [];
    private _inputDebounceTimer: ReturnType<typeof setTimeout> | null = null;
    private _tooltipDismissTimer: ReturnType<typeof setTimeout> | null = null;
    private _lastHapticTime: number = 0;
    private isInternalSyncing: boolean = false;

    constructor(
        triggerFn: () => void,
        validator: InputValidator,
        dom: DOMAdapter = new DOMAdapter(),
        formatter: CurrencyFormatter = new CurrencyFormatter()
    ) {
        this.triggerFn = triggerFn;
        this.validator = validator;
        this.dom = dom;
        this.formatter = formatter;
    }

    /**
     * Compute elastic slider upper bound when entered value exceeds default bounds.
     */
    private _computeElasticMax(val: number, defaultMax: number): number {
        if (val <= defaultMax) {
            return defaultMax;
        }
        const scaleFactor = 1.5;
        const target = val * scaleFactor;
        if (target >= 10000000) {
            return Math.ceil(target / 5000000) * 5000000; // Step of 50 Lakhs
        }
        if (target >= 1000000) {
            return Math.ceil(target / 500000) * 500000; // Step of 5 Lakhs
        }
        if (target >= 100000) {
            return Math.ceil(target / 50000) * 50000; // Step of 50k
        }
        if (target >= 10000) {
            return Math.ceil(target / 10000) * 10000; // Step of 10k
        }
        return Math.ceil(target);
    }

    /**
     * Dynamically adjust slider upper boundary if manual input exceeds initial bounds.
     */
    public adjustSliderBoundary(fieldId: string, enteredValue: number): void {
        const pair = this.pairs.find(p => p.fieldId === fieldId);
        if (!pair) return;
        const newMax = this._computeElasticMax(enteredValue, pair.defaultSliderMax);
        pair.range.max = String(newMax);
        pair.range.setAttribute('aria-valuemax', String(newMax));
        this._updateTrackProgress(pair.range);
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
            });

            let lastTap = 0;
            labelEl.addEventListener('touchend', (e) => {
                const now = Date.now();
                if (now - lastTap < 350 && now - lastTap > 0) {
                    e.preventDefault();
                    this.updateFieldValue(inputId, initialDefaultValue);
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
            const elasticMax = this._computeElasticMax(initialVal, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));
        }
        this._updateTrackProgress(range);
        this._updateSubtext(inputId, initialVal);
        this._updateWordBadge(inputId, initialVal);
        this._updatePresetChips(inputId, initialVal);

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
            tooltip.style.left = `clamp(28px, ${pct.toFixed(2)}%, calc(100% - 28px))`;
            if (inputId === 'sip' || inputId === 'lumpsum' || inputId === 'target_corpus' || inputId === 'swp_withdrawal') {
                tooltip.textContent = this.formatter.formatDynamic(val);
            } else if (inputId === 'years' || inputId === 'swp_years') {
                tooltip.textContent = `${val} Yrs`;
            } else {
                tooltip.textContent = `${val}%`;
            }
            tooltip.classList.add('is-active');
        };

        SliderManager.initGlobalTooltipDismissal();
        range.addEventListener('pointerdown', () => showTooltip(parseFloat(range.value) || 0));
        range.addEventListener('touchstart', () => showTooltip(parseFloat(range.value) || 0), { passive: true });

        // Range Slider Input Sync
        range.addEventListener('input', () => {
            if (this.isInternalSyncing) return;
            this.isInternalSyncing = true;
            try {
                input.value = range.value;
                const numericVal = parseFloat(range.value) || 0;
                this._updateAria(range, range.value);
                this._updateTrackProgress(range);
                this._updateSubtext(inputId, numericVal);
                this._updateWordBadge(inputId, numericVal);
                this._updatePresetChips(inputId, numericVal);
                this._clearError(inputId);
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
            this.triggerFn();
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
                        this._showError(inputId, msg);
                    }
                } else {
                    this._clearError(inputId);
                }

                // Elastic dynamic autoscaling of range max
                const elasticMax = this._computeElasticMax(validated, defaultSliderMax);
                range.max = String(elasticMax);
                range.setAttribute('aria-valuemax', String(elasticMax));

                range.value = String(validated);
                this._updateAria(range, validated);
                this._updateTrackProgress(range);
            } finally {
                this.isInternalSyncing = false;
            }

            // Debounce subtext updates and calculation trigger on raw text input
            if (this._inputDebounceTimer !== null) {
                clearTimeout(this._inputDebounceTimer);
            }
            this._inputDebounceTimer = setTimeout(() => {
                this._updateSubtext(inputId, validated);
                this._updateWordBadge(inputId, validated);
                this._updatePresetChips(inputId, validated);
                this.triggerFn();
            }, 100);
        });

        input.addEventListener('change', () => {
            const rawVal = IndianNumberParser.parse(input.value);
            const validated = this.validator.validate(inputId, isNaN(rawVal) ? range.value : rawVal);
            input.value = String(validated);
            
            const elasticMax = this._computeElasticMax(validated, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));

            range.value = String(validated);
            this._updateAria(range, validated);
            this._updateTrackProgress(range);
            this._updateSubtext(inputId, validated);
            this._updateWordBadge(inputId, validated);
            this._updatePresetChips(inputId, validated);
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
                this._updateTrackProgress(range);
                this._updateSubtext(inputId, val);
                this._updatePresetChips(inputId, val);
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
        const elasticMax = this._computeElasticMax(val, pair.defaultSliderMax);
        range.max = String(elasticMax);
        range.setAttribute('aria-valuemax', String(elasticMax));

        input.value = String(val);
        range.value = String(val);
        this._updateAria(range, val);
        this._updateTrackProgress(range);
        this._updateSubtext(fieldId, val);
        this._updateWordBadge(fieldId, val);
        this._updatePresetChips(fieldId, val);
        this._clearError(fieldId);
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
            const elasticMax = this._computeElasticMax(val, defaultSliderMax);
            range.max = String(elasticMax);
            range.setAttribute('aria-valuemax', String(elasticMax));
            this._updateTrackProgress(range);
            this._updateSubtext(fieldId, val);
            this._updateWordBadge(fieldId, val);
            this._updatePresetChips(fieldId, val);
        });
    }

    /**
     * Reset all registered input fields to their initial factory default values.
     */
    resetAllToDefaults(): void {
        this.pairs.forEach(({ fieldId, initialDefaultValue }) => {
            this.updateFieldValue(fieldId, initialDefaultValue);
        });
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

    private _updateTrackProgress(rangeEl: HTMLInputElement): void {
        const min = parseFloat(rangeEl.min) || 0;
        const max = parseFloat(rangeEl.max) || 100;
        const val = parseFloat(rangeEl.value) || 0;
        const rangeSpan = Math.max(max - min, 1);
        const percent = Math.min(Math.max(((val - min) / rangeSpan) * 100, 0), 100);

        rangeEl.style.setProperty('--range-progress', `${percent}%`);
    }

    private _updateSubtext(fieldId: string, val: number): void {
        const subtextEl = this.dom.getElement(`${fieldId}_subtext`);
        if (!subtextEl) return;
        const sipVal = parseFloat(this.dom.getValue('sip') || '0') || 0;
        const text = this.formatter.formatSubtext(fieldId, val, { sip: sipVal });
        subtextEl.textContent = text;

        if (fieldId === 'sip') {
            const stepupEl = this.dom.getElement('stepup_subtext');
            if (stepupEl) {
                const stepupVal = parseFloat(this.dom.getValue('stepup') || '0') || 0;
                stepupEl.textContent = this.formatter.formatSubtext('stepup', stepupVal, { sip: val });
            }
        }
    }

    private _updateWordBadge(fieldId: string, val: number): void {
        const badgeEl = this.dom.getElement(`${fieldId}_word_badge`);
        if (!badgeEl) return;
        const text = this.formatter.formatWordBadge(val);
        badgeEl.textContent = text;
        badgeEl.style.display = text ? 'inline-block' : 'none';
    }

    private _updatePresetChips(fieldId: string, currentVal: number): void {
        const chips = this.dom.getElements<HTMLButtonElement>(`button[data-preset-for="${fieldId}"]`);
        chips.forEach(chip => {
            const presetVal = parseFloat(chip.dataset.presetVal || '');
            const isActive = (!isNaN(presetVal) && Math.abs(presetVal - currentVal) < 0.01);
            const isRoseTheme = chip.classList.contains('is-active-rose') || chip.closest('[data-field-id]')?.querySelector('.accent-rose-500, .accent-rose-600') !== null;

            if (isActive) {
                chip.setAttribute('aria-pressed', 'true');
                if (isRoseTheme) {
                    chip.classList.add('is-active-rose', 'bg-rose-600', 'text-white', 'border-rose-600');
                    chip.classList.remove('bg-slate-50/90', 'text-slate-600', 'border-slate-200/90');
                } else {
                    chip.classList.add('is-active', 'bg-emerald-600', 'text-white', 'border-emerald-600');
                    chip.classList.remove('bg-slate-50/90', 'text-slate-600', 'border-slate-200/90');
                }
            } else {
                chip.setAttribute('aria-pressed', 'false');
                chip.classList.remove('is-active', 'is-active-rose', 'bg-emerald-600', 'bg-rose-600', 'text-white', 'border-emerald-600', 'border-rose-600');
                chip.classList.add('bg-slate-50/90', 'text-slate-600', 'border-slate-200/90');
            }
        });
    }

    private _updateAria(rangeEl: HTMLInputElement, val: number | string): void {
        rangeEl.setAttribute('aria-valuenow', String(val));
        const fieldId = rangeEl.id.replace(/_range$/, '');
        const readableText = this.formatter.formatAriaAnnouncement(fieldId, Number(val));
        rangeEl.setAttribute('aria-valuetext', readableText);
        A11yAnnouncer.announce(readableText, 700);
    }

    private _showError(fieldId: string, message: string): void {
        const errorEl = this.dom.getElement(`${fieldId}_error`);
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');

        const input = this.dom.getElement(fieldId);
        if (input) {
            input.classList.add('border-rose-400', 'bg-rose-50');
        }
    }

    private _clearError(fieldId: string): void {
        const errorEl = this.dom.getElement(`${fieldId}_error`);
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.classList.add('hidden');

        const input = this.dom.getElement(fieldId);
        if (input) {
            input.classList.remove('border-rose-400', 'bg-rose-50');
        }
    }
}
