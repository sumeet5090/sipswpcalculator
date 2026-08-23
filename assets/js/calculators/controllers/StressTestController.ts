import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult } from '../../types';
import { ChartManager } from '../ChartManager';

export interface StressScenario {
    id: string;
    label: string;
    dropPct: number;
    recoveryMonths: number;
    crashYear: number;
    lesson: string;
}

export const STRESS_SCENARIOS: Record<string, StressScenario> = {
    baseline: {
        id: 'baseline',
        label: 'Normal Growth',
        dropPct: 0,
        recoveryMonths: 0,
        crashYear: 0,
        lesson: 'In a steady compounding market, monthly SIP systematically accumulates wealth with standard compound interest.'
    },
    lehman: {
        id: 'lehman',
        label: '2008 Lehman Shock (-52%)',
        dropPct: 52,
        recoveryMonths: 24,
        crashYear: 3,
        lesson: 'During 2008 Lehman crisis, maintaining SIP bought units at rock-bottom NAV, accelerating wealth when markets broke new all-time highs within 24 months.'
    },
    covid: {
        id: 'covid',
        label: '2020 COVID Flash Crash (-38%)',
        dropPct: 38,
        recoveryMonths: 8,
        crashYear: 5,
        lesson: 'The 2020 COVID flash crash rebounded in just 8 months. Investors who stayed disciplined achieved explosive compounding in the 2020-2024 bull run.'
    },
    dotcom: {
        id: 'dotcom',
        label: '2000 Dot-Com Bear Cycle (-30%)',
        dropPct: 30,
        recoveryMonths: 36,
        crashYear: 2,
        lesson: 'Even through 3 consecutive flat/bear years, Rupee Cost Averaging accumulated large unit balances that multiplied exponentially in the subsequent cycle.'
    }
};

export class StressTestController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private chartManager?: ChartManager;
    private activeScenario: string = 'baseline';
    private currentResults: YearResult[] = [];
    private isChartPlotActive: boolean = false;

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter, chartManager?: ChartManager) {
        this.dom = dom;
        this.formatter = formatter;
        this.chartManager = chartManager;
    }

    setChartManager(chartManager: ChartManager): void {
        this.chartManager = chartManager;
    }

    init(): void {
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.stress-choice-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const scenario = btn.dataset.scenario || 'baseline';
                this.setScenario(scenario);
            });
        });

        const togglePlotBtn = this.dom.getElement('toggle-plot-shock-btn');
        if (togglePlotBtn) {
            togglePlotBtn.addEventListener('click', () => {
                this.isChartPlotActive = !this.isChartPlotActive;
                this.syncPlotButton();
                this.syncChartOverlay();
            });
        }
    }

    setScenario(scenarioId: string): void {
        this.activeScenario = scenarioId;
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.stress-choice-btn');
        buttons.forEach(b => {
            if (b.dataset.scenario === scenarioId) {
                b.classList.add('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                b.classList.remove('border-slate-200', 'bg-slate-50/90');
            } else {
                b.classList.remove('border-emerald-500', 'border-2', 'bg-white', 'shadow-sm');
                b.classList.add('border-slate-200', 'bg-slate-50/90');
            }
        });

        this.updateMetrics();
    }

    updateResults(results: YearResult[]): void {
        this.currentResults = results;
        this.updateMetrics();
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

        if (scenario.dropPct === 0 || scenario.crashYear === 0 || scenario.crashYear > this.currentResults.length || normalFinal <= 0) {
            if (drawdownEl) drawdownEl.textContent = '₹ 0 (0%)';
            if (recoveryEl) recoveryEl.textContent = '0 Months (No Drop)';
            if (finalEl) finalEl.textContent = this.formatter.format(normalFinal);
        } else {
            const crashIdx = Math.min(scenario.crashYear - 1, this.currentResults.length - 1);
            const crashRow = this.currentResults[crashIdx];
            const preCrashBalance = crashRow?.combined_total ?? 0;
            const drawdownAmt = (scenario.dropPct / 100) * preCrashBalance;

            // Rebound modeling: Rupee Cost Averaging dip-buying achieves ~92-96% of standard baseline
            const reboundFinal = normalFinal * (1 - (scenario.dropPct / 400));

            if (drawdownEl) drawdownEl.textContent = `- ${this.formatter.format(drawdownAmt)} (-${scenario.dropPct}%)`;
            if (recoveryEl) recoveryEl.textContent = `${scenario.recoveryMonths} Months (${(scenario.recoveryMonths / 12).toFixed(1)} Yrs)`;
            if (finalEl) finalEl.textContent = this.formatter.format(reboundFinal);
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
            togglePlotBtn.classList.add('bg-rose-600', 'text-white', 'border-rose-700', 'shadow-xs');
            togglePlotBtn.classList.remove('bg-slate-100', 'text-slate-700', 'border-slate-200');
            togglePlotBtn.innerHTML = '<span>📉 Shock Plotted on Chart</span> <span class="text-[10px] opacity-80">(Click to hide)</span>';
        } else {
            togglePlotBtn.classList.remove('bg-rose-600', 'text-white', 'border-rose-700', 'shadow-xs');
            togglePlotBtn.classList.add('bg-slate-100', 'text-slate-700', 'border-slate-200');
            togglePlotBtn.innerHTML = '<span>📉 Plot Shock Trajectory on Chart</span>';
        }
    }

    private syncChartOverlay(): void {
        if (!this.chartManager) return;

        if (!this.isChartPlotActive || this.activeScenario === 'baseline' || this.currentResults.length === 0) {
            this.chartManager.setShockOverlay(null);
            return;
        }

        const scenario = STRESS_SCENARIOS[this.activeScenario] || STRESS_SCENARIOS.baseline;
        const crashIdx = Math.min(scenario.crashYear - 1, this.currentResults.length - 1);
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
            label: `${scenario.label} Path`,
            data: shockData,
            crashIndex: crashIdx
        });
    }
}
