import { DOMAdapter } from '../../adapters/DOMAdapter';

export class TabController {
    private dom: DOMAdapter;
    private onSwpModeActivated?: () => void;

    constructor(dom: DOMAdapter = new DOMAdapter(), onSwpModeActivated?: () => void) {
        this.dom = dom;
        this.onSwpModeActivated = onSwpModeActivated;
    }

    init(): void {
        const comboTab = this.dom.getElement('tab-combo');
        const sipTab = this.dom.getElement('tab-sip');
        const swpTab = this.dom.getElement('tab-swp');

        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');

        const setTabState = (
            tab: HTMLElement | null,
            active: boolean,
            activeColorClasses: string[],
            badgeActiveClasses: string[]
        ) => {
            if (!tab) return;
            const span = tab.querySelector('span');
            if (active) {
                tab.classList.add('bg-white', 'shadow-flat', 'border', 'border-slate-200/60', ...activeColorClasses);
                tab.classList.remove('text-slate-500', 'hover:text-slate-900');
                tab.setAttribute('aria-selected', 'true');
                tab.setAttribute('tabindex', '0');
                if (span) {
                    span.className = `flex items-center justify-center w-4 h-4 rounded-full text-micro font-extrabold ${badgeActiveClasses[0]}`;
                }
            } else {
                tab.classList.remove('bg-white', 'shadow-flat', 'border', 'border-slate-200/60', ...activeColorClasses);
                tab.classList.add('text-slate-500', 'hover:text-slate-900');
                tab.setAttribute('aria-selected', 'false');
                tab.setAttribute('tabindex', '-1');
                if (span) {
                    span.className = `flex items-center justify-center w-4 h-4 rounded-full text-micro font-extrabold ${badgeActiveClasses[1]}`;
                }
            }
        };

        const switchTab = (tab: 'combo' | 'sip' | 'swp', shouldFocus = false) => {
            const sipPanel = this.dom.getElement('panel-sip');
            const swpPanel = this.dom.getElement('panel-swp');
            const stage1Header = this.dom.getElement('stage-1-header');
            const stage2Header = this.dom.getElement('stage-2-header');
            const stageDivider = this.dom.getElement('stage-transition-divider');
            const standaloneCorpus = this.dom.getElement('swp-standalone-corpus');
            const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
            const swpFields = this.dom.getElement('swp-fields');

            if (!sipPanel || !swpPanel) return;

            if (tab === 'combo') {
                if (appEl) appEl.dataset.mode = 'combo';

                sipPanel.classList.remove('hidden');
                swpPanel.classList.remove('hidden');
                if (stage1Header) stage1Header.classList.remove('hidden');
                if (stage2Header) stage2Header.classList.remove('hidden');
                if (stageDivider) stageDivider.classList.remove('hidden');
                if (standaloneCorpus) standaloneCorpus.classList.add('hidden');

                if (swpToggle) {
                    swpToggle.checked = true;
                    swpToggle.setAttribute('aria-expanded', 'true');
                }
                if (swpFields) {
                    swpFields.style.display = 'block';
                    swpFields.style.opacity = '1';
                    swpFields.style.pointerEvents = 'auto';
                    swpFields.setAttribute('aria-hidden', 'false');
                    const childInputs = swpFields.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select');
                    childInputs.forEach(input => { input.disabled = false; });
                }

                setTabState(comboTab, true, ['text-emerald-800'], ['bg-emerald-100 text-emerald-800', 'bg-slate-200 text-slate-600']);
                setTabState(sipTab, false, ['text-growth-emphasis'], ['bg-growth-surface text-growth-emphasis', 'bg-slate-200 text-slate-600']);
                setTabState(swpTab, false, ['text-rose-600'], ['bg-rose-100 text-rose-800', 'bg-slate-200 text-slate-600']);

                this.onSwpModeActivated?.();
                if (shouldFocus && comboTab) comboTab.focus();

            } else if (tab === 'sip') {
                if (appEl) appEl.dataset.mode = 'sip';

                sipPanel.classList.remove('hidden');
                swpPanel.classList.add('hidden');
                if (stage1Header) stage1Header.classList.add('hidden');
                if (stage2Header) stage2Header.classList.add('hidden');
                if (stageDivider) stageDivider.classList.add('hidden');
                if (standaloneCorpus) standaloneCorpus.classList.add('hidden');

                if (swpToggle) {
                    swpToggle.checked = false;
                    swpToggle.setAttribute('aria-expanded', 'false');
                }
                if (swpFields) {
                    swpFields.style.display = 'none';
                    swpFields.style.opacity = '0';
                    swpFields.style.pointerEvents = 'none';
                    swpFields.setAttribute('aria-hidden', 'true');
                    const childInputs = swpFields.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select');
                    childInputs.forEach(input => { input.disabled = true; });
                }

                setTabState(comboTab, false, ['text-emerald-800'], ['bg-emerald-100 text-emerald-800', 'bg-slate-200 text-slate-600']);
                setTabState(sipTab, true, ['text-growth-emphasis'], ['bg-growth-surface text-growth-emphasis', 'bg-slate-200 text-slate-600']);
                setTabState(swpTab, false, ['text-rose-600'], ['bg-rose-100 text-rose-800', 'bg-slate-200 text-slate-600']);

                this.onSwpModeActivated?.();
                if (shouldFocus && sipTab) sipTab.focus();

            } else if (tab === 'swp') {
                if (appEl) appEl.dataset.mode = 'swp';

                sipPanel.classList.add('hidden');
                swpPanel.classList.remove('hidden');
                if (stage1Header) stage1Header.classList.add('hidden');
                if (stage2Header) stage2Header.classList.add('hidden');
                if (stageDivider) stageDivider.classList.add('hidden');
                if (standaloneCorpus) standaloneCorpus.classList.remove('hidden');

                if (swpToggle) {
                    swpToggle.checked = true;
                    swpToggle.setAttribute('aria-expanded', 'true');
                }
                if (swpFields) {
                    swpFields.style.display = 'block';
                    swpFields.style.opacity = '1';
                    swpFields.style.pointerEvents = 'auto';
                    swpFields.setAttribute('aria-hidden', 'false');
                    const childInputs = swpFields.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select');
                    childInputs.forEach(input => { input.disabled = false; });
                }

                setTabState(comboTab, false, ['text-emerald-800'], ['bg-emerald-100 text-emerald-800', 'bg-slate-200 text-slate-600']);
                setTabState(sipTab, false, ['text-growth-emphasis'], ['bg-growth-surface text-growth-emphasis', 'bg-slate-200 text-slate-600']);
                setTabState(swpTab, true, ['text-rose-600'], ['bg-rose-100 text-rose-800', 'bg-slate-200 text-slate-600']);

                this.onSwpModeActivated?.();
                if (shouldFocus && swpTab) swpTab.focus();
            }
        };

        if (comboTab) {
            comboTab.addEventListener('click', () => switchTab('combo'));
            comboTab.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    switchTab('sip', true);
                }
            });
        }

        if (sipTab) {
            sipTab.addEventListener('click', () => switchTab('sip'));
            sipTab.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    switchTab('swp', true);
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (comboTab) switchTab('combo', true);
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
