import { DOMAdapter } from '../../adapters/DOMAdapter';

export class TabController {
    private dom: DOMAdapter;

    constructor(dom: DOMAdapter = new DOMAdapter()) {
        this.dom = dom;
    }

    init(): void {
        const sipTab = this.dom.getElement('tab-sip');
        const swpTab = this.dom.getElement('tab-swp');

        const switchTab = (tab: string, shouldFocus = false) => {
            const sipPanel = this.dom.getElement('panel-sip');
            const swpPanel = this.dom.getElement('panel-swp');

            if (!sipPanel || !swpPanel || !sipTab || !swpTab) return;

            const sipSpan = sipTab.querySelector('span');
            const swpSpan = swpTab.querySelector('span');

            if (tab === 'sip') {
                sipPanel.classList.remove('hidden');
                swpPanel.classList.add('hidden');
                sipTab.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/30');
                sipTab.classList.remove('text-slate-500', 'hover:text-slate-700');
                if (sipSpan) {
                    sipSpan.classList.add('bg-emerald-100', 'text-emerald-800');
                    sipSpan.classList.remove('bg-slate-200/80', 'text-slate-600');
                }
                swpTab.classList.remove('bg-white', 'text-rose-600', 'shadow-sm', 'border', 'border-slate-200/30');
                swpTab.classList.add('text-slate-500', 'hover:text-slate-700');
                if (swpSpan) {
                    swpSpan.classList.add('bg-slate-200/80', 'text-slate-600');
                    swpSpan.classList.remove('bg-rose-100', 'text-rose-800');
                }
                sipTab.setAttribute('aria-selected', 'true');
                sipTab.setAttribute('tabindex', '0');
                swpTab.setAttribute('aria-selected', 'false');
                swpTab.setAttribute('tabindex', '-1');
                if (shouldFocus) sipTab.focus();
            } else {
                swpPanel.classList.remove('hidden');
                sipPanel.classList.add('hidden');
                swpTab.classList.add('bg-white', 'text-rose-600', 'shadow-sm', 'border', 'border-slate-200/30');
                swpTab.classList.remove('text-slate-500', 'hover:text-slate-700');
                if (swpSpan) {
                    swpSpan.classList.add('bg-rose-100', 'text-rose-800');
                    swpSpan.classList.remove('bg-slate-200/80', 'text-slate-600');
                }
                sipTab.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/30');
                sipTab.classList.add('text-slate-500', 'hover:text-slate-700');
                if (sipSpan) {
                    sipSpan.classList.add('bg-slate-200/80', 'text-slate-600');
                    sipSpan.classList.remove('bg-emerald-100', 'text-emerald-800');
                }
                sipTab.setAttribute('aria-selected', 'false');
                sipTab.setAttribute('tabindex', '-1');
                swpTab.setAttribute('aria-selected', 'true');
                swpTab.setAttribute('tabindex', '0');
                if (shouldFocus) swpTab.focus();
            }
        };

        if (sipTab) {
            sipTab.addEventListener('click', () => switchTab('sip'));
            sipTab.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    switchTab('swp', true);
                }
            });
        }
        if (swpTab) {
            swpTab.addEventListener('click', () => switchTab('swp'));
            swpTab.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    switchTab('sip', true);
                }
            });
        }
    }
}
