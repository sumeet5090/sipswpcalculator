import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs } from '../../types';

export class AssetRebalanceController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private targetEquityPct: number = 70;
    private targetDebtPct: number = 30;
    private currentInputs: InvestmentInputs | null = null;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
    }

    init(): void {
        const card = this.dom.getElement('asset-rebalancing-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.rebalance-choice-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const eq = parseFloat(btn.dataset.equity || '70');
                const debt = parseFloat(btn.dataset.debt || '30');
                this.setTargetSplit(eq, debt);
            });
        });
    }

    setTargetSplit(equityPct: number, debtPct: number): void {
        this.targetEquityPct = equityPct;
        this.targetDebtPct = debtPct;

        const card = this.dom.getElement('asset-rebalancing-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.rebalance-choice-btn');
        buttons.forEach(b => {
            if (parseFloat(b.dataset.equity || '0') === equityPct) {
                b.classList.add('border-indigo-500', 'text-white');
                b.classList.remove('border-slate-700', 'text-slate-300');
            } else {
                b.classList.remove('border-indigo-500', 'text-white');
                b.classList.add('border-slate-700', 'text-slate-300');
            }
        });

        this.updateDisplay();
    }

    updateInputs(inputs: InvestmentInputs): void {
        this.currentInputs = inputs;
        this.updateDisplay();
    }

    private updateDisplay(): void {
        if (!this.currentInputs) return;

        const eqRate = this.currentInputs.rate || 12;
        const debtRate = 7.0; // Standard high-quality AAA Indian debt fund yield
        const blendedRate = ((this.targetEquityPct / 100) * eqRate) + ((this.targetDebtPct / 100) * debtRate);
        const volReduction = Math.round((this.targetDebtPct / 100) * 80);

        const cagrEl = this.dom.getElement('rebalance-preview-cagr');
        const splitEl = this.dom.getElement('rebalance-preview-split');
        const volEl = this.dom.getElement('rebalance-preview-volatility');
        const actionEl = this.dom.getElement('rebalance-action-text');

        if (cagrEl) cagrEl.textContent = `${blendedRate.toFixed(1)}% p.a.`;
        if (splitEl) splitEl.textContent = `${this.targetEquityPct}% Equity / ${this.targetDebtPct}% Debt`;
        if (volEl) volEl.textContent = `-${volReduction}% vs Pure Eq`;

        const totalSip = this.currentInputs.sip || 25000;
        const equitySip = Math.round((this.targetEquityPct / 100) * totalSip);
        const debtSip = totalSip - equitySip;

        if (actionEl) {
            actionEl.innerHTML = `Allocate monthly SIP: <strong class="text-white">${this.formatter.format(equitySip)} into Equity</strong> and <strong class="text-white">${this.formatter.format(debtSip)} into Debt</strong> funds to maintain exact ${this.targetEquityPct}/${this.targetDebtPct} asset balance without triggering any capital gains tax.`;
        }
    }
}
