import { DOMAdapter } from '../../adapters/DOMAdapter';

export class SmartNudgeController {
    private dom: DOMAdapter;
    private setSmartNudgeRate: (rate: number) => void;

    constructor(dom: DOMAdapter, setSmartNudgeRate: (rate: number) => void) {
        this.dom = dom;
        this.setSmartNudgeRate = setSmartNudgeRate;
    }

    init(): void {
        const nudgeBtn = this.dom.getElement('rate-nudge-btn');
        const nudgePopover = this.dom.getElement('rate-nudge-popover');
        const nudgeClose = this.dom.getElement('rate-nudge-close');

        if (nudgeBtn && nudgePopover) {
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
            const indiaBtn = this.dom.getElement('use-india-rate');
            const usBtn = this.dom.getElement('use-us-rate');
            if (indiaBtn) {
                const indiaRate = parseFloat(indiaBtn.dataset.rate || '12');
                indiaBtn.addEventListener('click', () => this.setSmartNudgeRate(indiaRate));
            }
            if (usBtn) {
                const usRate = parseFloat(usBtn.dataset.rate || '15');
                usBtn.addEventListener('click', () => this.setSmartNudgeRate(usRate));
            }

            document.addEventListener('click', (e: Event) => {
                if (!nudgePopover.contains(e.target as Node) && e.target !== nudgeBtn) {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                }
            });
            document.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Escape') {
                    nudgePopover.classList.add('hidden');
                    nudgeBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
    }
}
