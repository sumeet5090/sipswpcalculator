import defaultsConfig from '../../../content/calculator_defaults.json';

/**
 * InputValidator.js
 * Centralized boundaries, constraints, and validation rules.
 * Constraints are read from the data-config attribute on #calculator-app
 * (injected by the PHP backend from calculator_defaults.php).
 * A static fallback is used if the attribute is absent (e.g., in tests).
 */
export class InputValidator {
    /**
     * @param {object|null} constraints - Override constraints (used in unit tests).
     *   If null, constraints are read from the DOM's data-config attribute.
     */
    constructor(constraints = null) {
        if (constraints) {
            this.constraints = constraints;
            return;
        }

        // Read from the single source of truth serialized into the DOM by PHP.
        const appEl = document.querySelector('[data-js="calculator-app"]');
        if (appEl && appEl.dataset.config) {
            try {
                const cfg = JSON.parse(appEl.dataset.config);
                this.constraints = this._mapConfig(cfg);
                return;
            } catch (e) {
                console.warn('InputValidator: Failed to parse data-config. Using fallback.', e);
            }
        }

        // Static fallback — read from compiled calculator_defaults.json
        this.constraints = this._mapConfig(defaultsConfig);
    }

    /**
     * Map the PHP config structure to the flat constraints object format.
     * @param {object} cfg - Parsed data-config JSON
     * @returns {object}
     */
    _mapConfig(cfg) {
        const result = {};
        for (const [key, val] of Object.entries(cfg)) {
            result[key] = {
                min:     val.min,
                max:     val.max,
                default: val.default,
            };
        }
        return result;
    }

    /**
     * Sanitize and validate a specific field against its limits.
     * @param {string} field
     * @param {number|string} val
     * @returns {number} Sanitized value within bounds, or default if NaN.
     */
    validate(field, val) {
        const limits = this.constraints[field];
        if (!limits) return parseFloat(val) || 0;

        let parsed = parseFloat(val);
        if (isNaN(parsed)) {
            return limits.default;
        }

        if (parsed < limits.min) return limits.min;
        if (parsed > limits.max) return limits.max;

        return parsed;
    }
}
