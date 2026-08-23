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
        const savePngBtn = this.dom.getElement('save-pledge-png-btn');
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

        if (savePngBtn) {
            savePngBtn.addEventListener('click', () => this.exportCertificateAsPng());
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

    private exportCertificateAsPng(): void {
        const nameInput = this.dom.getElement<HTMLInputElement>('pledge-investor-name');
        const name = nameInput?.value?.trim() || 'Disciplined Investor';
        const inputs = this.getInputsCallback();
        const results = this.getResultsCallback();
        const lastRow = results && results.length > 0 ? results[results.length - 1] : null;

        const sipVal = inputs.sip ?? 25000;
        const yearsVal = inputs.years ?? 15;
        const finalCorpus = lastRow ? lastRow.combined_total : 0;

        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 630;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        // Background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, 1200, 630);

        const bgGrad = ctx.createLinearGradient(0, 0, 1200, 630);
        bgGrad.addColorStop(0, '#f8fafc');
        bgGrad.addColorStop(0.5, '#ffffff');
        bgGrad.addColorStop(1, '#ecfdf5');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(16, 16, 1168, 598);

        // Borders
        ctx.strokeStyle = '#047857';
        ctx.lineWidth = 6;
        ctx.strokeRect(20, 20, 1160, 590);

        ctx.strokeStyle = '#a7f3d0';
        ctx.lineWidth = 2;
        ctx.strokeRect(28, 28, 1144, 574);

        // Header Title
        ctx.textAlign = 'center';
        ctx.fillStyle = '#065f46';
        ctx.font = 'bold 16px sans-serif';
        ctx.fillText('🛡️ SOVEREIGN WEALTH & DISCIPLINE CONTRACT', 600, 75);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 36px sans-serif';
        ctx.fillText('Investor Goal Commitment Certificate', 600, 125);

        ctx.fillStyle = '#64748b';
        ctx.font = '500 18px sans-serif';
        ctx.fillText('This document certifies that', 600, 170);

        // Investor Name
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 32px sans-serif';
        ctx.fillText(name, 600, 220);

        ctx.fillStyle = '#64748b';
        ctx.font = '500 18px sans-serif';
        ctx.fillText('has pledged to systematically invest', 600, 265);

        // SIP Display
        ctx.fillStyle = '#047857';
        ctx.font = '800 44px sans-serif';
        ctx.fillText(`${this.formatter.formatDynamic(sipVal)} / Month`, 600, 325);

        // 2-Card Metrics Box
        // Horizon Box
        ctx.fillStyle = '#ffffff';
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 2;
        ctx.fillRect(280, 365, 300, 85);
        ctx.strokeRect(280, 365, 300, 85);

        ctx.fillStyle = '#64748b';
        ctx.font = 'bold 13px sans-serif';
        ctx.fillText('TARGET HORIZON', 430, 395);
        ctx.fillStyle = '#0f172a';
        ctx.font = '800 24px sans-serif';
        ctx.fillText(`${yearsVal} Years`, 430, 430);

        // Corpus Box
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(620, 365, 300, 85);
        ctx.strokeRect(620, 365, 300, 85);

        ctx.fillStyle = '#64748b';
        ctx.font = 'bold 13px sans-serif';
        ctx.fillText('TARGET MATURITY CORPUS', 770, 395);
        ctx.fillStyle = '#047857';
        ctx.font = '800 24px sans-serif';
        ctx.fillText(this.formatter.formatDynamic(finalCorpus), 770, 430);

        // Quote
        ctx.fillStyle = '#475569';
        ctx.font = 'italic 16px sans-serif';
        ctx.fillText('"Market volatility is the fee for exceptional long-term wealth compounding."', 600, 500);

        // Footer Stamp
        ctx.fillStyle = '#94a3b8';
        ctx.font = '500 13px sans-serif';
        const dateStr = new Date().toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' });
        ctx.fillText(`Certified on ${dateStr} • SIP & SWP Wealth Planner Engine • Non-Binding Sovereign Commitment`, 600, 560);

        // Trigger Download
        const link = document.createElement('a');
        link.download = `Goal_Commitment_${name.replace(/\s+/g, '_')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
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
        const finalCorpus = lastRow ? lastRow.combined_total : 0;

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
        const finalCorpus = lastRow ? lastRow.combined_total : 0;

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
