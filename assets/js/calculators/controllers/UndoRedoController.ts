import { InvestmentInputs } from '../../types';

/**
 * UndoRedoController.ts
 * Manages an in-memory 15-state linear history buffer for calculation parameters,
 * supporting keyboard shortcuts (Cmd+Z / Cmd+Shift+Z / Ctrl+Z / Ctrl+Y).
 */
export class UndoRedoController {
    private history: InvestmentInputs[] = [];
    private currentIndex: number = -1;
    private maxHistory: number = 15;
    private isRestoring: boolean = false;
    private onRestore: (inputs: InvestmentInputs) => void;

    constructor(onRestore: (inputs: InvestmentInputs) => void) {
        this.onRestore = onRestore;
        this.initKeyboardShortcuts();
    }

    /**
     * Push a new snapshot into history if different from the current state.
     */
    pushState(inputs: InvestmentInputs): void {
        if (this.isRestoring) return;

        // Compare with current state to avoid duplicate consecutive states
        if (this.currentIndex >= 0 && this.currentIndex < this.history.length) {
            const current = this.history[this.currentIndex];
            if (this.isEqual(current, inputs)) {
                return;
            }
        }

        // Truncate any future redo states if branching off a new change
        this.history = this.history.slice(0, this.currentIndex + 1);

        this.history.push({ ...inputs });

        // Maintain capacity limit
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        } else {
            this.currentIndex++;
        }
    }

    /**
     * Revert to previous parameter snapshot.
     */
    undo(): boolean {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            const target = this.history[this.currentIndex];
            this.applyState(target);
            return true;
        }
        return false;
    }

    /**
     * Advance to next parameter snapshot.
     */
    redo(): boolean {
        if (this.currentIndex < this.history.length - 1) {
            this.currentIndex++;
            const target = this.history[this.currentIndex];
            this.applyState(target);
            return true;
        }
        return false;
    }

    private applyState(target: InvestmentInputs): void {
        this.isRestoring = true;
        try {
            this.onRestore({ ...target });
        } finally {
            this.isRestoring = false;
        }
    }

    private initKeyboardShortcuts(): void {
        if (typeof window === 'undefined') return;

        window.addEventListener('keydown', (e: KeyboardEvent) => {
            // Do not intercept when user is focused inside a text input or textarea
            const target = e.target as HTMLElement | null;
            if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
                // Allow normal input undo inside text fields
                return;
            }

            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const modifier = isMac ? e.metaKey : e.ctrlKey;

            if (modifier && e.key.toLowerCase() === 'z') {
                if (e.shiftKey) {
                    // Redo (Cmd+Shift+Z or Ctrl+Shift+Z)
                    if (this.redo()) {
                        e.preventDefault();
                    }
                } else {
                    // Undo (Cmd+Z or Ctrl+Z)
                    if (this.undo()) {
                        e.preventDefault();
                    }
                }
            } else if (!isMac && modifier && e.key.toLowerCase() === 'y') {
                // Windows Redo (Ctrl+Y)
                if (this.redo()) {
                    e.preventDefault();
                }
            }
        });
    }

    private isEqual(a: InvestmentInputs, b: InvestmentInputs): boolean {
        return (
            a.sip === b.sip &&
            a.years === b.years &&
            a.rate === b.rate &&
            a.stepup === b.stepup &&
            a.inflation === b.inflation &&
            a.lumpsum === b.lumpsum &&
            a.enable_swp === b.enable_swp &&
            a.swp_withdrawal === b.swp_withdrawal &&
            a.swp_years === b.swp_years &&
            a.swp_stepup === b.swp_stepup &&
            a.swp_rate === b.swp_rate
        );
    }
}
