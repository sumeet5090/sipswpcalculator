/**
 * MagneticSnapHelper.ts
 * Implements non-linear exponential power-curve transformations
 * with magnetic haptic snap-points at round Indian currency milestones.
 */
export class MagneticSnapHelper {
    // Culturally standard Indian investment round milestone targets
    private static SNAP_MILESTONES = [
        1000, 2500, 5000, 10000, 15000, 20000, 25000, 30000, 50000, 75000,
        100000, 150000, 200000, 250000, 500000, 1000000, 2500000, 5000000, 10000000
    ];

    /**
     * Map a raw linear slider position [0, 100] to an exponential value.
     */
    static positionToExponentialValue(
        positionPercent: number,
        min: number,
        max: number,
        exponent: number = 1.8
    ): number {
        const norm = Math.max(0, Math.min(100, positionPercent)) / 100;
        const curved = Math.pow(norm, exponent);
        const raw = min + (max - min) * curved;

        return this.snapToMilestone(raw, max);
    }

    /**
     * Magnetically snap a raw value if within 2.5% threshold of a key milestone.
     */
    static snapToMilestone(val: number, max: number): number {
        const threshold = Math.max(500, (max - val) * 0.025);

        for (const milestone of this.SNAP_MILESTONES) {
            if (Math.abs(val - milestone) <= threshold) {
                if (typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function') {
                    try {
                        navigator.vibrate(6);
                    } catch (_) {}
                }
                return milestone;
            }
        }

        // Clean integer rounding for intermediate values
        if (val >= 100000) {
            return Math.round(val / 5000) * 5000;
        } else if (val >= 25000) {
            return Math.round(val / 1000) * 1000;
        } else if (val >= 5000) {
            return Math.round(val / 500) * 500;
        }
        return Math.round(val / 100) * 100;
    }
}
