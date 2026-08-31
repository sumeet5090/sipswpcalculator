/**
 * save-calculation.ts
 * Client-side localStorage persistence for user calculations.
 * Enables returning visitors to review and reload previously saved financial plans.
 */

export interface SavedCalculation {
    id: string;
    title: string;
    calcType: string;
    sipAmount: number;
    sipDuration: number;
    interestRate: number;
    stepUpPct: number;
    swpEnabled: boolean;
    swpWithdrawal?: number;
    swpDuration?: number;
    swpRate?: number;
    swpStepUp?: number;
    finalCorpus?: string;
    savedAt: string;
}

const STORAGE_KEY = 'sipswp_saved_plans_v1';
const MAX_SAVED = 10;

/**
 * Safely save a calculation to localStorage with FIFO eviction.
 */
export function saveCalculation(calc: Omit<SavedCalculation, 'id' | 'savedAt'>): boolean {
    try {
        const existing = loadCalculations();
        const newEntry: SavedCalculation = {
            ...calc,
            id: 'calc_' + Date.now().toString(36) + Math.random().toString(36).substring(2, 6),
            savedAt: new Date().toISOString()
        };

        existing.unshift(newEntry);
        const trimmed = existing.slice(0, MAX_SAVED);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(trimmed));
        return true;
    } catch {
        // QuotaExceededError or security restrictions in private mode
        return false;
    }
}

/**
 * Load all saved calculations from localStorage.
 */
export function loadCalculations(): SavedCalculation[] {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return [];
        }
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

/**
 * Remove a specific calculation by ID.
 */
export function deleteCalculation(id: string): void {
    try {
        const existing = loadCalculations();
        const filtered = existing.filter(c => c.id !== id);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    } catch {
        // Safe fail
    }
}

/**
 * Clear all saved calculations.
 */
export function clearCalculations(): void {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Safe fail
    }
}

/**
 * Initialize Save Plan UI listener on the Breakdown Table Action Bar.
 */
export function initSaveCalculationUI(): void {
    const saveBtn = document.getElementById('saveCalculationBtn');
    if (!saveBtn) {
        return;
    }

    saveBtn.addEventListener('click', () => {
        const sipInput = document.getElementById('sip_amount') as HTMLInputElement | null;
        const durationInput = document.getElementById('duration') as HTMLInputElement | null;
        const rateInput = document.getElementById('interest_rate') as HTMLInputElement | null;
        const stepUpInput = document.getElementById('step_up_pct') as HTMLInputElement | null;
        const swpToggle = document.getElementById('swp_enabled') as HTMLInputElement | null;
        const corpusDisplay = document.getElementById('summary-final-corpus') || document.getElementById('mini-hud-corpus');

        const sip = sipInput ? parseFloat(sipInput.value) || 10000 : 10000;
        const duration = durationInput ? parseInt(durationInput.value, 10) || 15 : 15;
        const rate = rateInput ? parseFloat(rateInput.value) || 12 : 12;
        const stepUp = stepUpInput ? parseFloat(stepUpInput.value) || 0 : 0;
        const swpEnabled = swpToggle ? swpToggle.checked : false;
        const corpus = corpusDisplay ? corpusDisplay.textContent?.trim() || '' : '';

        const success = saveCalculation({
            title: `₹${sip.toLocaleString('en-IN')}/mo • ${duration}Y @ ${rate}%`,
            calcType: 'sip_swp',
            sipAmount: sip,
            sipDuration: duration,
            interestRate: rate,
            stepUpPct: stepUp,
            swpEnabled: swpEnabled,
            finalCorpus: corpus
        });

        const iconEl = document.getElementById('saveBtnIcon');
        const textEl = document.getElementById('saveBtnText');

        if (success && textEl) {
            const originalText = textEl.textContent || 'Save Plan';
            textEl.textContent = 'Saved! ✓';
            if (iconEl) iconEl.textContent = '✅';
            saveBtn.classList.add('bg-emerald-100/90', 'border-emerald-300', 'text-emerald-900');
            saveBtn.classList.remove('bg-amber-50/90', 'border-amber-200', 'text-amber-900');

            setTimeout(() => {
                textEl.textContent = originalText;
                if (iconEl) iconEl.textContent = '💾';
                saveBtn.classList.remove('bg-emerald-100/90', 'border-emerald-300', 'text-emerald-900');
                saveBtn.classList.add('bg-amber-50/90', 'border-amber-200', 'text-amber-900');
            }, 2500);
        }
    });
}
