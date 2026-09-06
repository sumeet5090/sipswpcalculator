import { DOMAdapter } from '../../adapters/DOMAdapter';
import { WebHapticEngine } from '../helpers/WebHapticEngine';

/**
 * BottomSheetGestureController.ts
 * Implements physics-driven touch drag-to-dismiss gestures for mobile `<dialog>` bottom sheets.
 * Adheres strictly to iOS HIG and Material Design 3 sheet ergonomics:
 * - Directional slope locking (deltaY > deltaX, deltaY > 0)
 * - GPU-accelerated requestAnimationFrame translateY transforms
 * - Dynamic velocity calculation on touchend (deltaY / dt)
 * - Threshold dismissal (>80px or >0.4px/ms flick) with mechanical haptic tick
 * - Seamless cancel / spring reset on sub-threshold drag
 */
export class BottomSheetGestureController {
    private dom: DOMAdapter;
    private boundDialogs: Set<HTMLDialogElement> = new Set();
    private activeDialog: HTMLDialogElement | null = null;
    private startY: number = 0;
    private startX: number = 0;
    private currentY: number = 0;
    private startTime: number = 0;
    private isDragging: boolean = false;
    private rafId: number | null = null;

    constructor(dom: DOMAdapter = new DOMAdapter()) {
        this.dom = dom;
    }

    /**
     * Initializes gesture bindings across all bottom sheet dialogs and drag handles.
     */
    public init(): void {
        if (typeof window === 'undefined' || !('ontouchstart' in window)) {
            return;
        }

        // Query all modal drag handles
        const handles = document.querySelectorAll<HTMLElement>('.modal-drag-handle');
        handles.forEach((handle) => {
            const dialog = handle.closest('dialog');
            if (dialog) {
                this.bindSheetGestures(dialog, handle);
            }
        });

        // Query well-known bottom sheets directly
        const knownSheetIds = ['mobile-actions-sheet', 'qr-share-modal', 'sebiBenchmarkModal'];
        knownSheetIds.forEach((id) => {
            const dialog = this.dom.getElement<HTMLDialogElement>(id);
            if (dialog && !this.boundDialogs.has(dialog)) {
                const handle = dialog.querySelector<HTMLElement>('.modal-drag-handle') || dialog;
                this.bindSheetGestures(dialog, handle);
            }
        });
    }

    /**
     * Binds touch lifecycle events to a dialog sheet and its drag handle.
     */
    public bindSheetGestures(dialog: HTMLDialogElement, handle: HTMLElement): void {
        if (this.boundDialogs.has(dialog)) return;
        this.boundDialogs.add(dialog);

        handle.style.touchAction = 'pan-y';

        const onTouchStart = (e: TouchEvent) => {
            if (!dialog.open || e.touches.length !== 1) return;
            const touch = e.touches[0];
            this.activeDialog = dialog;
            this.startY = touch.clientY;
            this.startX = touch.clientX;
            this.currentY = touch.clientY;
            this.startTime = Date.now();
            this.isDragging = true;
            dialog.style.transition = 'none';
        };

        const onTouchMove = (e: TouchEvent) => {
            if (!this.isDragging || this.activeDialog !== dialog || e.touches.length !== 1) return;
            const touch = e.touches[0];
            const deltaY = touch.clientY - this.startY;
            const deltaX = touch.clientX - this.startX;

            // Directional slope lock: only downward vertical displacement
            if (deltaY <= 0 || Math.abs(deltaY) < Math.abs(deltaX)) {
                return;
            }

            this.currentY = touch.clientY;
            if (e.cancelable) {
                e.preventDefault();
            }

            if (this.rafId !== null) {
                cancelAnimationFrame(this.rafId);
            }

            this.rafId = requestAnimationFrame(() => {
                if (this.activeDialog) {
                    // Slight resistance rubber-banding
                    const translation = deltaY;
                    this.activeDialog.style.transform = `translateY(${translation}px)`;
                }
            });
        };

        const onTouchEnd = () => {
            if (!this.isDragging || this.activeDialog !== dialog) return;
            this.isDragging = false;

            if (this.rafId !== null) {
                cancelAnimationFrame(this.rafId);
                this.rafId = null;
            }

            const deltaY = this.currentY - this.startY;
            const elapsed = Math.max(1, Date.now() - this.startTime);
            const velocity = deltaY / elapsed; // px per ms

            const shouldDismiss = deltaY > 80 || (deltaY > 30 && velocity > 0.4);

            if (shouldDismiss) {
                // Animate complete dismissal off bottom
                dialog.style.transition = 'transform 0.2s cubic-bezier(0.32, 1, 0.23, 1)';
                dialog.style.transform = 'translateY(100%)';
                WebHapticEngine.triggerTick(30);

                const handleTransitionEnd = () => {
                    dialog.removeEventListener('transitionend', handleTransitionEnd);
                    dialog.close();
                    dialog.style.transform = '';
                    dialog.style.transition = '';
                };
                dialog.addEventListener('transitionend', handleTransitionEnd, { once: true });

                // Fallback in case transition event doesn't fire
                setTimeout(() => {
                    if (dialog.open) {
                        dialog.close();
                        dialog.style.transform = '';
                        dialog.style.transition = '';
                    }
                }, 250);
            } else {
                // Spring back to resting position
                dialog.style.transition = 'transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1)';
                dialog.style.transform = 'translateY(0px)';
                setTimeout(() => {
                    dialog.style.transform = '';
                    dialog.style.transition = '';
                }, 260);
            }

            this.activeDialog = null;
        };

        handle.addEventListener('touchstart', onTouchStart, { passive: true });
        handle.addEventListener('touchmove', onTouchMove, { passive: false });
        handle.addEventListener('touchend', onTouchEnd, { passive: true });
        handle.addEventListener('touchcancel', onTouchEnd, { passive: true });
    }
}
