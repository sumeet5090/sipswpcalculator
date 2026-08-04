import defaultsConfig from '../../../content/calculator_defaults.json';

export interface FieldConstraint {
    min: number;
    max: number;
    default: number;
}

/**
 * InputValidator.ts
 * Centralized boundaries, constraints, and validation rules.
 * Constraints are read from the #calculator-app-state Data Island script tag
 * if present, falling back to compiled-in defaults.
 */
export class InputValidator {
    private constraints: Record<string, FieldConstraint>;

    /**
     * @param constraints Override constraints (used in unit tests).
     *   If null, constraints are read from the #calculator-app-state Data Island script tag.
     */
    constructor(constraints: Record<string, FieldConstraint> | null = null) {
        if (constraints) {
            this.constraints = constraints;
            return;
        }

        // Read from the single source of truth (Data Island) serialized into the DOM by PHP.
        const stateEl = document.getElementById('calculator-app-state');
        if (stateEl && stateEl.textContent) {
            try {
                const cfg = JSON.parse(stateEl.textContent);
                this.constraints = this._mapConfig(cfg);
                return;
            } catch (e) {
                console.warn('InputValidator: Failed to parse calculator-app-state Data Island. Using fallback.', e);
            }
        }

        // Static fallback — read from compiled calculator_defaults.json
        this.constraints = this._mapConfig(defaultsConfig as any);
    }

    /**
     * Map the PHP config structure to the flat constraints object format.
     */
    private _mapConfig(cfg: Record<string, any>): Record<string, FieldConstraint> {
        const result: Record<string, FieldConstraint> = {};
        for (const [key, val] of Object.entries(cfg)) {
            result[key] = {
                min:     Number(val.min),
                max:     Number(val.max),
                default: Number(val.default),
            };
        }
        return result;
    }

    /**
     * Sanitize and validate a specific field against its limits.
     * @returns Sanitized value within bounds, or default if NaN.
     */
    validate(field: string, val: number | string): number {
        const limits = this.constraints[field];
        if (!limits) return parseFloat(String(val)) || 0;

        let parsed = parseFloat(String(val));
        if (isNaN(parsed)) {
            return limits.default;
        }

        if (parsed < limits.min) return limits.min;
        if (parsed > limits.max) return limits.max;

        return parsed;
    }
}
