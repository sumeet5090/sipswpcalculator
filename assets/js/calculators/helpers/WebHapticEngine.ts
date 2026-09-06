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
     * Firm milestone vibration (double-pulse notch feel) for significant thresholds (e.g. ₹1 Crore, 10/20 years).
     */
    public static triggerMilestone(): void {
        if (!this.isSupported()) return;

        const now = Date.now();
        if (now - this.lastVibrationTime < 180) return;
        this.lastVibrationTime = now;

        try {
            navigator.vibrate([15, 30, 20]);
        } catch {
            // Silent fallback
        }
    }

    /**
     * Inspects previous and current corpus values and triggers a milestone haptic pulse
     * when crossing key financial boundaries (₹10L, ₹25L, ₹50L, ₹1Cr, ₹2.5Cr, ₹5Cr, ₹10Cr).
     */
    public static checkCorpusMilestone(oldCorpus: number, newCorpus: number): void {
        if (!this.isSupported() || oldCorpus <= 0 || newCorpus <= 0) return;

        const MILESTONES = [
            1_000_000,   // ₹10 Lakh
            2_500_000,   // ₹25 Lakh
            5_000_000,   // ₹50 Lakh
            10_000_000,  // ₹1 Crore
            25_000_000,  // ₹2.5 Crore
            50_000_000,  // ₹5 Crore
            100_000_000, // ₹10 Crore
        ];

        for (const threshold of MILESTONES) {
            if ((oldCorpus < threshold && newCorpus >= threshold) || (oldCorpus >= threshold && newCorpus < threshold)) {
                this.triggerMilestone();
                break;
            }
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
