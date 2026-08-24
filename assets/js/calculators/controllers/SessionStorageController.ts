import { InvestmentInputs } from '../../types';

interface StoredDraft {
    inputs: InvestmentInputs;
    timestamp: number;
}

/**
 * SessionStorageController.ts
 * Manages automatic debounced local session drafts in sessionStorage to prevent
 * accidental data loss during tab reloads or external link navigation.
 */
export class SessionStorageController {
    private static STORAGE_KEY = 'sip_swp_calc_draft_state_v1';
    private debounceTimer: ReturnType<typeof setTimeout> | null = null;

    /**
     * Debounced persist of current calculation inputs to sessionStorage.
     */
    persistDraft(inputs: InvestmentInputs, delayMs: number = 400): void {
        if (typeof window === 'undefined' || !window.sessionStorage) return;

        if (this.debounceTimer !== null) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            try {
                const payload: StoredDraft = {
                    inputs,
                    timestamp: Date.now()
                };
                sessionStorage.setItem(SessionStorageController.STORAGE_KEY, JSON.stringify(payload));
            } catch {
                // Ignore storage quota errors silently
            }
        }, delayMs);
    }

    /**
     * Retrieve stored draft if valid and created within the last 24 hours.
     */
    loadDraft(): InvestmentInputs | null {
        if (typeof window === 'undefined' || !window.sessionStorage) return null;

        try {
            const raw = sessionStorage.getItem(SessionStorageController.STORAGE_KEY);
            if (!raw) return null;

            const parsed = JSON.parse(raw) as StoredDraft;
            if (!parsed || typeof parsed !== 'object' || !parsed.inputs) return null;

            // Expire drafts older than 24 hours
            const maxAgeMs = 24 * 60 * 60 * 1000;
            if (Date.now() - parsed.timestamp > maxAgeMs) {
                this.clearDraft();
                return null;
            }

            return parsed.inputs;
        } catch {
            return null;
        }
    }

    /**
     * Clear stored draft.
     */
    clearDraft(): void {
        if (typeof window === 'undefined' || !window.sessionStorage) return;
        try {
            sessionStorage.removeItem(SessionStorageController.STORAGE_KEY);
        } catch {
            // Ignore
        }
    }
}
