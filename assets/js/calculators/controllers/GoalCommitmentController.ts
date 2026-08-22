import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs, YearResult } from '../../types';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';

export class GoalCommitmentController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputsCallback: () => InvestmentInputs;
    private getResultsCallback: () => YearResult[];

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputsCallback: () => InvestmentInputs,
        getResultsCallback: () => YearResult[]
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputsCallback = getInputsCallback;
        this.getResultsCallback = getResultsCallback;
    }

    init(): void {
        const openBtn = this.dom.getElement('open-goal-pledge-btn');
        const closeBtn = this.dom.getElement('close-pledge-modal-btn');
        const copyBtn = this.dom.getElement('copy-pledge-btn');
        const printBtn = this.dom.getElement('print-pledge-btn');
        const modal = this.dom.getElement('goal-commitment-modal');

        if (openBtn) {
            openBtn.addEventListener('click', () => this.openModal(openBtn));
        }

        if (closeBtn && modal) {
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', () => this.copyPledge());
        }

        if (printBtn) {
            printBtn.addEventListener('click', () => {
                window.print();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                this.closeModal();
            }
        });
    }

    openModal(triggerElement?: HTMLElement): void {
        const modal = this.dom.getElement('goal-commitment-modal');
        if (!modal) return;

        this.populateMetrics();
        modal.classList.remove('hidden');
        ModalScrollLockHelper.lock(triggerElement);
    }

    closeModal(): void {
        const modal = this.dom.getElement('goal-commitment-modal');
        if (modal) {
            modal.classList.add('hidden');
            ModalScrollLockHelper.unlock();
        }
    }

    private populateMetrics(): void {
        const inputs = this.getInputsCallback();
        const results = this.getResultsCallback();
        const lastRow = results && results.length > 0 ? results[results.length - 1] : null;

        const sipDisplay = this.dom.getElement('pledge-sip-display');
        const horizonDisplay = this.dom.getElement('pledge-horizon-display');
        const targetDisplay = this.dom.getElement('pledge-target-display');

        const sipVal = inputs.sip ?? 25000;
        const yearsVal = inputs.years ?? 15;
        const finalCorpus = lastRow ? lastRow.combined_total : 12600000;

        if (sipDisplay) {
            sipDisplay.textContent = `${this.formatter.formatDynamic(sipVal)} / month`;
        }
        if (horizonDisplay) {
            horizonDisplay.textContent = `${yearsVal} Years`;
        }
        if (targetDisplay) {
            targetDisplay.textContent = this.formatter.formatDynamic(finalCorpus);
        }
    }

    private copyPledge(): void {
        const nameInput = this.dom.getElement<HTMLInputElement>('pledge-investor-name');
        const name = nameInput?.value?.trim() || 'Disciplined Investor';
        const inputs = this.getInputsCallback();
        const results = this.getResultsCallback();
        const lastRow = results && results.length > 0 ? results[results.length - 1] : null;

        const sipVal = inputs.sip ?? 25000;
        const yearsVal = inputs.years ?? 15;
        const finalCorpus = lastRow ? lastRow.combined_total : 12600000;

        const text = `📜 INVESTOR GOAL COMMITMENT CERTIFICATE\n\nI, ${name}, hereby pledge to systematically invest ${this.formatter.formatDynamic(sipVal)}/month for ${yearsVal} years to achieve my target corpus of ${this.formatter.formatDynamic(finalCorpus)}.\n\n"Market volatility is the fee for exceptional long-term wealth compounding."`;

        const onCopySuccess = () => {
            const copyBtn = this.dom.getElement('copy-pledge-btn');
            if (copyBtn) {
                const original = copyBtn.innerHTML;
                copyBtn.innerHTML = '<span>✓ Copied to Clipboard!</span>';
                setTimeout(() => {
                    copyBtn.innerHTML = original;
                }, 2000);
            }
        };

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(onCopySuccess).catch(() => {
                this.fallbackCopy(text);
                onCopySuccess();
            });
        } else {
            this.fallbackCopy(text);
            onCopySuccess();
        }
    }

    private fallbackCopy(text: string): void {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
        } catch {
            // Safe fallback ignore
        }
        document.body.removeChild(textarea);
    }
}
