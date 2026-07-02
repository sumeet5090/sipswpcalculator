/**
 * InputValidator.js
 * Centralized boundaries, constraints, and validation rules.
 * Refactored as an Object-Oriented class.
 */
export class InputValidator {
    constructor(constraints = null) {
        this.constraints = constraints || {
            sip: { min: 500, max: 1000000, default: 10000 },
            years: { min: 1, max: 50, default: 20 },
            rate: { min: 0.1, max: 30, default: 12 },
            stepup: { min: 0, max: 50, default: 10 },
            swp_withdrawal: { min: 0, max: 1000000, default: 5000 },
            swp_years: { min: 1, max: 50, default: 20 },
            swp_stepup: { min: 0, max: 20, default: 6 }
        };
    }

    /**
     * Sanitize and validate a specific field against limits.
     * @param {string} field 
     * @param {number|string} val 
     * @returns {number} Sanitized value within bounds.
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
