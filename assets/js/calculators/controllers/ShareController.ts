import { DOMAdapter } from '../../adapters/DOMAdapter';
import { InvestmentInputs } from '../../types';

export class ShareController {
    private dom: DOMAdapter;
    private getInputs: () => InvestmentInputs;

    constructor(dom: DOMAdapter, getInputs: () => InvestmentInputs) {
        this.dom = dom;
        this.getInputs = getInputs;
    }

    init(): void {
        const shareBtn = this.dom.getElement('shareCalcBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                const inputs = this.getInputs();
                const params = new URLSearchParams();
                params.set('sip', String(inputs.sip));
                params.set('years', String(inputs.years));
                params.set('rate', String(inputs.rate));
                params.set('stepup', String(inputs.stepup));
                params.set('lumpsum', String(inputs.lumpsum));
                const curVal = this.dom.getValue('currency') || 'INR';
                params.set('cur', curVal);
                if (inputs.enable_swp) {
                    params.set('swp_on', '1');
                    params.set('swp', String(inputs.swp_withdrawal));
                    params.set('swp_years', String(inputs.swp_years));
                    params.set('swp_stepup', String(inputs.swp_stepup));
                    params.set('swp_rate', String(inputs.swp_rate));
                }
                const shareUrl = window.location.origin + window.location.pathname + '?' + params.toString();

                navigator.clipboard.writeText(shareUrl).then(() => {
                    const btnText = this.dom.getElement('shareBtnText');
                    if (btnText) btnText.textContent = 'Copied!';
                    shareBtn.classList.remove('text-emerald-600', 'border-indigo-200');
                    shareBtn.classList.add('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
                    setTimeout(() => {
                        if (btnText) btnText.textContent = 'Share';
                        shareBtn.classList.add('text-emerald-600', 'border-indigo-200');
                        shareBtn.classList.remove('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
                    }, 2000);
                }).catch(() => {
                    prompt('Copy this link:', shareUrl);
                });
            });
        }
    }
}
