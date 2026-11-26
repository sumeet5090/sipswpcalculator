import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { MathEngine } from '../MathEngine.ts';
import { SliderManager } from '../SliderManager.ts';
import { ChartManager } from '../ChartManager.ts';
import { CalculatorRegistry } from '../constants/CalculatorRegistry.ts';
import { A11yAnnouncer } from './A11yAnnouncer.ts';
import { saveCalculation, buildSavedPlanTitle } from './SavedPlansStorage.ts';
import type { EngagementSubsystem, LifecycleSubsystem } from '../subsystems/index.ts';
import type { InvestmentInputs, YearResult } from '../../types';

export interface CoreFormControlsDeps {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    sliderManager: SliderManager;
    chartManager: ChartManager;
    engagementSubsystem: EngagementSubsystem;
    lifecycleSubsystem: LifecycleSubsystem;
    getInputs: () => InvestmentInputs;
    getExportPayload: () => Record<string, string | number | boolean>;
    getLatestResults: () => YearResult[];
    getActiveGoalMode: () => string;
    setGoalMode: (mode: string) => void;
    syncSwpToggleState: () => void;
    triggerCalculation: () => void;
    onResizeFit: () => void;
}

export function bindSavePlanAction(deps: {
    dom: DOMAdapter;
    mode: string;
    lifecycleSubsystem: LifecycleSubsystem;
    getInputs: () => InvestmentInputs;
    getExportPayload: () => Record<string, string | number | boolean>;
    getLatestResults: () => YearResult[];
}): void {
    const savePlanBtn = deps.dom.getElement<HTMLButtonElement>('saveCalculationBtn');
    if (!savePlanBtn) return;

    savePlanBtn.addEventListener('click', () => {
        const inputs = deps.getInputs();
        const payload = deps.getExportPayload();
        const corpusEl = deps.dom.getElement('summary-corpus')
            || deps.dom.getElement('mini-hud-corpus');
        const finalCorpusText = corpusEl?.textContent?.trim() || '';

        const saved = saveCalculation({
            title: buildSavedPlanTitle(deps.mode, payload, inputs),
            calcType: deps.mode,
            sipAmount: inputs.sip,
            sipDuration: inputs.years,
            interestRate: inputs.rate,
            stepUpPct: inputs.stepup,
            swpEnabled: inputs.enable_swp,
            swpWithdrawal: inputs.swp_withdrawal,
            swpDuration: inputs.swp_years,
            swpRate: inputs.swp_rate,
            swpStepUp: inputs.swp_stepup,
            finalCorpus: finalCorpusText
        });

        if (!CalculatorRegistry.isSpecializedMode(deps.mode)) {
            deps.lifecycleSubsystem.saveScenarioDiffSnapshot(inputs, deps.getLatestResults());
        }

        const textEl = deps.dom.getElement('saveBtnText');
        const iconEl = deps.dom.getElement('saveBtnIcon');
        if (saved && textEl) {
            const origText = textEl.textContent || 'Save Plan Snapshot';
            const origIconHtml = iconEl?.innerHTML ?? '';
            textEl.textContent = 'Saved! ✓';
            if (iconEl) {
                iconEl.textContent = '✓';
                iconEl.classList.add('text-emerald-600', 'font-bold');
            }
            setTimeout(() => {
                textEl.textContent = origText;
                if (iconEl) {
                    iconEl.innerHTML = origIconHtml;
                    iconEl.classList.remove('text-emerald-600', 'font-bold');
                }
            }, 2200);
        }
    });
}

