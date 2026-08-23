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
                const appEl = this.dom.getElement('calculator-app');
                const isSwpMode = (appEl?.dataset?.mode === 'swp');

                params.set('sip', String(inputs.sip));
                params.set('years', String(inputs.years));
                params.set('rate', String(inputs.rate));
                params.set('stepup', String(inputs.stepup));

                if (isSwpMode) {
                    params.set('corpus', String(inputs.lumpsum));
                } else {
                    params.set('lumpsum', String(inputs.lumpsum));
                }

                if (inputs.inflation > 0) {
                    params.set('inflation', String(inputs.inflation));
                }

                const curVal = this.dom.getValue('currency') || 'INR';
                if (curVal !== 'INR') {
                    params.set('cur', curVal);
                }

                const targetCorpusVal = this.dom.getValue('target_corpus');
                const goalTargetBtn = this.dom.getElement('goal-target');
                if (goalTargetBtn && goalTargetBtn.getAttribute('aria-checked') === 'true') {
                    params.set('goal_mode', 'target');
                    if (targetCorpusVal) {
                        params.set('target_corpus', String(targetCorpusVal));
                    }
                }

                const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
                if (postTaxToggle?.checked) {
                    params.set('post_tax', '1');
                }

                const wealthMapToggle = this.dom.getElement<HTMLInputElement>('show_wealth_map');
                if (wealthMapToggle?.checked) {
                    params.set('wealth_map', '1');
                }

                if (inputs.enable_swp) {
                    params.set('swp_on', '1');
                    params.set('swp', String(inputs.swp_withdrawal));
                    params.set('swp_years', String(inputs.swp_years));
                    params.set('swp_stepup', String(inputs.swp_stepup));
                    params.set('swp_rate', String(inputs.swp_rate));
                }
                const shareUrl = window.location.origin + window.location.pathname + '?' + params.toString();

                const showCopiedFeedback = () => {
                    const btnText = this.dom.getElement('shareBtnText');
                    if (btnText) btnText.innerHTML = '<span class="animate-bounce inline-block">✅</span> <span>Link Copied!</span>';
                    shareBtn.classList.remove('text-emerald-600', 'border-indigo-200');
                    shareBtn.classList.add('text-emerald-800', 'border-emerald-400', 'bg-emerald-100/90', 'shadow-sm');

                    if (typeof navigator !== 'undefined' && 'vibrate' in navigator) {
                        navigator.vibrate([15, 30, 15]);
                    }

                    setTimeout(() => {
                        if (btnText) btnText.textContent = 'Share';
                        shareBtn.classList.add('text-emerald-600', 'border-indigo-200');
                        shareBtn.classList.remove('text-emerald-800', 'border-emerald-400', 'bg-emerald-100/90', 'shadow-sm');
                    }, 2200);
                };

                this.dom.copyToClipboard(shareUrl, showCopiedFeedback);
            });
        }
    }
}
