import { CurrencyFormatter } from '../CurrencyHelper';

/**
 * A11yAnnouncer.ts
 * Dedicated screen reader live region announcer with intelligent debouncing.
 * Ensures WCAG 2.1 Criterion 4.1.3 (Status Messages) compliance without flooding the speech queue.
 */
export class A11yAnnouncer {
    private static debounceTimer: ReturnType<typeof setTimeout> | null = null;
    private static lastAnnouncedMessage: string = '';
    private static formatter: CurrencyFormatter = new CurrencyFormatter();

    /**
     * Broadcasts an announcement to assistive technologies via aria-live container.
     * @param message Text to be spoken
     * @param delayMs Trailing debounce delay in milliseconds (default: 350ms)
     */
    public static announce(message: string, delayMs: number = 350): void {
        if (!message || message === this.lastAnnouncedMessage) return;

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            const announcerEl = document.getElementById('calculator-a11y-live-announcer');
            if (announcerEl) {
                // Clear and re-populate to force screen reader re-announcement
                announcerEl.textContent = '';
                // Small microtask delay ensures assistive technologies register the text mutation
                setTimeout(() => {
                    announcerEl.textContent = message;
                    this.lastAnnouncedMessage = message;
                }, 50);
            }
        }, delayMs);
    }

    /**
     * Helper to broadcast a structured calculation summary.
     */
    public static announceCalculation(
        mode: string,
        primaryInputLabel: string,
        primaryInputValue: number,
        years: number,
        rate: number,
        corpus: number,
        gainsOrWithdrawals: number
    ): void {
        const primaryFormatted = this.formatter.formatDynamic(primaryInputValue);
        const corpusFormatted = this.formatter.formatDynamic(corpus);
        const secondaryFormatted = this.formatter.formatDynamic(gainsOrWithdrawals);

        let summary = '';
        if (mode === 'swp') {
            summary = `SWP configured for ${years} years at ${rate}% expected return. Starting withdrawal (${primaryInputLabel}): ${primaryFormatted} per month. Remaining final corpus: ${corpusFormatted}, with ${secondaryFormatted} total withdrawals.`;
        } else if (mode === 'target') {
            summary = `Target corpus goal: ${corpusFormatted} in ${years} years at ${rate}%. Required monthly ${primaryInputLabel}: ${primaryFormatted}.`;
        } else {
            summary = `SIP investment of ${primaryFormatted} (${primaryInputLabel}) for ${years} years at ${rate}% expected return. Projected final corpus: ${corpusFormatted}, with ${secondaryFormatted} in total compounding gains.`;
        }

        this.announce(summary, 800);
    }
}
