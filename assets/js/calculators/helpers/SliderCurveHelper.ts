/**
 * SliderCurveHelper.ts
 * Provides continuous, strictly-monotonic bijective transfer functions between
 * normalized slider positions (0 to 100) and currency/financial parameter values.
 * 
 * Resolves the UI friction where linear sliders crowd 80% of retail inputs (₹500-₹50k)
 * into the leftmost 5% of a ₹10,00,000 range.
 */

export type CurveType = 'linear' | 'currency_piecewise' | 'power_quadratic';

export class SliderCurveHelper {
    /**
     * Convert slider position percentage (0 to 100) to actual domain value.
     */
    static positionToValue(
        pos: number,
        min: number,
        max: number,
        step: number = 1,
        curve: CurveType = 'linear'
    ): number {
        const clampedPos = Math.max(0, Math.min(100, pos));
        if (curve === 'linear' || max <= min) {
            const rawVal = min + (clampedPos / 100) * (max - min);
            return this.snapToStep(rawVal, min, max, step);
        }

        if (curve === 'currency_piecewise') {
            // Midpoint anchor: 50% slider travel represents the retail ceiling (e.g. ₹50,000 or 10% of max)
            const midValue = Math.min(50000, min + (max - min) * 0.15);
            
            let rawVal: number;
            if (clampedPos <= 50) {
                // First half (0-50%): Linear fine control from min to midValue
                rawVal = min + (clampedPos / 50) * (midValue - min);
            } else {
                // Second half (50-100%): Quadratic curve from midValue to max
                const t = (clampedPos - 50) / 50;
                rawVal = midValue + Math.pow(t, 2) * (max - midValue);
            }
            return this.snapToStep(rawVal, min, max, step);
        }

        if (curve === 'power_quadratic') {
            const t = clampedPos / 100;
            const rawVal = min + Math.pow(t, 2) * (max - min);
            return this.snapToStep(rawVal, min, max, step);
        }

        const fallback = min + (clampedPos / 100) * (max - min);
        return this.snapToStep(fallback, min, max, step);
    }

    /**
     * Convert domain value to normalized slider position percentage (0 to 100).
     */
    static valueToPosition(
        val: number,
        min: number,
        max: number,
        curve: CurveType = 'linear'
    ): number {
        const clampedVal = Math.max(min, Math.min(max, val));
        if (curve === 'linear' || max <= min) {
            return ((clampedVal - min) / (max - min)) * 100;
        }

        if (curve === 'currency_piecewise') {
            const midValue = Math.min(50000, min + (max - min) * 0.15);
            if (clampedVal <= midValue) {
                if (midValue === min) return 0;
                return ((clampedVal - min) / (midValue - min)) * 50;
            } else {
                if (max === midValue) return 100;
                const t = Math.sqrt((clampedVal - midValue) / (max - midValue));
                return 50 + t * 50;
            }
        }

        if (curve === 'power_quadratic') {
            const t = Math.sqrt((clampedVal - min) / (max - min));
            return t * 100;
        }

        return ((clampedVal - min) / (max - min)) * 100;
    }

    private static snapToStep(val: number, min: number, max: number, step: number): number {
        if (step <= 0) return Math.max(min, Math.min(max, val));
        const snapped = Math.round((val - min) / step) * step + min;
        return Math.max(min, Math.min(max, snapped));
    }
}