export function bindCoreFormControls(deps: CoreFormControlsDeps): void {
    const {
        dom,
        formatter,
        sliderManager,
        chartManager,
        engagementSubsystem,
        lifecycleSubsystem,
        getInputs,
        getLatestResults,
        getActiveGoalMode,
        setGoalMode,
        syncSwpToggleState,
        triggerCalculation,
    } = deps;

    sliderManager.syncAll({
        'sip': 'sip_range',
        'years': 'years_range',
        'rate': 'rate_range',
        'stepup': 'stepup_range',
        'inflation': 'inflation_range',
        'lumpsum': 'lumpsum_range',
        'corpus': 'corpus_range',
        'target_corpus': 'target_corpus_range',
        'swp_withdrawal': 'swp_withdrawal_range',
        'swp_years': 'swp_years_range',
        'swp_stepup': 'swp_stepup_range',
        'swp_rate': 'swp_rate_range',
    });

    const growBtn = dom.getElement('goal-grow');
    const targetBtn = dom.getElement('goal-target');
    if (growBtn) growBtn.addEventListener('click', () => setGoalMode('grow'));
    if (targetBtn) targetBtn.addEventListener('click', () => setGoalMode('target'));

    const sipInput = dom.getElement('sip');
    const sipRange = dom.getElement('sip_range');
    const autoSwitchToGrow = () => {
        if (getActiveGoalMode() === 'target') {
            setGoalMode('grow');
        }
    };
    if (sipInput) sipInput.addEventListener('input', autoSwitchToGrow);
    if (sipRange) sipRange.addEventListener('input', autoSwitchToGrow);

    const swpWithdrawal = dom.getElement('swp_withdrawal');
    const swpWithdrawalRange = dom.getElement('swp_withdrawal_range');
    const swpYears = dom.getElement('swp_years');
    const swpYearsRange = dom.getElement('swp_years_range');
    let swpRaf: number | null = null;
    const handleSwpInput = () => {
        if (swpRaf) cancelAnimationFrame(swpRaf);
        swpRaf = requestAnimationFrame(() => {
            const inputs = getInputs();
            if (inputs.enable_swp && inputs.swp_withdrawal > 0 && inputs.swp_years > 0) {
                const reqCorpus = MathEngine.calculateRequiredStartingCorpusForSwp(inputs);
                if (getActiveGoalMode() === 'target') {
                    dom.setValue('target_corpus', reqCorpus);

                    const targetRangeEl = dom.getElement<HTMLInputElement>('target_corpus_range');
                    if (targetRangeEl) {
                        const defaultMax = parseFloat(targetRangeEl.getAttribute('max') || '50000000');
                        targetRangeEl.max = reqCorpus > defaultMax ? String(reqCorpus) : String(defaultMax);
                        dom.setValue('target_corpus_range', reqCorpus);
                    }
                }
            }
        });
    };

    if (swpWithdrawal) swpWithdrawal.addEventListener('input', handleSwpInput);
    if (swpWithdrawalRange) swpWithdrawalRange.addEventListener('input', handleSwpInput);
    if (swpYears) swpYears.addEventListener('input', handleSwpInput);
    if (swpYearsRange) swpYearsRange.addEventListener('input', handleSwpInput);

    const swpToggle = dom.getElement<HTMLInputElement>('enable_swp');
    if (swpToggle) {
        swpToggle.addEventListener('change', () => syncSwpToggleState());
    }

    const postTaxToggle = dom.getElement<HTMLInputElement>('show_post_tax');
    if (postTaxToggle) {
        postTaxToggle.addEventListener('change', () => {
            dom.getElements<HTMLElement>('tax-col').forEach(el => {
                el.style.display = postTaxToggle.checked ? '' : 'none';
            });
            triggerCalculation();
        });
    }

    const wealthMapToggle = dom.getElement('show_wealth_map');
    if (wealthMapToggle) {
        wealthMapToggle.addEventListener('change', () => triggerCalculation());
    }

    const bridgeBtn = dom.getElement('apply-sip-to-swp-btn') || dom.getElement('edit-as-standalone-swp-btn');
    if (bridgeBtn) {
        bridgeBtn.addEventListener('click', () => {
            const latestResults = getLatestResults();
            if (latestResults.length === 0) return;
            const inputs = getInputs();
            const sipYears = inputs.years || 0;
            const maturedRow = (inputs.enable_swp && sipYears > 0 && sipYears <= latestResults.length)
                ? latestResults[sipYears - 1]
                : latestResults[latestResults.length - 1];
            const maturedCorpus = maturedRow.combined_total;
            if (maturedCorpus <= 0) return;

            sliderManager.updateFieldValue('corpus', maturedCorpus);

            const swpCheck = dom.getElement<HTMLInputElement>('enable_swp');
            if (swpCheck) {
                swpCheck.checked = true;
                syncSwpToggleState();
            }

            const swpTab = dom.getElement('tab-swp');
            if (swpTab) swpTab.click();

            engagementSubsystem.playChime();
            engagementSubsystem.vibrate([15, 30, 15]);
            A11yAnnouncer.announce(`Transferred matured SIP corpus of ${formatter.formatDynamic(maturedCorpus)} into SWP initial balance.`);
        });
    }

    const corridorToggle = dom.getElement<HTMLInputElement>('show_historical_corridor');
    if (corridorToggle) {
        corridorToggle.addEventListener('change', () => {
            chartManager.setHistoricalCorridor(corridorToggle.checked);
        });
    }

    const stepupBoostBtn = dom.getElement<HTMLButtonElement>('apply-10pct-stepup-btn');
    if (stepupBoostBtn) {
        stepupBoostBtn.addEventListener('click', () => {
            const current = parseFloat(dom.getValue('stepup') || '0') || 0;
            const nextVal = current === 0 ? 10 : Math.min(25, current + 5);
            sliderManager.updateFieldValue('stepup', nextVal);
            triggerCalculation();
            engagementSubsystem.triggerMicroBurst();
            A11yAnnouncer.announce(`Applied salary step-up: ${nextVal}%`);
        });
    }

    const snapshotBtn = dom.getElement('snapshot-scenario-btn');
    if (snapshotBtn) {
        snapshotBtn.addEventListener('click', () => {
            lifecycleSubsystem.saveScenarioDiffSnapshot(getInputs(), getLatestResults());
        });
    }
}

export function bindResizeObserver(onWidthChanged: () => void): void {
    let resizeTimer: ReturnType<typeof setTimeout> | undefined;
    let lastWidth = window.innerWidth;
    window.addEventListener('resize', () => {
        if (window.innerWidth === lastWidth) {
            return;
        }
        lastWidth = window.innerWidth;
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(onWidthChanged, 150);
    });
}
