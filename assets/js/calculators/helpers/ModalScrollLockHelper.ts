export class ModalScrollLockHelper {
    private static openModalCount = 0;
    private static previousActiveElement: HTMLElement | null = null;

    /**
     * Locks body scrolling and captures the triggering element for focus restoration.
     */
    static lock(triggerElement?: HTMLElement | null): void {
        if (this.openModalCount === 0) {
            this.previousActiveElement = (triggerElement || document.activeElement) as HTMLElement | null;
            document.body.style.overflow = 'hidden';
        }
        this.openModalCount++;
    }

    /**
     * Unlocks body scrolling and restores keyboard focus to the triggering element.
     */
    static unlock(restoreFocus: boolean = true): void {
        this.openModalCount = Math.max(0, this.openModalCount - 1);

        if (this.openModalCount === 0) {
            document.body.style.overflow = '';
            if (
                restoreFocus &&
                this.previousActiveElement &&
                document.body.contains(this.previousActiveElement) &&
                typeof this.previousActiveElement.focus === 'function'
            ) {
                this.previousActiveElement.focus();
                this.previousActiveElement = null;
            }
        }
    }

    /**
     * Traps Tab / Shift+Tab keyboard focus strictly within modalElement.
     * Returns a cleanup function to remove the listener on modal close.
     */
    static bindFocusTrap(modalElement: HTMLElement): () => void {
        const focusableSelector = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

        const handleKeydown = (e: KeyboardEvent) => {
            if (e.key !== 'Tab') return;

            const focusables = Array.from(modalElement.querySelectorAll<HTMLElement>(focusableSelector))
                .filter(el => el.offsetParent !== null);

            if (focusables.length === 0) return;

            const firstEl = focusables[0];
            const lastEl = focusables[focusables.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === firstEl || !modalElement.contains(document.activeElement)) {
                    e.preventDefault();
                    lastEl.focus();
                }
            } else {
                if (document.activeElement === lastEl || !modalElement.contains(document.activeElement)) {
                    e.preventDefault();
                    firstEl.focus();
                }
            }
        };

        modalElement.addEventListener('keydown', handleKeydown);
        return () => {
            modalElement.removeEventListener('keydown', handleKeydown);
        };
    }
}
