import { InputValidator } from './InputValidator';

interface SliderPair {
    input: HTMLInputElement;
    range: HTMLInputElement;
    fieldId: string;
    defaultSliderMax: number;
}

/**
 * SliderManager.ts
 * Encapsulates all range slider ↔ input synchronization logic.
 * Follows Single Responsibility Principle.
 */
export class SliderManager {
    private triggerFn: () => void;
    private validator: InputValidator;
    private pairs: SliderPair[] = [];
    private _inputDebounceTimer: ReturnType<typeof setTimeout> | null = null;

    constructor(triggerFn: () => void, validator: InputValidator) {
        this.triggerFn = triggerFn;
        this.validator = validator;
    }

    /**
     * Register and wire a single input ↔ range pair.
     */
    sync(inputId: string, rangeId: string): void {
        const input = document.getElementById(inputId) as HTMLInputElement | null;
        const range = document.getElementById(rangeId) as HTMLInputElement | null;
        if (!input || !range) return;

        const defaultSliderMax = parseFloat(range.getAttribute('max') || '100000');

        this.pairs.push({ input, range, fieldId: inputId, defaultSliderMax });

        range.addEventListener('input', () => {
            input.value = range.value;
            this._updateAria(range, range.value);
            this._clearError(inputId);
            this.triggerFn();
        });

        input.addEventListener('input', () => {
            const rawVal = parseFloat(input.value);
            const fieldName = inputId;
            const validated = this.validator.validate(fieldName, input.value);

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

            // Debounce text input to prevent jank during rapid typing
            if (this._inputDebounceTimer !== null) {
                clearTimeout(this._inputDebounceTimer);
            }
            this._inputDebounceTimer = setTimeout(() => this.triggerFn(), 150);
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

    private _updateAria(rangeEl: HTMLInputElement, val: number | string): void {
        rangeEl.setAttribute('aria-valuenow', String(val));
    }

    private _showError(fieldId: string, message: string): void {
        const errorEl = document.getElementById(`${fieldId}_error`);
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');

        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.add('border-rose-400', 'bg-rose-50');
        }
    }

    private _clearError(fieldId: string): void {
        const errorEl = document.getElementById(`${fieldId}_error`);
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.classList.add('hidden');

        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.remove('border-rose-400', 'bg-rose-50');
        }
    }
}
