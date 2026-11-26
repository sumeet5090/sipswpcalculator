/**
 * CalculatorApp.ts
 * Core frontend application orchestrator.
 * Delegates specialized domains to cohesive Subsystems:
 * - ExportSubsystem: PDF, CSV, QR, and social sharing
 * - EngagementSubsystem: Quiz, milestones, audio feedback, nudges, city FIRE benchmarks
 * - LifecycleSubsystem: Stress tests, rebalancing, tax waterfall, longevity guardian, and undo/redo
 * - ErgonomicsSubsystem: Mobile deck, keyboards, command palette, glossary, and HUD
 */
import { eventBus } from '../utils/EventBus.ts';
import { MathEngine } from './MathEngine.ts';
import { CurrencyFormatter } from './CurrencyHelper.ts';
import { InputValidator } from './InputValidator.ts';
import { ChartManager } from './ChartManager.ts';
import { AnalyticsService } from './AnalyticsLogger.ts';
import { SliderManager } from './SliderManager.ts';
import { DOMAdapter } from '../adapters/DOMAdapter.ts';
import { GrowStrategy } from './strategies/GrowStrategy.ts';
import { TargetCorpusStrategy } from './strategies/TargetCorpusStrategy.ts';
import { CalculatorStrategy } from './strategies/CalculatorStrategy.ts';
import { InvestmentInputs, YearResult } from '../types';
import { StepperController } from './controllers/StepperController.ts';
import { TabController } from './controllers/TabController.ts';
import { UrlStateController } from './controllers/UrlStateController.ts';
import { ResultsController } from './controllers/ResultsController.ts';
import { SummaryMetricsController } from './controllers/SummaryMetricsController.ts';
import { ChartScrubbingController } from './controllers/ChartScrubbingController.ts';
import { LumpsumDisclosureController } from './controllers/LumpsumDisclosureController.ts';
import { ContextualBridgeController } from './controllers/ContextualBridgeController.ts';
import { A11yAnnouncer } from './helpers/A11yAnnouncer.ts';
import { CalculatorRegistry } from './constants/CalculatorRegistry.ts';
import { ModalScrollLockHelper } from './helpers/ModalScrollLockHelper.ts';
import { SpecializedCalculatorController } from './controllers/SpecializedCalculatorController.ts';
import {
    readCoreInputs,
    buildExportPayload,
    applyCoreRestoredInputs
} from './helpers/CoreInputReader.ts';
import {
    bindPassiveSeoClickListeners,
    scheduleSettledAnalytics
} from './helpers/PassiveTelemetryHelper.ts';
import {
    bindSavePlanAction,
    bindCoreFormControls,
    bindResizeObserver
} from './helpers/CoreFormControlsBinder.ts';
import {
    ExportSubsystem,
    EngagementSubsystem,
    LifecycleSubsystem,
    ErgonomicsSubsystem
} from './subsystems/index.ts';

export class CalculatorApp {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private chartManager: ChartManager;
    private chartScrubbingController: ChartScrubbingController;
    private analytics: AnalyticsService;
    private userHasInteracted: boolean;
    private interactionCount: number;
    private latestResults: YearResult[];
    private activeGoalMode: string;
    private strategies: Record<string, CalculatorStrategy>;
    private sliderManager: SliderManager;
    private resultsController: ResultsController;
    private summaryMetricsController: SummaryMetricsController;
    private lumpsumDisclosureController: LumpsumDisclosureController;
    private contextualBridgeController: ContextualBridgeController;
    private specializedController: SpecializedCalculatorController | null = null;

    private exportSubsystem: ExportSubsystem;
    private engagementSubsystem: EngagementSubsystem;
    private lifecycleSubsystem: LifecycleSubsystem;
    private ergonomicsSubsystem: ErgonomicsSubsystem;

    private shortcutsInitialized = false;
    private _deferredSettledTimer: ReturnType<typeof setTimeout> | null = null;
    private _chartDragDebounceTimer: ReturnType<typeof setTimeout> | null = null;

