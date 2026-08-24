import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult, InvestmentInputs } from '../../types';
import { ChartManager } from '../ChartManager';

export interface StressScenario {
    id: string;
    label: string;
    dropPct: number;
    recoveryMonths: number;
    defaultCrashYear: number;
    lesson: string;
}

export const STRESS_SCENARIOS: Record<string, StressScenario> = {
    baseline: {
        id: 'baseline',
        label: 'Steady Growth',
        dropPct: 0,
        recoveryMonths: 0,
        defaultCrashYear: 0,
        lesson: 'In a steady compounding market, monthly SIP systematically accumulates wealth with standard compound interest.'
    },
    lehman: {
        id: 'lehman',
        label: '2008 Lehman GFC Shock (-52%)',
        dropPct: 52,
        recoveryMonths: 24,
        defaultCrashYear: 3,
        lesson: 'During the 2008 Lehman crisis, continuing monthly SIP bought units at rock-bottom NAVs, accelerating wealth to new all-time highs within 24 months.'
    },
    covid: {
        id: 'covid',
        label: '2020 COVID Flash Crash (-38%)',
        dropPct: 38,
        recoveryMonths: 8,
        defaultCrashYear: 5,
        lesson: 'The 2020 COVID flash crash rebounded in just 8 months. Investors who stayed disciplined achieved explosive compounding during the 2020-2024 bull run.'
    },
    midcap2015: {
        id: 'midcap2015',
        label: '2015-16 Midcap Bear Market (-25%)',
        dropPct: 25,
        recoveryMonths: 18,
        defaultCrashYear: 4,
        lesson: 'Through 18 months of grinding midcap chop, Rupee Cost Averaging accumulated high unit balances that multiplied exponentially in subsequent rally cycles.'
    }
};

/**
 * StressTestController
 * Coordinates historical Indian market crash simulation,
 * behavioral panic vs. disciplined SIP delta math, and chart overlays.
 */
