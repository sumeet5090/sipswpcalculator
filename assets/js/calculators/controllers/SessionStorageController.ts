import { InvestmentInputs } from '../../types';

interface StoredDraft {
    mode: string;
    inputs: InvestmentInputs;
    timestamp: number;
}

/**
 * SessionStorageController.ts
 * Manages automatic debounced local session drafts in sessionStorage to prevent
 * accidental data loss during tab reloads or external link navigation.
 * Strictly namespaced by calculator mode to eliminate cross-calculator draft pollution.
 */
export class SessionStorageController {
    private static LEGACY_STORAGE_KEY = 'sip_swp_calc_draft_state_v1';
    private static STORAGE_KEY_PREFIX = 'sip_swp_calc_draft_';
    private static STORAGE_KEY_VERSION = '_v2';
    private debounceTimer: ReturnType<typeof setTimeout> | null = null;

    constructor() {
        this.purgeLegacyDraft();
    }

    private getStorageKey(mode: string): string {
        const cleanMode = (mode || 'combo').trim().toLowerCase();
        return `${SessionStorageController.STORAGE_KEY_PREFIX}${cleanMode}${SessionStorageController.STORAGE_KEY_VERSION}`;
    }

    /**
     * Purges legacy un-namespaced draft key to eliminate corrupted states from older sessions.
     */
    public purgeLegacyDraft(): void {
        if (typeof window === 'undefined' || !window.sessionStorage) return;
        try {
            sessionStorage.removeItem(SessionStorageController.LEGACY_STORAGE_KEY);
        } catch {
            // Ignore
        }
    }

    /**
     * Debounced persist of current calculation inputs to sessionStorage namespaced by mode.
     */
    persistDraft(mode: string, inputs: InvestmentInputs, delayMs: number = 400): void {
        if (typeof window === 'undefined' || !window.sessionStorage) return;

        // Anti-degeneracy: Never persist degenerate or broken input vectors
        if (!this.isValidDraftForMode(mode, inputs)) {
            return;
        }

        if (this.debounceTimer !== null) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            try {
                const key = this.getStorageKey(mode);
                const payload: StoredDraft = {
                    mode,
                    inputs,
                    timestamp: Date.now()
                };
                sessionStorage.setItem(key, JSON.stringify(payload));
            } catch {
                // Ignore storage quota errors silently
            }
        }, delayMs);
    }

    /**
     * Retrieve stored draft if valid, matching the requested mode, and created within 24 hours.
     */
    loadDraft(mode: string): InvestmentInputs | null {
        if (typeof window === 'undefined' || !window.sessionStorage) return null;

        try {
            const key = this.getStorageKey(mode);
            const raw = sessionStorage.getItem(key);
            if (!raw) return null;

            const parsed = JSON.parse(raw) as StoredDraft;
            if (!parsed || typeof parsed !== 'object' || !parsed.inputs) return null;

            // Validate matching mode
            if (parsed.mode && parsed.mode.toLowerCase() !== mode.toLowerCase()) {
                this.clearDraft(mode);
                return null;
            }

            // Expire drafts older than 24 hours
            const maxAgeMs = 24 * 60 * 60 * 1000;
            if (Date.now() - parsed.timestamp > maxAgeMs) {
                this.clearDraft(mode);
                return null;
            }

            // Validate against degeneracy guardrails
            if (!this.isValidDraftForMode(mode, parsed.inputs)) {
                this.clearDraft(mode);
                return null;
            }

            return parsed.inputs;
        } catch {
            return null;
        }
    }

    /**
     * Sanity & anti-degeneracy validation guardrails.
     * Rejects any draft with flatlines (e.g. 1 year, 1% rate, or minimum clamped inputs).
     */
    public isValidDraftForMode(mode: string, inputs: InvestmentInputs): boolean {
        if (!inputs || typeof inputs !== 'object') return false;

        const m = (mode || 'combo').toLowerCase();

        if (m === 'combo' || m === 'sip' || m === 'sip-step-up') {
            // Must have at least 2 years and 2% rate
            if ((inputs.years ?? 0) < 2) return false;
            if ((inputs.rate ?? 0) < 2) return false;
            // Must have active SIP >= 1000 or lumpsum >= 10000
            const hasSip = (inputs.sip ?? 0) >= 1000;
            const hasLumpsum = (inputs.lumpsum ?? 0) >= 10000;
            if (!hasSip && !hasLumpsum) return false;
            return true;
        }

        if (m === 'swp') {
            if ((inputs.swp_years ?? 0) < 2) return false;
            if ((inputs.swp_rate ?? 0) < 2) return false;
            if ((inputs.lumpsum ?? 0) < 10000 && (inputs.swp_withdrawal ?? 0) <= 0) return false;
            return true;
        }

        if (m === 'lumpsum') {
            if ((inputs.years ?? 0) < 2) return false;
            if ((inputs.rate ?? 0) < 2) return false;
            if ((inputs.lumpsum ?? 0) < 10000) return false;
            return true;
        }

        if (m === 'target_corpus' || m === 'target') {
            if ((inputs.years ?? 0) < 2) return false;
            if ((inputs.rate ?? 0) < 2) return false;
            return true;
        }

        // For specialized modes, default true if defined
        return true;
    }

    /**
     * Clear stored draft for a mode or all calculator drafts.
     */
    clearDraft(mode?: string): void {
        if (typeof window === 'undefined' || !window.sessionStorage) return;
        try {
            if (mode) {
                sessionStorage.removeItem(this.getStorageKey(mode));
            } else {
                // Clear all modes
                const modes = ['combo', 'sip', 'swp', 'lumpsum', 'sip-step-up', 'target_corpus', 'target', 'compound_interest', 'cagr', 'emi', 'inflation', 'ppf', 'fd'];
                modes.forEach(m => sessionStorage.removeItem(this.getStorageKey(m)));
                sessionStorage.removeItem(SessionStorageController.LEGACY_STORAGE_KEY);
            }
        } catch {
            // Ignore
        }
    }
}