    constructor(
        dom: DOMAdapter = new DOMAdapter(),
        formatter: CurrencyFormatter = new CurrencyFormatter(),
        validator: InputValidator = new InputValidator(),
        chartManager?: ChartManager,
        analytics: AnalyticsService = new AnalyticsService()
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.validator = validator;
        this.chartManager = chartManager ?? new ChartManager(this.formatter, this.validator, this.dom);
        this.chartScrubbingController = new ChartScrubbingController(this.dom, this.formatter);
        this.chartManager.setScrubbingController(this.chartScrubbingController);
        this.analytics = analytics;
        this.userHasInteracted = false;
        this.interactionCount = 0;
        this.latestResults = [];
        this.activeGoalMode = 'grow';

        // Strategy instances
        this.strategies = {
            'grow': new GrowStrategy(this.dom, this.validator),
            'target': new TargetCorpusStrategy(this.dom, this.validator),
            'target_corpus': new TargetCorpusStrategy(this.dom, this.validator)
        };

        this.sliderManager = new SliderManager(
            (options?: { isDragging?: boolean }) => {
                this.userHasInteracted = true;
                this.interactionCount++;
                this.triggerCalculation(options);
            },
            this.validator,
            this.dom,
            this.formatter
        );

        this.lumpsumDisclosureController = new LumpsumDisclosureController(
            this.dom,
            () => {
                this.sliderManager.updateFieldValue('lumpsum', 0, true);
                this.userHasInteracted = true;
                this.interactionCount++;
                this.triggerCalculation();
            }
        );

        this.sliderManager.setOnFieldResetToDefault((fieldId, defaultVal) => {
            if (fieldId === 'lumpsum' && defaultVal <= 0) {
                this.lumpsumDisclosureController.collapse(false);
            }
        });

        this.resultsController = new ResultsController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.summaryMetricsController = new SummaryMetricsController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.contextualBridgeController = new ContextualBridgeController(this.dom);

        // Subsystems orchestration
        this.exportSubsystem = new ExportSubsystem({
            dom: this.dom,
            formatter: this.formatter,
            chartManager: this.chartManager,
            analytics: this.analytics,
            getInputs: () => this.getInputs(),
            getExportPayload: () => this.getExportPayload(),
            getLatestResults: () => this.latestResults.length > 0 ? this.latestResults : this.chartManager.getLastResults(),
            getActiveGoalMode: () => this.activeGoalMode,
            getInteractionCount: () => this.interactionCount
        });

        this.engagementSubsystem = new EngagementSubsystem({
            dom: this.dom,
            sliderManager: this.sliderManager,
            formatter: this.formatter,
            analytics: this.analytics,
            triggerCalculation: () => this.triggerCalculation(),
            getInputs: () => this.getInputs(),
            getLatestResults: () => this.latestResults.length > 0 ? this.latestResults : this.chartManager.getLastResults(),
            onSmartNudgeRate: (rate) => this.setSmartNudgeRate(rate)
        });

        this.lifecycleSubsystem = new LifecycleSubsystem({
            dom: this.dom,
            formatter: this.formatter,
            sliderManager: this.sliderManager,
            chartManager: this.chartManager,
            analytics: this.analytics,
            getInputs: () => this.getInputs(),
            triggerCalculation: () => this.triggerCalculation(),
            syncSwpToggleState: () => this.syncSwpToggleState(),
            setGoalMode: (mode) => this.setGoalMode(mode),
            applyRestoredInputs: (inputs) => this.applyRestoredInputs(inputs),
            onSyncLumpsumDisclosure: (val, programmaticReset) => {
                this.lumpsumDisclosureController.syncForValue(val, programmaticReset);
            },
            onSafeSwpAdjusted: (safeAmount) => {
                this.sliderManager.updateFieldValue('swp_withdrawal', safeAmount);
                this.triggerCalculation();
                this.engagementSubsystem.playTick(520, 0.02);
            },
            onLifecycleTransferred: (maturedCorpus, safeMonthlyWithdrawal) => {
                const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = true;
                    this.syncSwpToggleState();
                }
                this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                this.sliderManager.updateFieldValue('swp_withdrawal', safeMonthlyWithdrawal);
                this.setGoalMode('grow');
                const tabSwp = this.dom.getElement<HTMLButtonElement>('tab-swp');
                if (tabSwp) tabSwp.click();
                this.triggerCalculation();
            }
        });

