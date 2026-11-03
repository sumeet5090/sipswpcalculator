import { DOMAdapter } from '../../adapters/DOMAdapter';
import type { InvestmentInputs, YearResult } from '../../types';
import { CanvasExportHelper } from '../helpers/CanvasExportHelper';
import { WealthPassCanvasRenderer } from '../helpers/WealthPassCanvasRenderer';

export class ShareController {
    private dom: DOMAdapter;
    private getInputs: () => InvestmentInputs;
    private getResults?: () => YearResult[];

    constructor(
        dom: DOMAdapter,
        getInputs: () => InvestmentInputs,
        getResults?: () => YearResult[]
    ) {
        this.dom = dom;
        this.getInputs = getInputs;
        this.getResults = getResults;
    }

    public buildShareUrl(): string {
        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = appEl?.dataset?.mode;
        const specializedModes = ['compound_interest', 'cagr', 'emi', 'inflation', 'ppf', 'fd'];

        if (mode && specializedModes.includes(mode)) {
            const form = this.dom.getElement<HTMLFormElement>('calculator-form');
            if (form) {
                const formData = new FormData(form);
                const params = new URLSearchParams();
                formData.forEach((val, key) => {
                    const strVal = String(val).trim();
                    if (strVal !== '' && key !== 'website_url' && !['sip', 'stepup', 'lumpsum', 'enable_swp'].includes(key)) {
                        params.set(key, strVal);
                    }
                });
                const queryString = params.toString();
                return queryString ? `${window.location.origin}${window.location.pathname}?${queryString}` : window.location.href;
            }
        }

        const inputs = this.getInputs();
        const params = new URLSearchParams();
        const isSwpMode = (mode === 'swp');
        const targetCorpusVal = this.dom.getValue('target_corpus');
        const goalTargetBtn = this.dom.getElement('goal-target');
        const isTargetMode = (mode === 'target_corpus') || (goalTargetBtn && goalTargetBtn.getAttribute('aria-checked') === 'true');

        if (isTargetMode) {
            params.set('goal_mode', 'target');
            if (targetCorpusVal) {
                params.set('target_corpus', String(targetCorpusVal));
            }
        } else {
            params.set('sip', String(inputs.sip));
        }

        params.set('years', String(inputs.years));
        params.set('rate', String(inputs.rate));
        params.set('stepup', String(inputs.stepup));

        if (isSwpMode) {
            params.set('corpus', String(inputs.lumpsum));
        } else if (!isTargetMode) {
            params.set('lumpsum', String(inputs.lumpsum));
        }

        if (inputs.inflation > 0) {
            params.set('inflation', String(inputs.inflation));
        }

        const curVal = this.dom.getValue('currency') || 'INR';
        if (curVal !== 'INR') {
            params.set('cur', curVal);
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
        return window.location.origin + window.location.pathname + '?' + params.toString();
    }

    init(): void {
        const shareBtn = this.dom.getElement('shareCalcBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                const shareUrl = this.buildShareUrl();

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

        const waBtns = document.querySelectorAll<HTMLButtonElement>('.whatsapp-share-btn');
        waBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                this.shareToWhatsApp();
            });
        });

        const socialCardBtn = this.dom.getElement('downloadSocialCardBtn');
        if (socialCardBtn) {
            socialCardBtn.addEventListener('click', () => {
                this.downloadSocialCard();
            });
        }

        const wealthPassBtn = this.dom.getElement('shareWealthPassBtn');
        if (wealthPassBtn) {
            wealthPassBtn.addEventListener('click', () => {
                const discreetToggle = this.dom.getElement<HTMLInputElement>('discreet-share-toggle');
                const isDiscreet = discreetToggle ? discreetToggle.checked : false;
                this.shareWealthPass(isDiscreet);
            });
        }

        const mobileNativeShareBtn = this.dom.getElement('mobile-native-share-btn');
        if (mobileNativeShareBtn) {
            mobileNativeShareBtn.addEventListener('click', () => {
                void this.tryNativeShare();
            });
        }
    }

    /**
     * Dispatch native OS share sheet with financial proposal summary and calculation URL.
     * Discriminates AbortError silently as standard user cancellation.
     */
    async tryNativeShare(): Promise<boolean> {
        const shareUrl = this.buildShareUrl();
        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = appEl?.dataset?.mode;
        const specializedModes = ['compound_interest', 'cagr', 'emi', 'inflation', 'ppf', 'fd'];

        let planSummary = '';
        if (mode && specializedModes.includes(mode)) {
            const h1 = document.querySelector('h1')?.textContent?.trim() || 'Financial Calculation';
            const corpus = this.dom.getElement('summary-corpus')?.textContent?.trim() || '';
            planSummary = `${h1}${corpus ? ` • Total: ${corpus}` : ''}`;
        } else if (mode === 'target_corpus') {
            const targetCorpusVal = parseFloat(this.dom.getValue('target_corpus') || '0');
            const years = this.dom.getValue('years') || '15';
            const reqSipDisplay = this.dom.getElement('target_calculated_sip_display')?.textContent?.trim() || '';
            planSummary = `Target Corpus Goal Plan (${years} Years) • Target: ₹${targetCorpusVal.toLocaleString('en-IN')}${reqSipDisplay ? ` • Required SIP: ${reqSipDisplay}` : ''}`;
        } else {
            const inputs = this.getInputs();
            const results = this.getResults ? this.getResults() : [];
            planSummary = `Wealth Plan (${inputs.years} Years) • Monthly SIP: ₹${inputs.sip.toLocaleString('en-IN')}`;
            if (results && results.length > 0) {
                const last = results[results.length - 1];
                planSummary += ` • Projected Corpus: ₹${Math.round(last.combined_total).toLocaleString('en-IN')}`;
            }
        }

        if (typeof navigator !== 'undefined' && 'share' in navigator) {
            try {
                await navigator.share({
                    title: 'Financial Plan',
                    text: planSummary,
                    url: shareUrl
                });
                return true;
            } catch (err: unknown) {
                if (err instanceof Error && err.name === 'AbortError') {
                    return false;
                }
            }
        }

        // Fallback: Copy link with feedback
        this.dom.copyToClipboard(shareUrl, () => {
            const nativeBtn = this.dom.getElement('mobile-native-share-btn');
            if (nativeBtn) {
                const originalHtml = nativeBtn.innerHTML;
                nativeBtn.innerHTML = '<span>✅ Link Copied!</span>';
                setTimeout(() => {
                    nativeBtn.innerHTML = originalHtml;
                }, 2000);
            }
        });
        return false;
    }

    /**
     * Generate and download a branded 1080x1080 social card.
     */
    downloadSocialCard(): void {
        const inputs = this.getInputs();
        let totalInvested = 0;
        let totalGains = 0;
        let finalCorpus = 0;

        const results = this.getResults ? this.getResults() : [];
        if (results && results.length > 0) {
            const last = results[results.length - 1];
            totalInvested = last.cumulative_invested;
            finalCorpus = last.combined_total;
            totalGains = Math.max(0, finalCorpus - totalInvested);
        } else {
            // Fallback to DOM elements
            const parseAmount = (id: string): number => {
                const el = this.dom.getElement(id);
                if (!el || !el.textContent) return 0;
                const clean = el.textContent.replace(/[^0-9.]/g, '');
                return parseFloat(clean) || 0;
            };
            totalInvested = parseAmount('summary-invested');
            totalGains = parseAmount('summary-interest');
            finalCorpus = parseAmount('summary-corpus');
        }

        const appEl = this.dom.getElement('calculator-app');
        const mode = appEl?.dataset?.mode || 'sip';
        const titleMap: Record<string, string> = {
            'sip': 'SIP Wealth Accumulation Plan',
            'swp': 'Retirement SWP Cash Flow Plan',
            'stepup': 'Step-Up SIP Accelerated Plan',
            'combo': 'SIP & SWP Comprehensive Plan',
            'lumpsum': 'Lumpsum Investment Plan',
            'target_corpus': 'Target Corpus Wealth Plan',
            'compound_interest': 'Compound Interest Growth Plan',
            'cagr': 'CAGR Return Performance Plan',
            'emi': 'Loan EMI Repayment Plan',
            'inflation': 'Inflation Purchasing Power Plan',
            'ppf': 'PPF Long-Term Savings Plan',
            'fd': 'Fixed Deposit Returns Plan'
        };
        const planTitle = titleMap[mode] || 'Wealth Accumulation Plan';

        const dataUrl = CanvasExportHelper.exportSocialSummaryCard(
            inputs,
            totalInvested,
            totalGains,
            finalCorpus,
            planTitle
        );

        if (!dataUrl) return;

        // Visual feedback
        const btnText = this.dom.getElement('socialCardBtnText');
        if (btnText) btnText.innerHTML = '<span>✅ Saved!</span>';

        // Trigger file download
        const a = document.createElement('a');
        a.href = dataUrl;
        const yearsFallback = inputs.years || parseFloat(this.dom.getValue('years') || this.dom.getValue('emi_years') || this.dom.getValue('ppf_years') || this.dom.getValue('fd_years') || '10');
        a.download = `wealth-plan-${yearsFallback}yrs.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        if (btnText) {
            setTimeout(() => {
                btnText.textContent = 'Share Card';
            }, 2000);
        }
    }

    /**
     * Share or download the sleek Wealth Horizon Pass with Web Share API and Discreet Privacy Mode.
     */
    async shareWealthPass(isDiscreetMode: boolean = false): Promise<void> {
        const inputs = this.getInputs();
        const results = this.getResults ? this.getResults() : [];
        let totalInvested = 0;
        let totalGains = 0;
        let finalCorpus = 0;

        if (results && results.length > 0) {
            const last = results[results.length - 1];
            totalInvested = last.cumulative_invested;
            finalCorpus = last.combined_total;
            totalGains = Math.max(0, finalCorpus - totalInvested);
        }

        const blob = await WealthPassCanvasRenderer.generatePassBlob({
            inputs,
            finalCorpus,
            totalInvested,
            totalGains,
            isDiscreetMode,
            goalTitle: finalCorpus >= 10000000 ? 'Destination: Crorepati Sovereign' : 'Destination: Financial Freedom'
        });

        if (!blob) return;

        const file = new File([blob], `Wealth-Horizon-Pass-${inputs.years}Y.png`, { type: 'image/png' });

        if (typeof navigator !== 'undefined' && 'canShare' in navigator && navigator.canShare({ files: [file] })) {
            try {
                await navigator.share({
                    title: 'My Wealth Horizon Pass',
                    text: `Mapped my ${inputs.years}-year wealth blueprint on sipswpcalculator.com`,
                    files: [file]
                });
                return;
            } catch {
                // User cancelled or share failed; fallback to direct download below
            }
        }

        // Direct Download Fallback
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wealth-horizon-pass-${inputs.years}Y.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Dispatch structured investment plan proposal directly to WhatsApp.
     */
    shareToWhatsApp(results?: YearResult[]): void {
        const shareUrl = this.buildShareUrl();
        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = appEl?.dataset?.mode;
        const specializedModes = ['compound_interest', 'cagr', 'emi', 'inflation', 'ppf', 'fd'];

        let planSummary = '';
        if (mode && specializedModes.includes(mode)) {
            const h1 = document.querySelector('h1')?.textContent?.trim() || 'Financial Calculation';
            const corpus = this.dom.getElement('summary-corpus')?.textContent?.trim() || '';
            const invested = this.dom.getElement('summary-invested')?.textContent?.trim() || '';
            const interest = this.dom.getElement('summary-interest')?.textContent?.trim() || '';

            planSummary = `📊 *${h1}*`;
            if (invested) planSummary += `\n• Principal / Invested: ${invested}`;
            if (interest) planSummary += `\n• Interest / Growth: ${interest}`;
            if (corpus) planSummary += `\n• Total / Maturity: ${corpus}`;
        } else if (mode === 'target_corpus') {
            const targetCorpusVal = parseFloat(this.dom.getValue('target_corpus') || '0');
            const years = this.dom.getValue('years') || '15';
            const rate = this.dom.getValue('rate') || '12';
            const stepup = this.dom.getValue('stepup') || '10';
            const reqSipDisplay = this.dom.getElement('target_calculated_sip_display')?.textContent?.trim() || '';

            planSummary = `🎯 *Target Corpus Goal Plan (${years} Years)*`;
            if (targetCorpusVal > 0) planSummary += `\n• Target Goal: ₹${targetCorpusVal.toLocaleString('en-IN')}`;
            if (reqSipDisplay) planSummary += `\n• Required Monthly SIP: ${reqSipDisplay}`;
            planSummary += `\n• Expected Return: ${rate}%\n• Annual Step-Up: ${stepup}%`;
        } else {
            const inputs = this.getInputs();
            planSummary = `📊 *My Wealth Plan (${inputs.years} Years)*\n• Monthly SIP: ₹${inputs.sip.toLocaleString('en-IN')}\n• Annual Step-Up: ${inputs.stepup}%`;
            
            if (results && results.length > 0) {
                const last = results[results.length - 1];
                planSummary += `\n• Projected Corpus: ₹${Math.round(last.combined_total).toLocaleString('en-IN')}`;
            }

            if (inputs.enable_swp) {
                planSummary += `\n• Monthly Retirement SWP: ₹${inputs.swp_withdrawal.toLocaleString('en-IN')}`;
            }
        }
        
        const text = `${planSummary}\n\n👉 View, test, or customize this exact calculation here:\n${shareUrl}`;
        const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
        if (typeof window !== 'undefined') {
            window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
        }
    }
}
