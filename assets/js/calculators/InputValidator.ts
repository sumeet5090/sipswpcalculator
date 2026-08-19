import defaultsConfig from '../../../content/calculator_defaults.json';

export interface FieldConstraint {
    min: number;
    max: number;
    default: number;
}

export interface RawConfigField {
    min?: number | string;
    max?: number | string;
    default?: number | string;
}

export type RawConfigMap = Record<string, RawConfigField>;

export interface MilestoneTarget {
    label: string;
    value: number;
    icon: string;
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
                const cfg = JSON.parse(stateEl.textContent) as RawConfigMap;
                this.constraints = this._mapConfig(cfg);
                return;
            } catch (err) {
                console.warn('[InputValidator] Failed to parse #calculator-app-state Data Island, using static defaults:', err);
            }
        }

        // Static fallback — read from compiled calculator_defaults.json
        this.constraints = this._mapConfig(defaultsConfig as unknown as RawConfigMap);
    }

    /**
     * Map the PHP config structure to the flat constraints object format.
     */
    private _mapConfig(cfg: RawConfigMap): Record<string, FieldConstraint> {
        const result: Record<string, FieldConstraint> = {};
        for (const [key, val] of Object.entries(cfg)) {
            if (val && typeof val === 'object' && !Array.isArray(val)) {
                const hasMin = 'min' in val && typeof val.min !== 'undefined' && val.min !== null && !isNaN(Number(val.min));
                const hasMax = 'max' in val && typeof val.max !== 'undefined' && val.max !== null && !isNaN(Number(val.max));
                const hasDefault = 'default' in val && typeof val.default !== 'undefined' && val.default !== null && !isNaN(Number(val.default));

                if (hasMin || hasMax || hasDefault) {
                    result[key] = {
                        min:     hasMin ? Number(val.min) : 0,
                        max:     hasMax ? Number(val.max) : Number.MAX_SAFE_INTEGER,
                        default: hasDefault ? Number(val.default) : (hasMin ? Number(val.min) : 0),
                    };
                }
            }
        }
        return result;
    }

    /**
     * Retrieve constraint boundaries for a field.
     */
    getConstraint(field: string): FieldConstraint | undefined {
        return this.constraints[field];
    }

    /**
     * Sanitize and validate a specific field against its limits.
     * @returns Sanitized value within bounds, or default if NaN.
     */
    validate(field: string, val: number | string): number {
        const limits = this.constraints[field];
        if (!limits) return parseFloat(String(val)) || 0;

        const parsed = parseFloat(String(val));
        if (isNaN(parsed)) {
            return limits.default;
        }

        if (parsed < limits.min) return limits.min;
        if (parsed > limits.max) return limits.max;

        return parsed;
    }

    /**
     * Retrieve centralized milestone targets from configuration.
     */
    getMilestoneTargets(): MilestoneTarget[] {
        const stateEl = document.getElementById('calculator-app-state');
        if (stateEl && stateEl.textContent) {
            try {
                const parsed = JSON.parse(stateEl.textContent);
                if (Array.isArray(parsed.milestones)) {
                    return parsed.milestones;
                }
            } catch {}
        }
        const rawMilestones = (defaultsConfig as unknown as { milestones?: Array<{ label?: string; value?: number; icon?: string }> }).milestones || [];
        return rawMilestones.map((m) => ({
            label: String(m.label ?? ''),
            value: Number(m.value ?? 0),
            icon: String(m.icon ?? '')
        }));
    }
}