        this.ergonomicsSubsystem = new ErgonomicsSubsystem({
            dom: this.dom,
            formatter: this.formatter,
            sliderManager: this.sliderManager,
            resultsController: this.resultsController,
            analytics: this.analytics,
            getInputs: () => this.getInputs(),
            getLatestResults: () => this.latestResults,
            triggerCalculation: () => this.triggerCalculation(),
            onWhatsAppShare: () => this.exportSubsystem.shareToWhatsApp(this.latestResults)
        });

        this.initGlobalShortcuts();
    }

    /**
     * Reapply a restored set of parameters across form inputs and trigger recalculation.
     */
    applyRestoredInputs(inputs: InvestmentInputs): void {
        applyCoreRestoredInputs(
            this.dom,
            this.sliderManager,
            inputs,
            (val, programmatic) => this.lumpsumDisclosureController.syncForValue(val, programmatic),
            () => this.syncSwpToggleState()
        );
        this.triggerCalculation();
    }

    private initGlobalShortcuts(): void {
        if (typeof window === 'undefined' || this.shortcutsInitialized) return;
        this.shortcutsInitialized = true;
        window.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.altKey && (e.key === 'r' || e.key === 'R')) {
                e.preventDefault();
                this.resetToDefaults();
            }
        });
    }

    /**
     * Reset all calculator inputs and sliders to factory benchmark defaults.
     */
    resetToDefaults(): void {
        const rootAppEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const currentMode = rootAppEl?.dataset?.mode || 'combo';
        this.lifecycleSubsystem.clearDraft(currentMode);
        this.sliderManager.resetAllToDefaults();
        this.lumpsumDisclosureController.syncForValue(0, true);
        this.engagementSubsystem.playTick(280, 0.05);
        this.engagementSubsystem.vibrate([12, 24, 12]);
        this.triggerCalculation();
    }

    /**
     * Gather form input parameters and run validation constraints.
     */
    getInputs(): InvestmentInputs {
        return readCoreInputs(this.dom, this.validator);
    }

    /**
     * Collect active form inputs including specialized mode parameters and currency for CSV/PDF exports.
     */
    getExportPayload(): Record<string, string | number | boolean> {
        return buildExportPayload(this.dom, this.formatter, this.validator);
    }

    /**
     * Publish inputs to calculation event queue.
     */
    triggerCalculation(options?: { isDragging?: boolean }): void {
        const isDragging = options?.isDragging ?? false;
        if (this.specializedController) {
            this.specializedController.calculate();
            return;
        }

        let inputs = this.getInputs();
        this.lifecycleSubsystem.syncBlueprintWithInputs(inputs);

        // Execute Strategy based on goal mode
        const strategy = this.strategies[this.activeGoalMode];
        if (strategy) {
            inputs = strategy.execute(inputs);
        }

        if (this.activeGoalMode === 'target_corpus' || this.activeGoalMode === 'target') {
            this.dom.setValue('sip', inputs.sip);
            this.dom.setValue('sip_range', inputs.sip);
            const targetDisplay = this.dom.getElement('target_calculated_sip_display');
            if (targetDisplay) {
                targetDisplay.textContent = `${this.formatter.format(inputs.sip)} / mo`;
            }
        }

        eventBus.publish('input:changed', { inputs, isDragging });
    }

    /**
     * Adapt text font size inside metrics tiles on screen resize.
     */
    fitSummaryCards(): void {
        this.summaryMetricsController.fitSummaryCards();
    }

    /**
     * Draw years breakdown logs securely using DOM node construction.
     */
    updateTable(data: YearResult[], enableSwp: boolean): void {
        this.resultsController.updateTable(data, enableSwp);
    }

    /**
     * Update summary stats block.
     */
    updateSummaryMetrics(data: YearResult[], isDragging: boolean = false): void {
        this.summaryMetricsController.updateSummaryMetrics(data, isDragging);
    }

    /**
     * Show/Hide SWP withdrawal configurations.
     */
    syncSwpToggleState(): void {
        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = appEl?.dataset?.mode;
        const isSwpMode = (mode === 'swp');
        const isComboMode = (mode === 'combo');
        const toggleEl = this.dom.getElement<HTMLInputElement>('enable_swp');

        let isChecked = false;
        if (isSwpMode || isComboMode) {
            isChecked = true;
            if (toggleEl && toggleEl.type === 'checkbox') {
                toggleEl.checked = true;
            }
        } else if (toggleEl) {
            isChecked = (toggleEl.type === 'checkbox') ? toggleEl.checked : (toggleEl.value === '1');
        }

        if (toggleEl) {
            toggleEl.setAttribute('aria-expanded', isChecked ? 'true' : 'false');
        }

        const fields = this.dom.getElement('swp-fields');
        if (fields) {
            fields.setAttribute('aria-hidden', isChecked ? 'false' : 'true');

            const childInputs = fields.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select');
            childInputs.forEach(input => {
                input.disabled = !isChecked;
            });

            if (isChecked) {
                fields.style.display = 'block';
                fields.style.opacity = '1';
                fields.style.pointerEvents = 'auto';
            } else {
                fields.style.opacity = '0';
                fields.style.pointerEvents = 'none';
                fields.style.display = 'none';
            }
        }

        this.dom.getElements<HTMLElement>('swp-col').forEach(el => {
            el.style.display = isChecked ? '' : 'none';
        });

        this.triggerCalculation();
    }

    /**
     * Update Segmented Control UI styles and configure layout constraints based on goal mode.
     */
    setGoalMode(mode: string): void {
        if (mode === this.activeGoalMode) return;
        this.activeGoalMode = mode;

        const growBtn = this.dom.getElement('goal-grow');
        const targetBtn = this.dom.getElement('goal-target');
        const sipContainer = this.dom.getElement('sip_container');
        const targetCorpusContainer = this.dom.getElement('target_corpus_container');

        const activeClass = ['bg-white', 'text-emerald-600', 'shadow-sm', 'border', 'border-slate-200/20'];
        const inactiveClass = ['text-slate-500', 'hover:text-slate-700'];

        if (mode === 'grow') {
            if (growBtn) {
                growBtn.classList.add(...activeClass);
                growBtn.classList.remove(...inactiveClass);
                growBtn.setAttribute('aria-checked', 'true');
            }
            if (targetBtn) {
                targetBtn.classList.remove(...activeClass);
                targetBtn.classList.add(...inactiveClass);
                targetBtn.setAttribute('aria-checked', 'false');
            }
            if (sipContainer) {
                sipContainer.style.display = 'block';
                sipContainer.style.opacity = '1';
                sipContainer.style.pointerEvents = 'auto';
                sipContainer.removeAttribute('aria-hidden');
                const sipInputs = sipContainer.querySelectorAll<HTMLInputElement>('input');
                sipInputs.forEach(input => { input.disabled = false; });
            }
            if (targetCorpusContainer) {
                targetCorpusContainer.style.display = 'none';
            }
        } else {
            if (targetBtn) {
                targetBtn.classList.add(...activeClass);
                targetBtn.classList.remove(...inactiveClass);
                targetBtn.setAttribute('aria-checked', 'true');
            }
            if (growBtn) {
                growBtn.classList.remove(...activeClass);
                growBtn.classList.add(...inactiveClass);
                growBtn.setAttribute('aria-checked', 'false');
            }
            if (sipContainer) {
                sipContainer.style.display = 'none';
                sipContainer.style.opacity = '0';
                sipContainer.style.pointerEvents = 'none';
                sipContainer.setAttribute('aria-hidden', 'true');
                const sipInputs = sipContainer.querySelectorAll<HTMLInputElement>('input');
                sipInputs.forEach(input => { input.disabled = true; });
            }
            if (targetCorpusContainer) {
                targetCorpusContainer.style.display = 'block';
            }
        }
        this.triggerCalculation();
    }

    setSmartNudgeRate(val: number): void {
        this.dom.setValue('rate', val);
        this.dom.getElement('rate')?.dispatchEvent(new Event('input', { bubbles: true }));
        this.dom.setValue('rate_range', val);
        this.dom.getElement('rate_range')?.dispatchEvent(new Event('input', { bubbles: true }));

        const popover = this.dom.getElement('rate-nudge-popover');
        if (popover) {
            popover.classList.add('hidden');
            this.dom.getElement('rate-nudge-btn')?.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Initialize app lifecycle.
     */
    init(): void {
        const appEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = appEl?.dataset?.mode || 'combo';

        // Bind view switcher (line/donut) and benchmark chips across all calculators
        this.chartManager.initControls();
        bindSavePlanAction({
            dom: this.dom,
            mode,
            lifecycleSubsystem: this.lifecycleSubsystem,
            getInputs: () => this.getInputs(),
            getExportPayload: () => this.getExportPayload(),
            getLatestResults: () => this.latestResults
        });

        if (CalculatorRegistry.isSpecializedMode(mode)) {
            new StepperController(
                this.dom,
                this.validator,
                (fieldId, val) => this.sliderManager.updateFieldValue(fieldId, val),
                this.engagementSubsystem.getAudioController()
            ).init();
            this.initGlobalShortcuts();
            bindPassiveSeoClickListeners(this.analytics);
            bindResizeObserver(() => {
                this.summaryMetricsController.resetBaseFontCache();
                this.fitSummaryCards();
            });
            ModalScrollLockHelper.initGlobalDialogs();

            this.specializedController = new SpecializedCalculatorController(
                mode,
                this.dom,
                this.formatter,
                this.sliderManager,
                this.chartManager,
                this.resultsController,
                this.summaryMetricsController,
                this.analytics,
                (results) => {
                    this.latestResults = results;
                    this.ergonomicsSubsystem.updateResults(results, this.getInputs());
                }
            );
            this.specializedController.init();
            new UrlStateController(
                this.dom,
                () => this.syncSwpToggleState(),
                (goalMode) => this.setGoalMode(goalMode)
            ).init();
            this.exportSubsystem.init();
            this.ergonomicsSubsystem.init();
            return;
        }

        let searchStr = (typeof window !== 'undefined') ? window.location.search : '';
        if (!searchStr && typeof window !== 'undefined' && window.location.hash.includes('?')) {
            searchStr = '?' + window.location.hash.split('?')[1];
        }
        const urlParams = new URLSearchParams(searchStr);
        const urlGoal = urlParams.get('goal') || urlParams.get('goal_mode');

        if (mode === 'target_corpus' || mode === 'target' || urlGoal === 'target_corpus' || urlGoal === 'target') {
            this.activeGoalMode = 'target_corpus';
        }

        this.initGlobalShortcuts();

        bindCoreFormControls({
            dom: this.dom,
            formatter: this.formatter,
            sliderManager: this.sliderManager,
            chartManager: this.chartManager,
            engagementSubsystem: this.engagementSubsystem,
            lifecycleSubsystem: this.lifecycleSubsystem,
            getInputs: () => this.getInputs(),
            getExportPayload: () => this.getExportPayload(),
            getLatestResults: () => this.latestResults,
            getActiveGoalMode: () => this.activeGoalMode,
            setGoalMode: (m) => this.setGoalMode(m),
            syncSwpToggleState: () => this.syncSwpToggleState(),
            triggerCalculation: () => this.triggerCalculation(),
            onResizeFit: () => this.fitSummaryCards(),
        });

        new TabController(this.dom, () => {
            this.syncSwpToggleState();
        }).init();

        new StepperController(
            this.dom,
            this.validator,
            (fieldId, val) => this.sliderManager.updateFieldValue(fieldId, val),
            this.engagementSubsystem.getAudioController()
        ).init();

        this.exportSubsystem.init();
        this.engagementSubsystem.init();
        this.lifecycleSubsystem.init();
        this.ergonomicsSubsystem.init();

        this.summaryMetricsController.initTaxWaterfallModal(() => this.analytics.setTaxWaterfallOpened());
        ModalScrollLockHelper.initGlobalDialogs();

        const resetBtn = this.dom.getElement('calculator-reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetToDefaults();
            });
        }

        bindPassiveSeoClickListeners(this.analytics);
        bindResizeObserver(() => {
            this.summaryMetricsController.resetBaseFontCache();
            this.fitSummaryCards();
        });

        new UrlStateController(
            this.dom,
            () => this.syncSwpToggleState(),
            (goalMode) => this.setGoalMode(goalMode)
        ).init();

        this.lumpsumDisclosureController.init();

        this.initEventBusSubscribers();
        this.initInitialCalculation();
    }

    private initEventBusSubscribers(): void {
        eventBus.subscribe('input:changed', (payload: InvestmentInputs | { inputs: InvestmentInputs; isDragging?: boolean }) => {
            const inputs = (typeof payload === 'object' && payload !== null && 'inputs' in payload) ? payload.inputs : (payload as InvestmentInputs);
            const isDragging = (typeof payload === 'object' && payload !== null && 'isDragging' in payload) ? Boolean(payload.isDragging) : false;

            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;

            if (inputs.lumpsum > 0) {
                this.lumpsumDisclosureController.expand(false);
            }

            // ── FAST ZERO-LATENCY FRAME PIPELINE (Runs on every 60fps drag frame) ──
            // Instantly update summary metrics cards (#total-invested, #est-returns, #total-value)
            this.updateSummaryMetrics(combined, isDragging);

            const sipYears = inputs.years || 0;
            const maturedRow = (inputs.enable_swp && sipYears > 0 && sipYears <= combined.length)
                ? combined[sipYears - 1]
                : combined[combined.length - 1];
            if (maturedRow) {
                const bridgeValEl = this.dom.getElement('bridge-matured-corpus-val');
                if (bridgeValEl) {
                    bridgeValEl.textContent = this.formatter.formatDynamic(maturedRow.combined_total);
                }
            }

            // ── DEFERRED HEAVY PIPELINE (Decoupled to achieve sub-50ms mobile INP) ──
            if (isDragging) {
                // Trailing debounce chart canvas updates during high-frequency pointer moves
                if (this._chartDragDebounceTimer !== null) {
                    clearTimeout(this._chartDragDebounceTimer);
                }
                this._chartDragDebounceTimer = setTimeout(() => {
                    this._chartDragDebounceTimer = null;
                    this.chartManager.updateChartThrottled(combined, inputs.enable_swp);
                }, 80);

                if (this._deferredSettledTimer !== null) {
                    clearTimeout(this._deferredSettledTimer);
                }
                this._deferredSettledTimer = setTimeout(() => {
                    this._deferredSettledTimer = null;
                    if (this._chartDragDebounceTimer !== null) {
                        clearTimeout(this._chartDragDebounceTimer);
                        this._chartDragDebounceTimer = null;
                    }
                    this.chartManager.updateChartThrottled(combined, inputs.enable_swp);
                    this.summaryMetricsController.updateSummaryMetrics(combined, false);
                    this.executeSettledUpdates(inputs, combined);
                }, 100);
            } else {
                if (this._chartDragDebounceTimer !== null) {
                    clearTimeout(this._chartDragDebounceTimer);
                    this._chartDragDebounceTimer = null;
                }
                if (this._deferredSettledTimer !== null) {
                    clearTimeout(this._deferredSettledTimer);
                    this._deferredSettledTimer = null;
                }
                // Schedule low-cost canvas draw on rAF
                this.chartManager.updateChartThrottled(combined, inputs.enable_swp);
                this.summaryMetricsController.updateSummaryMetrics(combined, false);
                this.executeSettledUpdates(inputs, combined);
            }
        });
    }

    /**
     * Executes non-critical DOM table re-renders, state persistence, A11y announcements,
     * and idle layout analytics once slider dragging pauses or settles.
     */
    private executeSettledUpdates(inputs: InvestmentInputs, combined: YearResult[]): void {
        const rootAppEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const currentMode = rootAppEl?.dataset?.mode || 'combo';
        this.lifecycleSubsystem.persistDraft(inputs, currentMode);
        this.lifecycleSubsystem.pushUndoState(inputs);

        this.updateTable(combined, inputs.enable_swp);

        this.engagementSubsystem.updateResults(combined, inputs);
        this.lifecycleSubsystem.updateResults(combined, inputs);
        this.ergonomicsSubsystem.updateResults(combined, inputs);
        this.contextualBridgeController.update(inputs);

        const lastRow = combined[combined.length - 1];
        if (lastRow) {
            // Announce calculation to screen reader with throttle
            A11yAnnouncer.announceCalculation(
                inputs.enable_swp ? 'swp' : (this.activeGoalMode === 'target' ? 'target' : 'sip'),
                inputs.enable_swp ? 'SWP' : 'SIP',
                inputs.enable_swp ? inputs.swp_withdrawal : inputs.sip,
                inputs.years,
                inputs.rate,
                lastRow.combined_total,
                inputs.enable_swp ? (lastRow.cumulative_withdrawals || 0) : (lastRow.combined_total - lastRow.cumulative_invested)
            );
        }

        if (!this.userHasInteracted) return;

        scheduleSettledAnalytics(this.dom, this.analytics, inputs, combined, this.activeGoalMode);
    }

    private initInitialCalculation(): void {
        const runInitCalc = () => {
            let searchStr = (typeof window !== 'undefined') ? window.location.search : '';
            if (!searchStr && typeof window !== 'undefined' && window.location.hash.includes('?')) {
                searchStr = '?' + window.location.hash.split('?')[1];
            }
            const urlParams = new URLSearchParams(searchStr);
            const hasUrlParams = Array.from(urlParams.keys()).length > 0;

            const rootAppEl = this.dom.getElement('calculator-app') || document.querySelector<HTMLElement>('[data-js="calculator-app"]');
            const currentMode = rootAppEl?.dataset?.mode || 'combo';
            const isSwpMode = (currentMode === 'swp');
            const isComboMode = (currentMode === 'combo');

            if (!hasUrlParams) {
                const savedDraft = this.lifecycleSubsystem.loadDraft(currentMode);
                if (savedDraft) {
                    this.applyRestoredInputs(savedDraft);
                    return;
                }
            }

            const urlSwpOn = urlParams.get('swp_on') === '1';
            const initialSwpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');

            if (initialSwpToggle) {
                if (urlSwpOn || isSwpMode || isComboMode) {
                    if (initialSwpToggle.type === 'checkbox') {
                        initialSwpToggle.checked = true;
                    } else {
                        initialSwpToggle.value = '1';
                    }
                }
                this.syncSwpToggleState();
            } else if (isSwpMode || isComboMode) {
                this.syncSwpToggleState();
            }

            let initialInputs = this.getInputs();
            const strategy = this.strategies[this.activeGoalMode];
            if (strategy) {
                initialInputs = strategy.execute(initialInputs);
            }
            if (this.activeGoalMode === 'target_corpus' || this.activeGoalMode === 'target') {
                this.dom.setValue('sip', initialInputs.sip);
                this.dom.setValue('sip_range', initialInputs.sip);
                const targetDisplay = this.dom.getElement('target_calculated_sip_display');
                if (targetDisplay) {
                    targetDisplay.textContent = `${this.formatter.format(initialInputs.sip)} / mo`;
                }
            }

            let swpEnabledOnLoad = initialInputs.enable_swp;
            let existingData: YearResult[] = [];
            try {
                existingData = MathEngine.calculate(initialInputs);
            } catch (e) {
                console.error("Initial JS Calculation Failed:", e);
            }

            // Anti-degeneracy guardrail: If the initial data produced a flat line or <= 2 data points,
            // immediately recover by resetting sliders to curated factory defaults for this mode.
            if (existingData.length <= 2 && currentMode !== 'lumpsum') {
                this.sliderManager.resetAllToDefaults();
                initialInputs = this.getInputs();
                this.lumpsumDisclosureController.syncForValue(initialInputs.lumpsum, initialInputs.lumpsum <= 0);
                if (strategy) {
                    initialInputs = strategy.execute(initialInputs);
                }
                swpEnabledOnLoad = initialInputs.enable_swp;
                try {
                    existingData = MathEngine.calculate(initialInputs);
                } catch (e) {
                    console.error("Factory recovery calculation failed:", e);
                }
            }

            if (existingData.length > 0) {
                this.latestResults = existingData;

                this.updateTable(existingData, swpEnabledOnLoad);
                this.summaryMetricsController.setInstant(true);
                this.updateSummaryMetrics(existingData);
                this.summaryMetricsController.setInstant(false);
                this.ergonomicsSubsystem.updateFloatingHud(existingData);

                this.chartManager.updateChart(existingData, swpEnabledOnLoad);

                const sipYears = initialInputs.years || 0;
                const maturedRow = (initialInputs.enable_swp && sipYears > 0 && sipYears <= existingData.length)
                    ? existingData[sipYears - 1]
                    : existingData[existingData.length - 1];
                if (maturedRow) {
                    const bridgeValEl = this.dom.getElement('bridge-matured-corpus-val');
                    if (bridgeValEl) {
                        bridgeValEl.textContent = this.formatter.formatDynamic(maturedRow.combined_total);
                    }
                }
            }

            this.sliderManager.refreshVisuals();
            this.contextualBridgeController.update(initialInputs);
        };

        if (typeof requestAnimationFrame !== 'undefined') {
            requestAnimationFrame(runInitCalc);
        } else {
            setTimeout(runInitCalc, 0);
        }
    }
}

