import { DOMAdapter } from '../../adapters/DOMAdapter';
import { ModalScrollLockHelper } from '../helpers/ModalScrollLockHelper';
import { QrCodeGenerator } from '../../utils/QrCodeGenerator';
import { InvestmentInputs } from '../../types';

export class QrShareModalController {
    private dom: DOMAdapter;
    private getInputs?: () => InvestmentInputs;

    constructor(dom: DOMAdapter = new DOMAdapter(), getInputs?: () => InvestmentInputs) {
        this.dom = dom;
        this.getInputs = getInputs;
    }

    init(): void {
        const openBtns = this.dom.getElements<HTMLElement>('.open-qr-modal-btn, #open-qr-modal-btn');
        const closeBtn = this.dom.getElement('close-qr-modal-btn');
        const modal = this.dom.getElement('qr-share-modal');
        const copyBtn = this.dom.getElement('copy-qr-url-btn');

        openBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.openModal(btn);
            });
        });

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
            copyBtn.addEventListener('click', () => this.copyUrl());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                this.closeModal();
            }
        });
    }

    /**
     * Compute full permalink URL with current live calculator inputs.
     */
    public buildShareUrl(): string {
        if (!this.getInputs) {
            return window.location.href;
        }

        try {
            const inputs = this.getInputs();
            const params = new URLSearchParams();
            const appEl = this.dom.getElement('calculator-app');
            const isSwpMode = (appEl?.dataset?.mode === 'swp');

            if (inputs.sip !== undefined) params.set('sip', String(inputs.sip));
            if (inputs.years !== undefined) params.set('years', String(inputs.years));
            if (inputs.rate !== undefined) params.set('rate', String(inputs.rate));
            if (inputs.stepup !== undefined) params.set('stepup', String(inputs.stepup));

            if (isSwpMode) {
                if (inputs.lumpsum !== undefined) params.set('corpus', String(inputs.lumpsum));
            } else {
                if (inputs.lumpsum !== undefined) params.set('lumpsum', String(inputs.lumpsum));
            }

            if (inputs.inflation && inputs.inflation > 0) {
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
                if (inputs.swp_withdrawal) params.set('swp', String(inputs.swp_withdrawal));
                if (inputs.swp_years) params.set('swp_years', String(inputs.swp_years));
                if (inputs.swp_stepup) params.set('swp_stepup', String(inputs.swp_stepup));
                if (inputs.swp_rate) params.set('swp_rate', String(inputs.swp_rate));
            }

            const queryString = params.toString();
            return queryString
                ? `${window.location.origin}${window.location.pathname}?${queryString}`
                : `${window.location.origin}${window.location.pathname}`;
        } catch {
            return window.location.href;
        }
    }

    openModal(triggerElement?: HTMLElement): void {
        const modal = this.dom.getElement('qr-share-modal');
        const container = this.dom.getElement('qr-code-canvas-container');
        const urlInput = this.dom.getElement<HTMLInputElement>('qr-share-url-input');

        if (!modal || !container) return;

        const shareUrl = this.buildShareUrl();
        if (urlInput) {
            urlInput.value = shareUrl;
        }

        // Generate authentic, scannable QR code on canvas
        container.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.className = 'w-full h-full rounded-lg shadow-2xs';
        canvas.style.imageRendering = 'pixelated';
        container.appendChild(canvas);

        QrCodeGenerator.renderToCanvas(canvas, shareUrl, '#0f172a', '#ffffff');

        modal.classList.remove('hidden');
        ModalScrollLockHelper.lock(triggerElement);
    }

    closeModal(): void {
        const modal = this.dom.getElement('qr-share-modal');
        if (modal) {
            modal.classList.add('hidden');
            ModalScrollLockHelper.unlock();
        }
    }

    private copyTimeoutId: ReturnType<typeof setTimeout> | null = null;

    private copyUrl(): void {
        const urlInput = this.dom.getElement<HTMLInputElement>('qr-share-url-input');
        const copyBtn = this.dom.getElement('copy-qr-url-btn');
        const text = urlInput?.value || this.buildShareUrl();

        const onCopySuccess = () => {
            if (copyBtn) {
                if (this.copyTimeoutId) {
                    clearTimeout(this.copyTimeoutId);
                }
                const original = '<span>📋 Copy</span>';
                copyBtn.innerHTML = '<span>✓ Copied!</span>';
                this.copyTimeoutId = setTimeout(() => {
                    copyBtn.innerHTML = original;
                    this.copyTimeoutId = null;
                }, 2000);
            }
        };

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(onCopySuccess).catch(() => {
                this.dom.copyToClipboard(text, onCopySuccess);
            });
        } else {
            this.dom.copyToClipboard(text, onCopySuccess);
        }
    }
}
