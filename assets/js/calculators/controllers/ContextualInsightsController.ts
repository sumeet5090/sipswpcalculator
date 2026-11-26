import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';
import type { InvestmentInputs, YearResult } from '../../types';

/**
 * ContextualInsightsController
 * Manages summary KPI badges (Ratio, Rule of 72, Daily Accrual, Crossover),
 * the priority-ranked Contextual Alpha Radar, live conversational narrative tokens,
 * and the Section 112A Tax Waterfall breakdown modal.
 */
export class ContextualInsightsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    public updateInsights(
        data: YearResult[],
        inputs: InvestmentInputs,
        totalInvested: number,
        finalGains: number,
        preTaxCorpus: number,
        preTaxGains: number
    ): void {
        const lastRow = data[data.length - 1];
        if (!lastRow) return;

        const crossoverYear = data.find(r => r.annual_contribution > 0 && r.interest > r.annual_contribution)?.year;
        const crossoverBadge = this.dom.getElement('summary-crossover-badge');
        if (crossoverBadge) {
            if (crossoverYear) {
                crossoverBadge.textContent = `🚀 Crossover in Year ${crossoverYear} (Annual Gains > Annual SIP)`;
                crossoverBadge.style.display = 'inline-flex';
            } else {
                crossoverBadge.style.display = 'none';
            }
        }

        const ratioPill = this.dom.getElement('summary-ratio-pill');
        if (ratioPill) {
            const totalSum = totalInvested + finalGains;
            if (totalSum > 0) {
                const invPct = Math.round((totalInvested / totalSum) * 100);
                const gainPct = 100 - invPct;
                ratioPill.textContent = `${invPct}% Invested • ${gainPct}% Compounded Profit`;
                ratioPill.style.display = 'inline-flex';
            } else {
                ratioPill.style.display = 'none';
            }
        }

        const doublingBadge = this.dom.getElement('summary-doubling-badge');
        if (doublingBadge) {
            const effRate = inputs.years > 0 ? inputs.rate : (inputs.enable_swp ? inputs.swp_rate : inputs.rate);
            if (effRate > 0) {
                const doublingYrs = (72 / effRate).toFixed(1);
                doublingBadge.textContent = `⏳ Capital doubles every ${doublingYrs} yrs (@${effRate}%)`;
                doublingBadge.style.display = 'inline-flex';
            } else {
                doublingBadge.style.display = 'none';
            }
        }

        const dailyAccrual = this.dom.getElement('summary-daily-accrual');
        if (dailyAccrual) {
            const lastInterest = lastRow.interest || 0;
            if (lastInterest > 0) {
                const daily = Math.round(lastInterest / 365);
                dailyAccrual.textContent = `⚡ Compounding at ${this.formatter.format(daily)}/day at maturity`;
                dailyAccrual.style.display = 'inline-flex';
            } else {
                dailyAccrual.style.display = 'none';
            }
        }

        this.updateContextualRadar(data, inputs, totalInvested, finalGains);

        const taxGross = this.dom.getElement('tax-modal-gross-gains');
        const taxTaxable = this.dom.getElement('tax-modal-taxable-gains');
        const taxAmount = this.dom.getElement('tax-modal-tax-amount');
        const taxNet = this.dom.getElement('tax-modal-net-corpus');
        const taxHarvestSavings = this.dom.getElement('tax-modal-harvest-savings');
        const taxHarvestEffective = this.dom.getElement('tax-modal-harvest-effective');

        const ltcgExemption = inputs.ltcg_exemption ?? 125000;
        if (taxGross) taxGross.textContent = this.formatter.format(preTaxGains);
        if (taxTaxable) taxTaxable.textContent = this.formatter.format(Math.max(0, preTaxGains - ltcgExemption));
        if (taxAmount) taxAmount.textContent = `- ${this.formatter.format(lastRow.ltcg_tax ?? 0)}`;
        if (taxNet) taxNet.textContent = this.formatter.format(lastRow.post_tax_total ?? preTaxCorpus);

        const harvestAlpha = MathEngine.calculateTaxHarvestingSavings(inputs, data);
        if (taxHarvestSavings) taxHarvestSavings.textContent = `Save ${this.formatter.format(harvestAlpha.cumulativeSavings)}`;
        if (taxHarvestEffective) taxHarvestEffective.textContent = this.formatter.format(harvestAlpha.harvestedTax);
    }

    public updateContextualRadar(
        data: YearResult[],
        inputs: InvestmentInputs,
        totalInvested: number,
        finalGains: number
    ): void {
        const radarContainer = this.dom.getElement('contextual-alpha-radar');
        const radarIcon = this.dom.getElement('contextual-radar-icon');
        const radarText = this.dom.getElement('contextual-radar-text');
        if (!radarContainer || !radarText) return;

        if (inputs.enable_swp && inputs.swp_withdrawal > 0) {
            const depletedRow = data.find(r => r.year > inputs.years && r.combined_total <= 0);
            if (depletedRow) {
                radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-2xs mb-3 transition-all';
                if (radarIcon) radarIcon.textContent = '🚨';
                radarText.innerHTML = `<strong>Capital Exhaustion Warning:</strong> Portfolio depletes in Year ${depletedRow.year}. Consider safe-rate calibration.`;
                radarContainer.style.display = 'flex';
                return;
            }
        }

        const crossoverYear = data.find(r => r.annual_contribution > 0 && r.interest > r.annual_contribution)?.year;
        if (crossoverYear) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 text-emerald-900 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '🚀';
            radarText.innerHTML = `<strong>Wealth Crossover:</strong> In Year ${crossoverYear}, annual compounding gains surpass your annual invested principal.`;
            radarContainer.style.display = 'flex';
            return;
        }

        const effRate = inputs.years > 0 ? inputs.rate : (inputs.enable_swp ? inputs.swp_rate : inputs.rate);
        if (effRate > 0 && finalGains > totalInvested) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-900 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '💎';
            const doublingYrs = (72 / effRate).toFixed(1);
            radarText.innerHTML = `<strong>Compounding Multiplier:</strong> Capital doubles every ${doublingYrs} years with ${Math.round((finalGains / totalInvested) * 100)}% net wealth expansion.`;
            radarContainer.style.display = 'flex';
            return;
        }

        const lastRow = data[data.length - 1];
        const dailyRate = Math.round((lastRow?.interest || 0) / 365);
        if (dailyRate > 0) {
            radarContainer.className = 'flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200/90 text-slate-800 shadow-2xs mb-3 transition-all';
            if (radarIcon) radarIcon.textContent = '⚡';
            radarText.innerHTML = `<strong>Compounding Velocity:</strong> Generating <span class="font-mono font-bold text-emerald-700">${this.formatter.format(dailyRate)} / day</span> in passive interest at maturity.`;
            radarContainer.style.display = 'flex';
            return;
        }

        radarContainer.style.display = 'none';
    }

    public initTaxWaterfallModal(onOpen?: () => void): void {
        const modal = this.dom.getElement<HTMLDialogElement>('tax-waterfall-modal');
        const openBtn = this.dom.getElement('open-tax-waterfall-btn');
        const closeBtn = this.dom.getElement('close-tax-waterfall-btn');
        const footerCloseBtn = this.dom.getElement('close-tax-waterfall-footer-btn');

        if (!modal) return;

        let cleanupFocusTrap: (() => void) | null = null;
        const closeModal = () => {
            modal.close();
            if (cleanupFocusTrap) {
                cleanupFocusTrap();
                cleanupFocusTrap = null;
            }
            ModalScrollLockHelper.unlock();
        };

        if (openBtn) {
            openBtn.addEventListener('click', () => {
                modal.showModal();
                ModalScrollLockHelper.lock(openBtn);
                cleanupFocusTrap = ModalScrollLockHelper.bindFocusTrap(modal);
                onOpen?.();
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (footerCloseBtn) footerCloseBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        modal.addEventListener('cancel', () => {
            if (cleanupFocusTrap) {
                cleanupFocusTrap();
                cleanupFocusTrap = null;
            }
            ModalScrollLockHelper.unlock();
        });
    }

    public updateNarrative(inputs: InvestmentInputs): void {
        const narrativeSip = this.dom.getElement('narrative-sip');
        const narrativeLumpsumWrapper = this.dom.getElement('narrative-lumpsum-wrapper');
        const narrativeLumpsum = this.dom.getElement('narrative-lumpsum');
        const narrativeYears = this.dom.getElement('narrative-years');
        const narrativeRate = this.dom.getElement('narrative-rate');

        if (narrativeSip) narrativeSip.textContent = this.formatter.format(inputs.sip);
        if (narrativeLumpsumWrapper && narrativeLumpsum) {
            const lumpsumVal = inputs.lumpsum || 0;
            if (lumpsumVal > 0) {
                narrativeLumpsum.textContent = this.formatter.formatCompact(lumpsumVal);
                narrativeLumpsumWrapper.classList.remove('hidden');
                narrativeLumpsumWrapper.classList.add('inline-flex');
            } else {
                narrativeLumpsum.textContent = '';
                narrativeLumpsumWrapper.classList.add('hidden');
                narrativeLumpsumWrapper.classList.remove('inline-flex');
            }
        }
        if (narrativeYears) narrativeYears.textContent = `${inputs.years} yrs`;
        if (narrativeRate) narrativeRate.textContent = `${inputs.rate}% CAGR`;
    }
}
