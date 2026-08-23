import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { CurrencyFormatter } from './CurrencyHelper';
import { IndianNumberParser } from './helpers/IndianNumberParser';

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
 * dynamic progress track styling, quick-preset chips, and live subtext indicators.
 * Strictly adheres to SOLID, DRY, and POLA principles.
 */
export class SliderManager {
    private triggerFn: () => void;
    private validator: InputValidator;
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private pairs: SliderPair[] = [];
    private _inputDebounceTimer: ReturnType<typeof setTimeout> | null = null;

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

    private isInternalSyncing: boolean = false;

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
        this._initPresetChips(inputId, input, range);

        // Initial visual sync
        const initialVal = parseFloat(input.value) || 0;
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

        const hideTooltip = () => {
            tooltip.classList.remove('is-active');
        };

        range.addEventListener('pointerdown', () => showTooltip(parseFloat(range.value) || 0));
        range.addEventListener('touchstart', () => showTooltip(parseFloat(range.value) || 0), { passive: true });
        window.addEventListener('pointerup', hideTooltip);
        window.addEventListener('touchend', hideTooltip);
        window.addEventListener('touchcancel', hideTooltip);

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

                // Tactile Haptic Vibration at major intervals
                if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
                    if (numericVal % 10000 === 0 || (inputId === 'years' && numericVal % 5 === 0)) {
                        navigator.vibrate(6);
                    }
                }
            } finally {
                this.isInternalSyncing = false;
            }
            this.triggerFn();
        });

        input.addEventListener('input', () => {
            if (this.isInternalSyncing) return;
            this.isInternalSyncing = true;
            let validated: number;
            try {
                const rawVal = IndianNumberParser.parse(input.value);
                const fieldName = inputId;
                validated = this.validator.validate(fieldName, input.value);

                // Show inline error if out of bounds (and user has typed something)
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

                // Dynamically scale slider max if validated exceeds default slider max
                if (validated > defaultSliderMax) {
                    range.max = String(validated);
                } else {
                    range.max = String(defaultSliderMax);
                }

                range.value = String(validated);
                this._updateAria(range, validated);
                this._updateTrackProgress(range);
                this._updateSubtext(inputId, isNaN(rawVal) ? validated : rawVal);
                this._updateWordBadge(inputId, isNaN(rawVal) ? validated : rawVal);
                this._updatePresetChips(inputId, isNaN(rawVal) ? validated : rawVal);
            } finally {
                this.isInternalSyncing = false;
            }

            // Debounce text input to prevent jank during rapid typing
            if (this._inputDebounceTimer !== null) {
                clearTimeout(this._inputDebounceTimer);
            }
            this._inputDebounceTimer = setTimeout(() => this.triggerFn(), 150);
        });

        input.addEventListener('change', () => {
            if (this._inputDebounceTimer !== null) {
                clearTimeout(this._inputDebounceTimer);
                this._inputDebounceTimer = null;
            }
            const validated = this.validator.validate(inputId, input.value);
            input.value = String(validated);
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
     * Programmatically update a field value from external controllers (e.g. StepperController).
     */
    updateFieldValue(fieldId: string, val: number): void {
        const pair = this.pairs.find(p => p.fieldId === fieldId);
        if (!pair) {
            this.triggerFn();
            return;
        }

        const { input, range } = pair;
        const defaultMax = pair.defaultSliderMax;
        if (val > defaultMax) {
            range.max = String(val);
        }

        input.value = String(val);
        range.value = String(val);
        this._updateAria(range, val);
        this._updateTrackProgress(range);
        this._updateSubtext(fieldId, val);
        this._updateWordBadge(fieldId, val);
        this._updatePresetChips(fieldId, val);
        this._clearError(fieldId);
        this.triggerFn();
    }

    /**
     * Recompute and update all visual elements (track progress, subtexts, chips).
     */
    refreshVisuals(): void {
        this.pairs.forEach(({ input, range, fieldId }) => {
            const val = parseFloat(input.value) || 0;
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

    private _initPresetChips(fieldId: string, input: HTMLInputElement, range: HTMLInputElement): void {
        const chips = this.dom.getElements<HTMLButtonElement>(`button[data-preset-for="${fieldId}"]`);
        chips.forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.preventDefault();
                const presetVal = parseFloat(chip.dataset.presetVal || '');
                if (isNaN(presetVal)) return;

                const defaultMax = parseFloat(range.getAttribute('max') || '100000');
                if (presetVal > defaultMax) {
                    range.max = String(presetVal);
                }

                input.value = String(presetVal);
                range.value = String(presetVal);

                this._updateAria(range, presetVal);
                this._updateTrackProgress(range);
                this._updateSubtext(fieldId, presetVal);
                this._updatePresetChips(fieldId, presetVal);
                this._clearError(fieldId);

                this.triggerFn();
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
        const text = this.formatter.formatSubtext(fieldId, val);
        subtextEl.textContent = text;
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

    private _debounceAriaTimer: ReturnType<typeof setTimeout> | null = null;

    private _updateAria(rangeEl: HTMLInputElement, val: number | string): void {
        rangeEl.setAttribute('aria-valuenow', String(val));
        const fieldId = rangeEl.id.replace(/_range$/, '');
        if (this._debounceAriaTimer !== null) {
            clearTimeout(this._debounceAriaTimer);
        }
        this._debounceAriaTimer = setTimeout(() => {
            const readableText = this.formatter.formatAriaAnnouncement(fieldId, Number(val));
            rangeEl.setAttribute('aria-valuetext', readableText);
        }, 300);
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

