import { CalculatorRegistry } from '../constants/CalculatorRegistry.ts';
import type { InvestmentInputs } from '../../types';

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
        return false;
    }
}

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

export function deleteCalculation(id: string): void {
    try {
        const existing = loadCalculations();
        const filtered = existing.filter(c => c.id !== id);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
    } catch {
        // Safe fail
    }
}

export function clearCalculations(): void {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Safe fail
    }
}

export interface SavedPlansBackupPayload {
    version: number;
    exportedAt: string;
    plans: SavedCalculation[];
}

export interface ImportPlansResult {
    imported: number;
    total: number;
    error?: string;
}

export function exportCalculationsJson(): string {
    const payload: SavedPlansBackupPayload = {
        version: 1,
        exportedAt: new Date().toISOString(),
        plans: loadCalculations()
    };
    return JSON.stringify(payload, null, 2);
}

function isValidSavedCalculation(item: unknown): item is SavedCalculation {
    if (!item || typeof item !== 'object') return false;
    const obj = item as Record<string, unknown>;
    return (
        typeof obj.title === 'string' && obj.title.trim().length > 0 &&
        typeof obj.calcType === 'string' && obj.calcType.trim().length > 0 &&
        typeof obj.sipAmount === 'number' && Number.isFinite(obj.sipAmount) &&
        typeof obj.sipDuration === 'number' && Number.isFinite(obj.sipDuration) &&
        typeof obj.interestRate === 'number' && Number.isFinite(obj.interestRate)
    );
}

export function importCalculationsJson(rawJson: string): ImportPlansResult {
    try {
        const parsed: unknown = JSON.parse(rawJson);
        const candidates = Array.isArray(parsed)
            ? parsed
            : (parsed && typeof parsed === 'object' && Array.isArray((parsed as Record<string, unknown>).plans))
                ? (parsed as Record<string, unknown>).plans as unknown[]
                : null;

        if (!candidates) {
            return { imported: 0, total: loadCalculations().length, error: 'Invalid backup format.' };
        }

        const existing = loadCalculations();
        const seenIds = new Set(existing.map(p => p.id));
        const validNew: SavedCalculation[] = [];

        for (const item of candidates) {
            if (!isValidSavedCalculation(item)) continue;
            const id = typeof item.id === 'string' && item.id.trim() !== ''
                ? item.id
                : 'calc_' + Date.now().toString(36) + Math.random().toString(36).substring(2, 6);
            if (seenIds.has(id)) continue;
            seenIds.add(id);
            validNew.push({
                ...item,
                id,
                savedAt: typeof item.savedAt === 'string' && item.savedAt !== ''
                    ? item.savedAt
                    : new Date().toISOString()
            });
        }

        const merged = [...validNew, ...existing].slice(0, MAX_SAVED);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(merged));
        return { imported: validNew.length, total: merged.length };
    } catch {
        return { imported: 0, total: loadCalculations().length, error: 'Could not parse JSON file.' };
    }
}

function fmtInr(val: unknown): string {
    const num = typeof val === 'number' ? val : parseFloat(String(val ?? '0'));
    return Number.isFinite(num) ? Math.round(num).toLocaleString('en-IN') : '0';
}

export function buildSavedPlanTitle(
    mode: string,
    payload: Record<string, string | number | boolean>,
    inputs: InvestmentInputs
): string {
    switch (mode) {
        case 'swp':
            return `SWP ₹${fmtInr(inputs.swp_withdrawal)}/mo • ${inputs.swp_years}Y @ ${inputs.swp_rate}%`;
        case 'lumpsum':
            return `Lumpsum ₹${fmtInr(inputs.lumpsum)} • ${inputs.years}Y @ ${inputs.rate}%`;
        case 'target_corpus':
        case 'target': {
            const targetVal = payload.target_corpus
                ?? (document.getElementById('target_corpus') as HTMLInputElement | null)?.value
                ?? '0';
            return `Target ₹${fmtInr(targetVal)} • ${inputs.years}Y @ ${inputs.rate}%`;
        }
        case 'emi':
            return `Loan ₹${fmtInr(payload.emi_principal)} • ${payload.emi_years ?? '20'}Y @ ${payload.emi_rate ?? '8.5'}%`;
        case 'ppf':
            return `PPF ₹${fmtInr(payload.ppf_deposit)}/yr • ${payload.ppf_years ?? '15'}Y @ ${payload.ppf_rate ?? '7.1'}%`;
        case 'fd':
            return `FD ₹${fmtInr(payload.fd_principal)} • ${payload.fd_years ?? '5'}Y @ ${payload.fd_rate ?? '7.5'}%`;
        case 'compound_interest':
            return `CI ₹${fmtInr(payload.ci_principal)} • ${payload.ci_years ?? '10'}Y @ ${payload.ci_rate ?? '10'}%`;
        case 'cagr':
            return `CAGR ₹${fmtInr(payload.cagr_initial)} to ₹${fmtInr(payload.cagr_final)} • ${payload.cagr_years ?? '5'}Y`;
        case 'inflation':
            return `Inflation ₹${fmtInr(payload.inf_amount)} • ${payload.inf_years ?? '10'}Y @ ${payload.inf_rate ?? '6'}%`;
        case 'gratuity':
            return `Gratuity ₹${fmtInr(payload.gratuity_salary)} • ${payload.gratuity_years ?? '5'}Y`;
        case 'nps':
            return `NPS ₹${fmtInr(payload.nps_monthly_contribution)}/mo @ ${payload.nps_expected_return ?? '10'}%`;
        case 'post_retirement':
            return `Retirement Drawdown ₹${fmtInr(payload.post_ret_expense)}/mo`;
        case 'epf':
            return `EPF Basic ₹${fmtInr(payload.epf_basic_salary)} @ ${payload.epf_interest_rate ?? '8.25'}%`;
        case 'tax_harvest':
            return `LTCG Harvest ₹${fmtInr(payload.tax_portfolio_value)}`;
        case 'income_tax':
            return `Income Tax ₹${fmtInr(payload.it_gross_salary)} CTC`;
        default: {
            if (CalculatorRegistry.isSpecializedMode(mode)) {
                return CalculatorRegistry.getShareTitle(mode);
            }
            return `₹${fmtInr(inputs.sip)}/mo • ${inputs.years}Y @ ${inputs.rate}%`;
        }
    }
}
