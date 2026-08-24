import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';
import type { InvestmentInputs, YearResult } from '../../types';

/**
 * TaxWaterfallController
 * Manages the Budget 2024 Section 112A Interactive Tax Waterfall Modal
 * and live post-tax metrics updates.
 */
export class TaxWaterfallController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;

    private modal: HTMLDialogElement | null = null;
    private openBtn: HTMLButtonElement | null = null;
    private closeBtn: HTMLButtonElement | null = null;
    private closeFooterBtn: HTMLButtonElement | null = null;

    private grossGainsEl: HTMLElement | null = null;
    private taxableGainsEl: HTMLElement | null = null;
    private taxAmountEl: HTMLElement | null = null;
    private netCorpusEl: HTMLElement | null = null;
    private harvestSavingsEl: HTMLElement | null = null;
    private harvestEffectiveEl: HTMLElement | null = null;
    private retentionPctEl: HTMLElement | null = null;
    private effectiveRateEl: HTMLElement | null = null;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;

        this.initDOM();
        this.bindEvents();
    }

    private initDOM(): void {
        this.modal = this.dom.getElement<HTMLDialogElement>('tax-waterfall-modal');
        this.openBtn = this.dom.getElement<HTMLButtonElement>('open-tax-waterfall-btn');
        this.closeBtn = this.dom.getElement<HTMLButtonElement>('close-tax-waterfall-btn');
        this.closeFooterBtn = this.dom.getElement<HTMLButtonElement>('close-tax-waterfall-footer-btn');

        this.grossGainsEl = this.dom.getElement<HTMLElement>('tax-modal-gross-gains');
        this.taxableGainsEl = this.dom.getElement<HTMLElement>('tax-modal-taxable-gains');
        this.taxAmountEl = this.dom.getElement<HTMLElement>('tax-modal-tax-amount');
        this.netCorpusEl = this.dom.getElement<HTMLElement>('tax-modal-net-corpus');
        this.harvestSavingsEl = this.dom.getElement<HTMLElement>('tax-modal-harvest-savings');
        this.harvestEffectiveEl = this.dom.getElement<HTMLElement>('tax-modal-harvest-effective');
        this.retentionPctEl = this.dom.getElement<HTMLElement>('tax-modal-retention-pct');
        this.effectiveRateEl = this.dom.getElement<HTMLElement>('tax-modal-effective-rate');
    }

    private bindEvents(): void {
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => {
                if (this.modal && typeof this.modal.showModal === 'function') {
                    this.modal.showModal();
                    ModalScrollLockHelper.lock(this.openBtn);
                }
            });
        }

        const closeModal = () => {
            if (this.modal && typeof this.modal.close === 'function') {
                this.modal.close();
            }
        };

        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', closeModal);
        }
        if (this.closeFooterBtn) {
            this.closeFooterBtn.addEventListener('click', closeModal);
        }
        if (this.modal) {
            ModalScrollLockHelper.bindDialogBackdropClick(this.modal);
            this.modal.addEventListener('close', () => {
                ModalScrollLockHelper.unlock();
            });
        }
    }

    public update(results: YearResult[]): void {
        if (!results || results.length === 0) return;

        const inputs = this.getInputs();
        const lastRow = results[results.length - 1];
        const invested = lastRow.cumulative_invested;
        const total = lastRow.combined_total;
        const totalWithdrawn = lastRow.cumulative_withdrawals ?? 0;
        const grossGains = Math.max(0, (total + totalWithdrawn) - invested);
        const ltcgExemption = inputs.ltcg_exemption ?? 125000;
        const ltcgTaxRate = inputs.ltcg_tax_rate ?? 0.125;
        const taxableGains = Math.max(0, grossGains - ltcgExemption);
        const taxAmount = lastRow.ltcg_tax ?? Math.round(taxableGains * ltcgTaxRate);
        const netCorpus = lastRow.post_tax_total ?? Math.max(0, total - taxAmount);

        if (this.grossGainsEl) {
            this.grossGainsEl.textContent = this.formatter.format(grossGains);
        }
        if (this.taxableGainsEl) {
            this.taxableGainsEl.textContent = this.formatter.format(taxableGains);
        }
        if (this.taxAmountEl) {
            this.taxAmountEl.textContent = `- ${this.formatter.format(taxAmount)}`;
        }
        if (this.netCorpusEl) {
            this.netCorpusEl.textContent = this.formatter.format(netCorpus);
        }

        const retentionPct = total > 0 ? ((netCorpus / total) * 100).toFixed(1) : '100.0';
        const effectiveRate = total > 0 ? ((taxAmount / total) * 100).toFixed(1) : '0.0';

        if (this.retentionPctEl) {
            this.retentionPctEl.textContent = `${retentionPct}%`;
        }
        if (this.effectiveRateEl) {
            this.effectiveRateEl.textContent = `${effectiveRate}%`;
        }

        const harvest = MathEngine.calculateTaxHarvestingSavings(inputs, results);
        if (this.harvestSavingsEl) {
            this.harvestSavingsEl.textContent = `Save ${this.formatter.format(harvest.cumulativeSavings)}`;
        }
        if (this.harvestEffectiveEl) {
            this.harvestEffectiveEl.textContent = this.formatter.format(harvest.harvestedTax);
        }
    }
}
