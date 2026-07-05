/**
 * SliderManager.js
 * Encapsulates all range slider ↔ input synchronization logic.
 * Follows Single Responsibility Principle: SliderManager only knows how
 * to keep a range slider and a text input in sync, update ARIA state,
 * and surface inline validation errors. Calculation triggering is
 * delegated to the provided triggerFn callback.
 */
export class SliderManager {
    /**
     * @param {function} triggerFn - Callback invoked after any value change.
     * @param {InputValidator} validator - Validator instance for constraint checking.
     */
    constructor(triggerFn, validator) {
        this.triggerFn = triggerFn;
        this.validator = validator;
        this.pairs = [];
    }

    /**
     * Register and wire a single input ↔ range pair.
     * @param {string} inputId - ID of the <input type="number"> element.
     * @param {string} rangeId - ID of the <input type="range"> element.
     */
    sync(inputId, rangeId) {
        const input = document.getElementById(inputId);
        const range = document.getElementById(rangeId);
        if (!input || !range) return;

        this.pairs.push({ input, range, fieldId: inputId });

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
                const limits = this.validator.constraints[fieldName];
                if (limits) {
                    const msg = rawVal < limits.min
                        ? `Minimum is ${limits.min}`
                        : `Maximum is ${limits.max}`;
                    this._showError(inputId, msg);
                }
            } else {
                this._clearError(inputId);
            }

            range.value = validated;
            this._updateAria(range, validated);
            this.triggerFn();
        });
    }

    /**
     * Sync all registered pairs from a config object (e.g., read from data-config).
     * Only syncs pairs that exist in the DOM — safe to call on any page.
     * @param {object} pairMap - Map of { inputId: rangeId }
     */
    syncAll(pairMap) {
        for (const [inputId, rangeId] of Object.entries(pairMap)) {
            this.sync(inputId, rangeId);
        }
    }

    /**
     * Update aria-valuenow on a range element.
     * @param {HTMLInputElement} rangeEl
     * @param {number|string} val
     */
    _updateAria(rangeEl, val) {
        rangeEl.setAttribute('aria-valuenow', String(val));
    }

    /**
     * Show an inline validation error message below the field.
     * @param {string} fieldId
     * @param {string} message
     */
    _showError(fieldId, message) {
        const errorEl = document.getElementById(`${fieldId}_error`);
        if (!errorEl) return;
        errorEl.textContent = message;
        errorEl.classList.remove('hidden');

        const input = document.getElementById(fieldId);
        if (input) {
            input.classList.add('border-rose-400', 'bg-rose-50');
        }
    }

    /**
     * Clear an inline validation error message.
     * @param {string} fieldId
     */
    _clearError(fieldId) {
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
