import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { A11yAnnouncer } from '../helpers/A11yAnnouncer';
import type { InvestmentInputs, YearResult } from '../../types';

/**
 * LongevityGuardianController
 * Empathetic SWR Longevity Guardian that detects premature SWP depletion
 * and provides a 1-click "Auto-Heal" button to set sustainable withdrawals.
 */
export class LongevityGuardianController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private onApplySafeWithdrawal: (safeAmount: number) => void;

    private alertContainer: HTMLElement | null = null;
    private depletionYearEl: HTMLElement | null = null;
    private safeAmountEl: HTMLElement | null = null;
    private autoHealBtn: HTMLButtonElement | null = null;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs,
        onApplySafeWithdrawal: (safeAmount: number) => void
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.onApplySafeWithdrawal = onApplySafeWithdrawal;

        this.initDOM();
        this.bindEvents();
    }

    private initDOM(): void {
        this.alertContainer = this.dom.getElement<HTMLElement>('swp-longevity-guardian-alert');
        this.depletionYearEl = this.dom.getElement<HTMLElement>('swp-depletion-year-text');
        this.safeAmountEl = this.dom.getElement<HTMLElement>('swp-safe-amount-text');
        this.autoHealBtn = this.dom.getElement<HTMLButtonElement>('auto-heal-swp-btn');
    }

    private bindEvents(): void {
        if (this.autoHealBtn) {
            this.autoHealBtn.addEventListener('click', () => {
                const safeVal = Number(this.autoHealBtn?.dataset.safeAmount || 0);
                if (safeVal > 0) {
                    this.onApplySafeWithdrawal(safeVal);
                    A11yAnnouncer.announce(`SWP withdrawal auto-healed to safe amount of ${this.formatter.format(safeVal)} per month.`);
                }
            });
        }
    }

    public update(results: YearResult[]): void {
        if (!this.alertContainer) return;

        const inputs = this.getInputs();
        if (!inputs.enable_swp || inputs.swp_withdrawal <= 0 || !results || results.length === 0) {
            this.alertContainer.classList.add('hidden');
            return;
        }

        const swpStartYear = (inputs.years || 0) + 1;
        const totalYears = results.length;

        // Check if corpus hits zero prematurely
        let depletionYear = -1;
        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            if (row.year >= swpStartYear && row.combined_total <= 0) {
                depletionYear = row.year;
                break;
            }
        }

        if (depletionYear > 0 && depletionYear <= totalYears) {
            // Find starting corpus for the SWP phase
            const sipEndRow = results.find(r => r.year === inputs.years);
            const swpStartingCorpus = sipEndRow ? sipEndRow.combined_total : (inputs.lumpsum || 0);

            const safeWithdrawal = MathEngine.calculateSafeSwpWithdrawal(inputs, swpStartingCorpus);

            if (this.depletionYearEl) {
                this.depletionYearEl.textContent = `Year ${depletionYear}`;
            }

            if (this.safeAmountEl) {
                this.safeAmountEl.textContent = `${this.formatter.format(safeWithdrawal)}/mo`;
            }

            if (this.autoHealBtn) {
                this.autoHealBtn.dataset.safeAmount = String(safeWithdrawal);
            }

            this.alertContainer.classList.remove('hidden');
        } else {
            this.alertContainer.classList.add('hidden');
        }
    }
}
