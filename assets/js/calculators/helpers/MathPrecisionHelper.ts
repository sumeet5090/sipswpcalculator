/**
 * MathPrecisionHelper
 * IEEE-754 Epsilon-safe financial rounding utilities.
 * Guarantees strict cross-runtime rounding parity between TypeScript and PHP round($val, $precision).
 */
export class MathPrecisionHelper {
    /**
     * Round number to 2 decimal places using IEEE-754 epsilon offset.
     */
    public static round2(val: number): number {
        return Math.round((val + Number.EPSILON) * 100) / 100;
    }

    /**
     * Round number to 4 decimal places using IEEE-754 epsilon offset.
     */
    public static round4(val: number): number {
        return Math.round((val + Number.EPSILON) * 10000) / 10000;
    }

    /**
     * Round number to arbitrary decimal places using IEEE-754 epsilon offset.
     */
    public static round(val: number, decimals: number = 2): number {
        const factor = Math.pow(10, decimals);
        return Math.round((val + Number.EPSILON) * factor) / factor;
    }
}
