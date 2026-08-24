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

            const syncActiveOption = () => {
                const currentRate = parseFloat(this.dom.getValue('rate') || '12');
                const rateOptions = nudgePopover.querySelectorAll<HTMLButtonElement>('.smart-rate-option');
                rateOptions.forEach(opt => {
                    const optRate = parseFloat(opt.dataset.rate || '12');
                    if (Math.abs(optRate - currentRate) < 0.1) {
                        opt.classList.add('bg-emerald-50', 'border-emerald-300', 'ring-1', 'ring-emerald-400/40');
                        opt.classList.remove('bg-slate-50', 'border-slate-200/80');
                    } else {
                        opt.classList.remove('bg-emerald-50', 'border-emerald-300', 'ring-1', 'ring-emerald-400/40');
                        opt.classList.add('bg-slate-50', 'border-slate-200/80');
                    }
                });
            };

            nudgeBtn.addEventListener('click', e => {
                e.stopPropagation();
                const isHidden = nudgePopover.classList.contains('hidden');
                if (isHidden) {
                    syncActiveOption();
                    nudgePopover.classList.remove('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'true');
                } else {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                }
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

            const rateOptions = nudgePopover.querySelectorAll<HTMLButtonElement>('.smart-rate-option');
            rateOptions.forEach(opt => {
                const rate = parseFloat(opt.dataset.rate || '12');
                opt.addEventListener('click', () => applyRateAndClose(rate));
            });

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
