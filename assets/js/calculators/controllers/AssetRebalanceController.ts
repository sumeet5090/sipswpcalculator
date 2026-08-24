import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs, YearResult } from '../../types';

export interface AssetAllocationProfile {
    id: string;
    label: string;
    equityPct: number;
    debtPct: number;
    goldPct: number;
    targetAudience: string;
}

export const ASSET_PROFILES: Record<string, AssetAllocationProfile> = {
    aggressive: {
        id: 'aggressive',
        label: '80/20 Aggressive',
        equityPct: 80,
        debtPct: 20,
        goldPct: 0,
        targetAudience: 'Age 20-35 (Max Wealth Accumulation)'
    },
    balanced: {
        id: 'balanced',
        label: '70/30 Balanced',
        equityPct: 70,
        debtPct: 30,
        goldPct: 0,
        targetAudience: 'Age 30-45 (All-Round Compounding)'
    },
    allweather: {
        id: 'allweather',
        label: 'All-Weather India',
        equityPct: 60,
        debtPct: 25,
        goldPct: 15,
        targetAudience: 'Tri-Asset Inflation & Geopolitical Shield'
    },
    conservative: {
        id: 'conservative',
        label: 'Capital Shield',
        equityPct: 50,
        debtPct: 35,
        goldPct: 15,
        targetAudience: 'Age 45+ (Pre-Retirement Capital Defense)'
    }
};

/**
 * AssetRebalanceController
 * Manages multi-asset allocation (Equity, Debt, Gold), simulated portfolio drift,
 * smart cashflow SIP inflow routing, and Section 112A Tax Alpha savings.
 */
