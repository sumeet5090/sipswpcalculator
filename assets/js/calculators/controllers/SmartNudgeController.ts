import { DOMAdapter } from '../../adapters/DOMAdapter';

export class SmartNudgeController {
    private dom: DOMAdapter;
    private setSmartNudgeRate: (rate: number) => void;
    private isInitialized = false;
    private documentClickListener?: (e: Event) => void;
    private documentKeydownListener?: (e: KeyboardEvent) => void;

    constructor(dom: DOMAdapter, setSmartNudgeRate: (rate: number) => void) {
        this.dom = dom;
        this.setSmartNudgeRate = setSmartNudgeRate;
    }

    init(): void {
        if (this.isInitialized) return;

        const nudgeBtn = this.dom.getElement('rate-nudge-btn');
        const nudgePopover = this.dom.getElement('rate-nudge-popover');
        const nudgeClose = this.dom.getElement('rate-nudge-close');

        if (nudgeBtn && nudgePopover) {
            this.isInitialized = true;

            nudgeBtn.addEventListener('click', e => {
                e.stopPropagation();
                const isHidden = nudgePopover.classList.contains('hidden');
                nudgePopover.classList.toggle('hidden', !isHidden);
                nudgeBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            });
            if (nudgeClose) {
                nudgeClose.addEventListener('click', () => {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                });
            }
            const applyRateAndClose = (rate: number) => {
                this.setSmartNudgeRate(rate);
                nudgePopover.classList.add('hidden');
                nudgeBtn.setAttribute('aria-expanded', 'false');
                nudgeBtn.focus();
            };

            const indiaBtn = this.dom.getElement('use-india-rate');
            const usBtn = this.dom.getElement('use-us-rate');
            if (indiaBtn) {
                const indiaRate = parseFloat(indiaBtn.dataset.rate || '12');
                indiaBtn.addEventListener('click', () => applyRateAndClose(indiaRate));
            }
            if (usBtn) {
                const usRate = parseFloat(usBtn.dataset.rate || '15');
                usBtn.addEventListener('click', () => applyRateAndClose(usRate));
            }

            this.documentClickListener = (e: Event) => {
                if (!nudgePopover.contains(e.target as Node) && e.target !== nudgeBtn) {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                }
            };
            this.documentKeydownListener = (e: KeyboardEvent) => {
                if (e.key === 'Escape' && !nudgePopover.classList.contains('hidden')) {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                    nudgeBtn.focus();
                }
            };

            document.addEventListener('click', this.documentClickListener);
            document.addEventListener('keydown', this.documentKeydownListener);
        }
    }

    destroy(): void {
        if (this.documentClickListener) {
            document.removeEventListener('click', this.documentClickListener);
            this.documentClickListener = undefined;
        }
        if (this.documentKeydownListener) {
            document.removeEventListener('keydown', this.documentKeydownListener);
            this.documentKeydownListener = undefined;
        }
        this.isInitialized = false;
    }
}
