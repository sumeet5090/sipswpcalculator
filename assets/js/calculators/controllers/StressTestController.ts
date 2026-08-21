import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { YearResult } from '../../types';

export interface StressScenario {
    id: string;
    dropPct: number;
    recoveryMonths: number;
    crashYear: number;
    lesson: string;
}

export const STRESS_SCENARIOS: Record<string, StressScenario> = {
    baseline: {
        id: 'baseline',
        dropPct: 0,
        recoveryMonths: 0,
        crashYear: 0,
        lesson: 'In a steady compounding market, monthly SIP systematically accumulates wealth with standard compound interest.'
    },
    lehman: {
        id: 'lehman',
        dropPct: 52,
        recoveryMonths: 24,
        crashYear: 3,
        lesson: 'During 2008 Lehman crisis, maintaining SIP bought units at rock-bottom NAV, accelerating wealth when markets broke new all-time highs within 24 months.'
    },
    covid: {
        id: 'covid',
        dropPct: 38,
        recoveryMonths: 8,
        crashYear: 5,
        lesson: 'The 2020 COVID flash crash rebounded in just 8 months. Investors who stayed disciplined achieved explosive compounding in the 2020-2024 bull run.'
    },
    dotcom: {
        id: 'dotcom',
        dropPct: 30,
        recoveryMonths: 36,
        crashYear: 2,
        lesson: 'Even through 3 consecutive flat/bear years, Rupee Cost Averaging accumulated large unit balances that multiplied exponentially in the subsequent cycle.'
    }
};

export class StressTestController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private activeScenario: string = 'baseline';
    private currentResults: YearResult[] = [];

    constructor(dom: DOMAdapter, formatter: CurrencyFormatter) {
        this.dom = dom;
        this.formatter = formatter;
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
    }

    setScenario(scenarioId: string): void {
        this.activeScenario = scenarioId;
        const card = this.dom.getElement('stress-test-simulator-card');
        if (!card) return;

        const buttons = card.querySelectorAll<HTMLButtonElement>('.stress-choice-btn');
        buttons.forEach(b => {
            if (b.dataset.scenario === scenarioId) {
                b.classList.add('border-emerald-500', 'text-white');
                b.classList.remove('border-slate-700', 'text-slate-300');
            } else {
                b.classList.remove('border-emerald-500', 'text-white');
                b.classList.add('border-slate-700', 'text-slate-300');
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
        const normalFinal = lastRow.combined_total;

        const drawdownEl = this.dom.getElement('stress-preview-drawdown');
        const recoveryEl = this.dom.getElement('stress-preview-recovery');
        const finalEl = this.dom.getElement('stress-preview-final');
        const lessonEl = this.dom.getElement('stress-lesson-text');

        if (scenario.dropPct === 0 || scenario.crashYear === 0 || scenario.crashYear > this.currentResults.length) {
            if (drawdownEl) drawdownEl.textContent = '₹ 0 (0%)';
            if (recoveryEl) recoveryEl.textContent = '0 Months (No Drop)';
            if (finalEl) finalEl.textContent = this.formatter.format(normalFinal);
        } else {
            const crashIdx = Math.min(scenario.crashYear - 1, this.currentResults.length - 1);
            const crashRow = this.currentResults[crashIdx];
            const preCrashBalance = crashRow.combined_total;
            const drawdownAmt = (scenario.dropPct / 100) * preCrashBalance;

            // Rebound modeling: Rupee Cost Averaging dip-buying achieves ~92-96% of standard baseline
            const reboundFinal = normalFinal * (1 - (scenario.dropPct / 400));

            if (drawdownEl) drawdownEl.textContent = `- ${this.formatter.format(drawdownAmt)} (-${scenario.dropPct}%)`;
            if (recoveryEl) recoveryEl.textContent = `${scenario.recoveryMonths} Months (${(scenario.recoveryMonths / 12).toFixed(1)} Yrs)`;
            if (finalEl) finalEl.textContent = this.formatter.format(reboundFinal);
        }

        if (lessonEl) lessonEl.textContent = scenario.lesson;
    }
}
