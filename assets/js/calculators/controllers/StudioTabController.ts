import { DOMAdapter } from '../../adapters/DOMAdapter';

export interface StudioTelemetryData {
    years: number;
    fireCoveragePercent?: number;
    fireCityName?: string;
    milestonesUnlocked?: number;
    totalMilestones?: number;
    maxStressDrawdownPercent?: number;
    targetEquitySplit?: number;
}

/**
 * StudioTabController
 * Coordinates tab switching, mobile scroll-centering, and real-time telemetry badging
 * for the Multi-Mode Analytical Studio.
 */
export class StudioTabController {
    private dom: DOMAdapter;
    private onTabChange?: (tabId: string) => void;

    // Strict pure light-mode active and inactive class descriptors
    private readonly activeClasses = [
        'bg-white', 'text-emerald-900', 'shadow-xs', 'border-slate-200/90', 'font-black'
    ];
    private readonly inactiveClasses = [
        'text-slate-600', 'hover:text-slate-900', 'hover:bg-white/60', 'font-bold', 'border-transparent'
    ];

    constructor(dom: DOMAdapter = new DOMAdapter(), onTabChange?: (tabId: string) => void) {
        this.dom = dom;
        this.onTabChange = onTabChange;
    }

    public init(): void {
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

                const statusDot = tab.querySelector<HTMLElement>('.w-2.h-2');
                const badge = tab.querySelector<HTMLElement>('span[id^="tab-telemetry-"]');

                if (isSelected) {
                    tab.classList.add(...this.activeClasses);
                    tab.classList.remove(...this.inactiveClasses);

                    if (statusDot) {
                        statusDot.className = 'w-2 h-2 rounded-full bg-emerald-500 shadow-2xs';
                    }
                    if (badge) {
                        badge.className = 'text-[9.5px] font-extrabold px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/70';
                    }

                    // Smooth horizontal auto-center on mobile viewports
                    tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    if (shouldFocus) tab.focus();
                } else {
                    tab.classList.remove(...this.activeClasses);
                    tab.classList.add(...this.inactiveClasses);

                    if (statusDot) {
                        statusDot.className = 'w-2 h-2 rounded-full bg-slate-300 group-hover:bg-slate-400';
                    }
                    if (badge) {
                        badge.className = 'text-[9.5px] font-extrabold px-1.5 py-0.5 rounded-md bg-slate-200/70 text-slate-700 group-hover:bg-emerald-50 group-hover:text-emerald-800 transition-colors';
                    }
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

        const lakhBtn = this.dom.getElement('studio-unit-lakh');
        const exactBtn = this.dom.getElement('studio-unit-exact');
        if (lakhBtn && exactBtn) {
            lakhBtn.addEventListener('click', () => {
                lakhBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border-slate-200/60');
                lakhBtn.classList.remove('text-slate-500');
                exactBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border-slate-200/60');
                exactBtn.classList.add('text-slate-500');
            });
            exactBtn.addEventListener('click', () => {
                exactBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border-slate-200/60');
                exactBtn.classList.remove('text-slate-500');
                lakhBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border-slate-200/60');
                lakhBtn.classList.add('text-slate-500');
            });
        }
    }

    /**
     * Updates live telemetry indicators across tab badges in real-time
     * when the calculation engine produces new results.
     */
    public updateTelemetry(data: StudioTelemetryData): void {
        // 1. Yearly Breakdown Horizon
        const breakdownTag = this.dom.getElement('tab-telemetry-breakdown');
        if (breakdownTag && data.years !== undefined) {
            breakdownTag.textContent = `${data.years} Yrs`;
        }

        // 2. City FIRE Readiness
        const fireTag = this.dom.getElement('tab-telemetry-fire');
        if (fireTag && data.fireCoveragePercent !== undefined) {
            const cityName = data.fireCityName || 'FIRE';
            fireTag.textContent = `${Math.round(data.fireCoveragePercent)}% ${cityName}`;
        }

        // 3. Milestone Hit Ratio
        const milestoneTag = this.dom.getElement('tab-telemetry-milestones');
        if (milestoneTag && data.milestonesUnlocked !== undefined && data.totalMilestones !== undefined) {
            milestoneTag.textContent = `${data.milestonesUnlocked}/${data.totalMilestones} Hit`;
        }

        // 4. Stress Test Shock
        const stressTag = this.dom.getElement('tab-telemetry-stress');
        if (stressTag && data.maxStressDrawdownPercent !== undefined) {
            stressTag.textContent = `-${Math.round(data.maxStressDrawdownPercent)}% Shock`;
        }

        // 5. Asset Rebalancing Ratio
        const rebalanceTag = this.dom.getElement('tab-telemetry-rebalance');
        if (rebalanceTag && data.targetEquitySplit !== undefined) {
            rebalanceTag.textContent = `${data.targetEquitySplit}:${100 - data.targetEquitySplit}`;
        }
    }
}

