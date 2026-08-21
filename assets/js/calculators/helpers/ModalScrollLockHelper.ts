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
}
