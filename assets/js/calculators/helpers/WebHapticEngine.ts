/**
 * WebHapticEngine.ts
 * Delivers fine-grained, throttled mechanical haptic vibrations
 * via the HTML5 Vibration API on supported mobile browsers.
 */
export class WebHapticEngine {
    private static lastVibrationTime: number = 0;
    private static readonly DEFAULT_DEADBAND_MS: number = 60;

    /**
     * Checks if the device and browser support the Vibration API.
     */
    public static isSupported(): boolean {
        return typeof navigator !== 'undefined' && 'vibrate' in navigator && typeof navigator.vibrate === 'function';
    }

    /**
     * Crisp, subtle 6ms mechanical tick for slider steps and milestone crossings.
     */
    public static triggerTick(deadbandMs: number = WebHapticEngine.DEFAULT_DEADBAND_MS): void {
        if (!this.isSupported()) return;

        const now = Date.now();
        if (now - this.lastVibrationTime < deadbandMs) return;
        this.lastVibrationTime = now;

        try {
            navigator.vibrate(6);
        } catch {
            // Silently swallow any security or sandbox permission exceptions
        }
    }

    /**
     * Firm 12ms vibration for significant milestones (5-year leaps, ₹1 Crore threshold).
     */
    public static triggerMilestone(): void {
        if (!this.isSupported()) return;

        const now = Date.now();
        if (now - this.lastVibrationTime < 100) return;
        this.lastVibrationTime = now;

        try {
            navigator.vibrate(12);
        } catch {
            // Silent fallback
        }
    }

    /**
     * Multi-pulse pattern for celebratory actions (plan save, scenario snapshot, PDF download).
     */
    public static triggerSuccess(): void {
        if (!this.isSupported()) return;

        try {
            navigator.vibrate([10, 40, 10]);
        } catch {
            // Silent fallback
        }
    }
}
