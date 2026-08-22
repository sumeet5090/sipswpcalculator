import { DOMAdapter } from '../../adapters/DOMAdapter';

/**
 * StudioTabController
 * Coordinates tab switching within the Multi-Mode Analytical Studio
 * (Yearly Breakdown Table, Milestone Roadmap, Black Swan Stress-Test, City FIRE Benchmark, Asset Allocation).
 */
export class StudioTabController {
    private dom: DOMAdapter;
    private onTabChange?: (tabId: string) => void;

    constructor(dom: DOMAdapter = new DOMAdapter(), onTabChange?: (tabId: string) => void) {
        this.dom = dom;
        this.onTabChange = onTabChange;
    }

    init(): void {
        const tabContainer = this.dom.getElement('studio-tabs-nav');
        if (!tabContainer) return;

        const tabs = tabContainer.querySelectorAll<HTMLButtonElement>('.studio-tab-btn');
        const panels = document.querySelectorAll<HTMLElement>('.studio-tab-panel');

        if (tabs.length === 0 || panels.length === 0) return;

        const switchTab = (targetTabId: string, shouldFocus = false) => {
            tabs.forEach(tab => {
                const isSelected = tab.id === targetTabId;
                tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                tab.setAttribute('tabindex', isSelected ? '0' : '-1');

                if (isSelected) {
                    tab.classList.add('bg-white', 'text-emerald-800', 'shadow-xs', 'border-slate-200/90', 'font-bold');
                    tab.classList.remove('text-slate-500', 'hover:text-slate-800', 'font-medium', 'border-transparent');
                    if (shouldFocus) tab.focus();
                } else {
                    tab.classList.remove('bg-white', 'text-emerald-800', 'shadow-xs', 'border-slate-200/90', 'font-bold');
                    tab.classList.add('text-slate-500', 'hover:text-slate-800', 'font-medium', 'border-transparent');
                }
            });

            panels.forEach(panel => {
                const targetPanelId = targetTabId.replace('tab-', 'panel-');
                if (panel.id === targetPanelId) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });

            this.onTabChange?.(targetTabId);
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                switchTab(tab.id);
            });

            tab.addEventListener('keydown', (e: KeyboardEvent) => {
                let nextIndex = index;
                if (e.key === 'ArrowRight') {
                    nextIndex = (index + 1) % tabs.length;
                    e.preventDefault();
                    switchTab(tabs[nextIndex].id, true);
                } else if (e.key === 'ArrowLeft') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                    e.preventDefault();
                    switchTab(tabs[nextIndex].id, true);
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    switchTab(tabs[0].id, true);
                } else if (e.key === 'End') {
                    e.preventDefault();
                    switchTab(tabs[tabs.length - 1].id, true);
                }
            });
        });
    }
}