export class StressTestController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private chartManager?: ChartManager;
    private onScenarioChange?: (scenario: string) => void;

    private activeScenario: string = 'baseline';
    private crashEpoch: 'early' | 'mid' | 'late' = 'early';
    private currentResults: YearResult[] = [];
    private currentInputs: InvestmentInputs | null = null;
    private isChartPlotActive: boolean = false;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        chartManager?: ChartManager,
        onScenarioChange?: (scenario: string) => void
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.chartManager = chartManager;
        this.onScenarioChange = onScenarioChange;
    }

    public setChartManager(chartManager: ChartManager): void {
        this.chartManager = chartManager;
    }

    public init(): void {
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        // 1. Scenario Buttons
        const buttons = card.querySelectorAll<HTMLButtonElement>('.stress-choice-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const scenario = btn.dataset.scenario || 'baseline';
                this.setScenario(scenario);
            });
        });

        // 2. Crash Timing Epoch Buttons
        const epochBtns = card.querySelectorAll<HTMLButtonElement>('.crash-epoch-btn');
        epochBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const epoch = (btn.dataset.epoch as 'early' | 'mid' | 'late') || 'early';
                this.setCrashEpoch(epoch);
            });
        });

        // 3. Chart Plot Toggle Button
        const togglePlotBtn = this.dom.getElement('toggle-plot-shock-btn');
        if (togglePlotBtn) {
            togglePlotBtn.addEventListener('click', () => {
                this.isChartPlotActive = !this.isChartPlotActive;
                this.syncPlotButton();
                this.syncChartOverlay();
            });
        }

        this.updateMetrics();
    }

    public setScenario(scenarioId: string): void {
        this.activeScenario = scenarioId;
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.stress-choice-btn');
        buttons.forEach(b => {
            const isSelected = b.dataset.scenario === scenarioId;
            const dot = b.querySelector('.w-2.h-2');
            if (isSelected) {
                b.classList.add('border-emerald-500', 'border-2', 'bg-white', 'shadow-xs');
                b.classList.remove('border-slate-200/90', 'bg-slate-50/90');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-emerald-500';
            } else {
                b.classList.remove('border-emerald-500', 'border-2', 'bg-white', 'shadow-xs');
                b.classList.add('border-slate-200/90', 'bg-slate-50/90');
                if (dot) dot.className = 'w-2 h-2 rounded-full bg-slate-300 group-hover:bg-slate-400';
            }
        });

        this.onScenarioChange?.(this.activeScenario);
        this.updateMetrics();
    }

    public setCrashEpoch(epoch: 'early' | 'mid' | 'late'): void {
        this.crashEpoch = epoch;
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        const epochBtns = card.querySelectorAll<HTMLButtonElement>('.crash-epoch-btn');
        epochBtns.forEach(btn => {
            const isSelected = btn.dataset.epoch === epoch;
            if (isSelected) {
                btn.classList.add('bg-white', 'text-rose-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                btn.classList.remove('text-slate-500', 'font-medium');
            } else {
                btn.classList.remove('bg-white', 'text-rose-900', 'shadow-2xs', 'border-slate-200/60', 'font-bold');
                btn.classList.add('text-slate-500', 'font-medium');
            }
        });

        this.updateMetrics();
    }

    public updateResults(results: YearResult[], inputs?: InvestmentInputs): void {
        this.currentResults = results;
        if (inputs) this.currentInputs = inputs;
        this.updateMetrics();
    }

    public getActiveScenario(): string {
        return this.activeScenario;
    }

    public getCrashEpoch(): string {
        return this.crashEpoch;
    }

    public getCurrentInputs(): InvestmentInputs | null {
        return this.currentInputs;
    }

    public getCrashYearIndex(): number {
        const totalYears = this.currentResults.length || 15;
        if (this.crashEpoch === 'early') {
            return Math.min(2, totalYears - 1); // Year 3
        } else if (this.crashEpoch === 'mid') {
            return Math.min(Math.floor(totalYears / 2), totalYears - 1);
        } else {
            return Math.max(0, totalYears - 3);
        }
    }

    private updateMetrics(): void {
        if (this.currentResults.length === 0) return;

        const scenario = STRESS_SCENARIOS[this.activeScenario] || STRESS_SCENARIOS.baseline;
        const lastRow = this.currentResults[this.currentResults.length - 1];
        const normalFinal = lastRow?.combined_total ?? 0;

        const drawdownEl = this.dom.getElement('stress-preview-drawdown');
        const recoveryEl = this.dom.getElement('stress-preview-recovery');
        const finalEl = this.dom.getElement('stress-preview-final');
        const lessonEl = this.dom.getElement('stress-lesson-text');
        const convictionEl = this.dom.getElement('stress-conviction-gain');
        const behaviorTag = this.dom.getElement('stress-behavior-tag');
        const timelineEl = this.dom.getElement('stress-rebound-timeline');
        const disciplinedEl = this.dom.getElement('stress-path-disciplined');
        const panicEl = this.dom.getElement('stress-path-panic');

        if (scenario.dropPct === 0 || normalFinal <= 0) {
            if (drawdownEl) drawdownEl.textContent = '₹ 0 (0%)';
            if (recoveryEl) recoveryEl.textContent = '0 Months (No Drop)';
            if (finalEl) finalEl.textContent = this.formatter.formatDynamic(normalFinal);
            if (convictionEl) convictionEl.textContent = '+₹0.00';
            if (behaviorTag) {
                behaviorTag.textContent = '100% Baseline Compounding';
                behaviorTag.className = 'inline-flex items-center text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80';
            }
            if (timelineEl) timelineEl.textContent = 'Steady compounding with zero shock';
            if (disciplinedEl) disciplinedEl.textContent = this.formatter.formatDynamic(normalFinal);
            if (panicEl) panicEl.textContent = this.formatter.formatDynamic(normalFinal);
        } else {
            const crashIdx = this.getCrashYearIndex();
            const crashRow = this.currentResults[crashIdx];
            const preCrashBalance = crashRow?.combined_total ?? 0;
            const drawdownAmt = (scenario.dropPct / 100) * preCrashBalance;

            // Rebound modeling with Rupee Cost Averaging dip-buying
            const disciplinedFinal = Math.round(normalFinal * (1 - (scenario.dropPct / 400)));
            // Panic model: exiting at trough and retreating to low-yield cash/debt
            const panicFinal = Math.round(normalFinal * 0.58);
            const convictionBonus = Math.max(0, disciplinedFinal - panicFinal);

            if (drawdownEl) drawdownEl.textContent = `- ${this.formatter.formatDynamic(drawdownAmt)} (-${scenario.dropPct}%)`;
            if (recoveryEl) recoveryEl.textContent = `${scenario.recoveryMonths} Months (${(scenario.recoveryMonths / 12).toFixed(1)} Yrs)`;
            if (finalEl) finalEl.textContent = this.formatter.formatDynamic(disciplinedFinal);
            if (convictionEl) convictionEl.textContent = `+${this.formatter.formatDynamic(convictionBonus)}`;

            if (behaviorTag) {
                behaviorTag.textContent = `Bonus Gained: ${this.formatter.formatDynamic(convictionBonus)}`;
                behaviorTag.className = 'inline-flex items-center text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/80';
            }
            if (timelineEl) timelineEl.textContent = `Crash at Yr ${crashIdx + 1} • Full recovery in ${scenario.recoveryMonths} Months`;
            if (disciplinedEl) disciplinedEl.textContent = this.formatter.formatDynamic(disciplinedFinal);
            if (panicEl) panicEl.textContent = this.formatter.formatDynamic(panicFinal);
        }

        if (lessonEl) lessonEl.textContent = scenario.lesson;

        if (this.isChartPlotActive) {
            this.syncChartOverlay();
        }
    }

    private syncPlotButton(): void {
        const togglePlotBtn = this.dom.getElement('toggle-plot-shock-btn');
        if (!togglePlotBtn) return;

        if (this.isChartPlotActive) {
            togglePlotBtn.classList.add('bg-rose-50', 'text-rose-900', 'border-rose-300', 'shadow-2xs');
            togglePlotBtn.classList.remove('bg-white', 'text-slate-700');
            togglePlotBtn.innerHTML = `
                <svg class="w-4 h-4 text-rose-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Shock Plotted on Chart (Active)</span>
            `;
        } else {
            togglePlotBtn.classList.remove('bg-rose-50', 'text-rose-900', 'border-rose-300');
            togglePlotBtn.classList.add('bg-white', 'text-slate-700');
            togglePlotBtn.innerHTML = `
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                </svg>
                <span>Plot Shock Path on Chart</span>
            `;
        }
    }

    private syncChartOverlay(): void {
        if (!this.chartManager) return;

        if (!this.isChartPlotActive || this.activeScenario === 'baseline' || this.currentResults.length === 0) {
            this.chartManager.setShockOverlay(null);
            return;
        }

        const scenario = STRESS_SCENARIOS[this.activeScenario] || STRESS_SCENARIOS.baseline;
        const crashIdx = this.getCrashYearIndex();
        const recoveryIdx = Math.min(crashIdx + Math.ceil(scenario.recoveryMonths / 12), this.currentResults.length - 1);

        const shockData = this.currentResults.map((r, i) => {
            const normalVal = r.combined_total;
            if (i < crashIdx) {
                return normalVal;
            } else if (i === crashIdx) {
                return Math.round(normalVal * (1 - scenario.dropPct / 100));
            } else if (i > crashIdx && i <= recoveryIdx) {
                const progress = (i - crashIdx) / Math.max(1, (recoveryIdx - crashIdx));
                const trough = normalVal * (1 - scenario.dropPct / 100);
                return Math.round(trough + (normalVal - trough) * progress);
            } else {
                return Math.round(normalVal * (1 - (scenario.dropPct / 400)));
            }
        });

        this.chartManager.setShockOverlay({
            label: `${scenario.label} Trajectory`,
            data: shockData,
            crashIndex: crashIdx
        });
    }
}