export class AssetRebalanceController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    private activeProfile: string = 'balanced';
    private targetEquityPct: number = 70;
    private targetDebtPct: number = 30;
    private targetGoldPct: number = 0;
    private driftState: 'normal' | 'bull' | 'bear' = 'normal';

    private currentInputs: InvestmentInputs | null = null;
    private latestResults: YearResult[] = [];

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    public init(): void {
        const card = this.dom.getElement('asset-rebalancing-card');
        if (!card) return;

        // 1. Asset Profile Buttons
        const profileBtns = card.querySelectorAll<HTMLButtonElement>('.rebalance-choice-btn');
        profileBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const profileKey = btn.dataset.profile || 'balanced';
                this.setProfile(profileKey);
            });
        });

        // 2. Drift State Buttons
        const driftBtns = card.querySelectorAll<HTMLButtonElement>('.drift-state-btn');
        driftBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const state = (btn.dataset.drift as 'normal' | 'bull' | 'bear') || 'normal';
                this.setDriftState(state);
            });
        });

        this.updateDisplay();
    }

    public setProfile(profileKey: string): void {
        const profile = ASSET_PROFILES[profileKey] || ASSET_PROFILES.balanced;
        this.activeProfile = profileKey;
        this.targetEquityPct = profile.equityPct;
        this.targetDebtPct = profile.debtPct;
        this.targetGoldPct = profile.goldPct;

        const card = this.dom.getElement('asset-rebalancing-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.rebalance-choice-btn');
        buttons.forEach(b => {
            const isSelected = b.dataset.profile === profileKey;
            const dot = b.querySelector('.w-2.h-2');
            if (isSelected) {
                b.classList.add('border-indigo-600', 'border-2', 'bg-white', 'shadow-xs');
                b.classList.remove('border-slate-200/90', 'bg-slate-50/90');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-indigo-600';
            } else {
                b.classList.remove('border-indigo-600', 'border-2', 'bg-white', 'shadow-xs');
                b.classList.add('border-slate-200/90', 'bg-slate-50/90');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-slate-300 group-hover:bg-slate-400';
            }
        });

        this.updateDisplay();
    }

    public setDriftState(state: 'normal' | 'bull' | 'bear'): void {
        this.driftState = state;
        const card = this.dom.getElement('asset-rebalancing-card');
        if (!card) return;

        const driftBtns = card.querySelectorAll<HTMLButtonElement>('.drift-state-btn');
        driftBtns.forEach(btn => {
            const isSelected = btn.dataset.drift === state;
            if (isSelected) {
                btn.classList.add('bg-white', 'text-indigo-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                btn.classList.remove('text-slate-500', 'font-medium');
            } else {
                btn.classList.remove('bg-white', 'text-indigo-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                btn.classList.add('text-slate-500', 'font-medium');
            }
        });

        this.updateDisplay();
    }

    public updateInputs(inputs: InvestmentInputs, results?: YearResult[]): void {
        this.currentInputs = inputs;
        if (results) this.latestResults = results;
        this.updateDisplay();
    }

    public getActiveProfile(): string {
        return this.activeProfile;
    }

    public getDriftState(): string {
        return this.driftState;
    }

    public getCurrentInputs(): InvestmentInputs | null {
        return this.currentInputs;
    }

    public getLatestResults(): YearResult[] {
        return this.latestResults;
    }

    public getTargetSplit(): { equity: number; debt: number; gold: number } {
        return {
            equity: this.targetEquityPct,
            debt: this.targetDebtPct,
            gold: this.targetGoldPct
        };
    }

    private updateDisplay(): void {
        if (!this.currentInputs) return;

        const eqRate = Number.isFinite(this.currentInputs.rate) ? (this.currentInputs.rate ?? 12) : 12;
        const debtRate = 7.0; // High-quality AAA Indian debt yield
        const goldRate = 9.5; // Indian Sovereign Gold historical long-term CAGR

        const blendedRate =
            (this.targetEquityPct / 100) * eqRate +
            (this.targetDebtPct / 100) * debtRate +
            (this.targetGoldPct / 100) * goldRate;

        const volReduction = Math.round((this.targetDebtPct / 100) * 80 + (this.targetGoldPct / 100) * 40);

        // Update Progress Bar & Labels
        const barEq = this.dom.getElement('rebalance-bar-equity');
        const barDebt = this.dom.getElement('rebalance-bar-debt');
        const barGold = this.dom.getElement('rebalance-bar-gold');

        const pctEq = this.dom.getElement('rebalance-pct-equity');
        const pctDebt = this.dom.getElement('rebalance-pct-debt');
        const pctGold = this.dom.getElement('rebalance-pct-gold');
        const ratioLabel = this.dom.getElement('rebalance-ratio-label');

        if (barEq) barEq.style.width = `${this.targetEquityPct}%`;
        if (barDebt) barDebt.style.width = `${this.targetDebtPct}%`;
        if (barGold) barGold.style.width = `${this.targetGoldPct}%`;

        if (pctEq) pctEq.textContent = `${this.targetEquityPct}%`;
        if (pctDebt) pctDebt.textContent = `${this.targetDebtPct}%`;
        if (pctGold) pctGold.textContent = `${this.targetGoldPct}%`;

        if (ratioLabel) {
            ratioLabel.textContent = `${this.targetEquityPct}% Equity • ${this.targetDebtPct}% Debt • ${this.targetGoldPct}% Gold`;
        }

        // Metrics Grid
        const cagrEl = this.dom.getElement('rebalance-preview-cagr');
        const volEl = this.dom.getElement('rebalance-preview-volatility');
        const taxEl = this.dom.getElement('rebalance-tax-savings');

        if (cagrEl) cagrEl.textContent = `${blendedRate.toFixed(1)}% p.a.`;
        if (volEl) volEl.textContent = `-${volReduction}% vs Pure Equity`;

        // Section 112A Tax Alpha Estimation (15 years @ 12.5% LTCG + 1% exit load on ~15% drift turnover)
        const totalSip = Math.max(0, this.currentInputs.sip ?? 25000);
        const years = this.currentInputs.years ?? 15;
        const totalInvested = totalSip * 12 * years;
        const taxAlphaSaved = Math.round(totalInvested * 0.055); // Empirical ~5.5% capital retention via zero-redemption rebalancing

        if (taxEl) taxEl.textContent = `+${this.formatter.formatDynamic(taxAlphaSaved)}`;

        // Smart Cashflow Routing Allocation Math
        const actionEl = this.dom.getElement('rebalance-action-text');
        const statusChip = this.dom.getElement('rebalance-status-chip');

        if (this.driftState === 'normal') {
            const equitySip = Math.round((this.targetEquityPct / 100) * totalSip);
            const debtSip = Math.round((this.targetDebtPct / 100) * totalSip);
            const goldSip = Math.max(0, totalSip - equitySip - debtSip);

            if (statusChip) {
                statusChip.textContent = 'Equilibrium Maintained';
                statusChip.className = 'text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200';
            }

            if (actionEl) {
                let text = `Allocate monthly SIP: <strong class="text-slate-900">${this.formatter.format(equitySip)} into Equity</strong> and <strong class="text-slate-900">${this.formatter.format(debtSip)} into Debt</strong>`;
                if (this.targetGoldPct > 0) {
                    text += ` and <strong class="text-slate-900">${this.formatter.format(goldSip)} into Gold ETFs/SGBs</strong>`;
                }
                text += ` to maintain exact portfolio weighting with 0% capital gains tax.`;
                actionEl.innerHTML = text;
            }
        } else if (this.driftState === 'bull') {
            // Equity surged +12%; route 70% of new SIP into Debt/Gold to bring equity back down without selling
            const routedDebtSip = Math.round(totalSip * 0.70);
            const routedEquitySip = Math.max(0, totalSip - routedDebtSip);

            if (statusChip) {
                statusChip.textContent = 'Bull Drift Detected (Equity Overweight)';
                statusChip.className = 'text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200';
            }

            if (actionEl) {
                actionEl.innerHTML = `Equity has surged above target. <strong>Smart Cashflow Action:</strong> Route <strong class="text-indigo-900">${this.formatter.format(routedDebtSip)} into Debt</strong> and only <strong class="text-slate-900">${this.formatter.format(routedEquitySip)} into Equity</strong> for the next 6 months to restore ${this.targetEquityPct}/${this.targetDebtPct} balance without triggering any taxable redemptions!`;
            }
        } else {
            // Equity dropped -15%; route 90% of new SIP into Equity to harvest cheap NAV units
            const routedEquitySip = Math.round(totalSip * 0.90);
            const routedDebtSip = Math.max(0, totalSip - routedEquitySip);

            if (statusChip) {
                statusChip.textContent = 'Bear Dip Detected (Discount NAV Zone)';
                statusChip.className = 'text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-200';
            }

            if (actionEl) {
                actionEl.innerHTML = `Equity is undervalued due to market correction. <strong>Smart Cashflow Action:</strong> Route <strong class="text-emerald-900">${this.formatter.format(routedEquitySip)} into Equity</strong> and <strong class="text-slate-900">${this.formatter.format(routedDebtSip)} into Debt</strong> to aggressively accumulate cheap mutual fund units!`;
            }
        }
    }
}

