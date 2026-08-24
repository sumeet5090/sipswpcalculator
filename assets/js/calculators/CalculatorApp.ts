/**
 * CalculatorApp.ts
 * Main frontend application controller class.
 */
import { eventBus } from '../utils/EventBus';
import { MathEngine } from './MathEngine';
import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { ChartManager } from './ChartManager';
import { AnalyticsService } from './AnalyticsLogger';
import { SliderManager } from './SliderManager';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { GrowStrategy } from './strategies/GrowStrategy';
import { TargetCorpusStrategy } from './strategies/TargetCorpusStrategy';
import { CalculatorStrategy } from './strategies/CalculatorStrategy';
import { InvestmentInputs, YearResult } from '../types';
import { PdfExportController } from './controllers/PdfExportController';
import { CsvExportController } from './controllers/CsvExportController';
import { TabController } from './controllers/TabController';
import { StepperController } from './controllers/StepperController';
import { ShareController } from './controllers/ShareController';
import { SmartNudgeController } from './controllers/SmartNudgeController';
import { UrlStateController } from './controllers/UrlStateController';
import { ResultsController } from './controllers/ResultsController';
import { SummaryMetricsController } from './controllers/SummaryMetricsController';
import { GlossaryController } from './controllers/GlossaryController';
import { CommandPaletteController } from './controllers/CommandPaletteController';
import { WealthQuizController } from './controllers/WealthQuizController';
import { ScenarioDiffController } from './controllers/ScenarioDiffController';
import { MilestoneCelebrationController } from './controllers/MilestoneCelebrationController';
import { AudioFeedbackController } from './controllers/AudioFeedbackController';
import { CityBenchmarkController } from './controllers/CityBenchmarkController';
import { StressTestController } from './controllers/StressTestController';
import { AssetRebalanceController } from './controllers/AssetRebalanceController';
import { CardSpotlightController } from './controllers/CardSpotlightController';
import { GoalCommitmentController } from './controllers/GoalCommitmentController';
import { DailyAccrualController } from './controllers/DailyAccrualController';
import { QrShareModalController } from './controllers/QrShareModalController';
import { StudioTabController } from './controllers/StudioTabController';
import { SessionStorageController } from './controllers/SessionStorageController';
import { UndoRedoController } from './controllers/UndoRedoController';
import { FloatingHudController } from './controllers/FloatingHudController';
import { LongevityGuardianController } from './controllers/LongevityGuardianController';
import { TaxWaterfallController } from './controllers/TaxWaterfallController';
import { LifecycleBridgeController } from './controllers/LifecycleBridgeController';
import { MobileErgonomicDeckController } from './controllers/MobileErgonomicDeckController';
import { KeyboardViewportController } from './controllers/KeyboardViewportController';
import { KeyboardNavigationController } from './controllers/KeyboardNavigationController';
import { ChartScrubbingController } from './controllers/ChartScrubbingController';
import { A11yAnnouncer } from './helpers/A11yAnnouncer';
import { ModalScrollLockHelper } from './helpers/ModalScrollLockHelper';

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
    private scenarioDiffController: ScenarioDiffController;
    private celebrationController: MilestoneCelebrationController;
    private audioController: AudioFeedbackController;
    private cityBenchmarkController: CityBenchmarkController;
    private stressTestController: StressTestController;
    private assetRebalanceController: AssetRebalanceController;
    private spotlightController: CardSpotlightController;
    private goalCommitmentController: GoalCommitmentController;
    private dailyAccrualController: DailyAccrualController;
    private qrShareModalController: QrShareModalController;
    private sessionStorageController: SessionStorageController;
    private undoRedoController: UndoRedoController;
    private glossaryController: GlossaryController;
    private floatingHudController: FloatingHudController;
    private longevityGuardianController: LongevityGuardianController;
    private taxWaterfallController: TaxWaterfallController;
    private lifecycleBridgeController: LifecycleBridgeController;
    private mobileDeckController: MobileErgonomicDeckController;
    private keyboardViewportController: KeyboardViewportController;
    private keyboardNavController: KeyboardNavigationController;
    private studioTabController: StudioTabController;

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
            () => {
                this.userHasInteracted = true;
                this.interactionCount++;
                this.triggerCalculation();
            },
            this.validator,
            this.dom,
            this.formatter
        );

        this.resultsController = new ResultsController(
            this.dom,
            this.formatter,
            () => this.getInputs(),
            this.chartManager
        );
        this.chartManager.setResultsController(this.resultsController);

        this.summaryMetricsController = new SummaryMetricsController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.scenarioDiffController = new ScenarioDiffController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.celebrationController = new MilestoneCelebrationController(
            this.dom,
            this.formatter
        );

        this.audioController = new AudioFeedbackController(
            this.dom
        );

        this.cityBenchmarkController = new CityBenchmarkController(
            this.dom,
            this.sliderManager,
            this.formatter,
            () => this.triggerCalculation(),
            (city) => this.analytics.setCityBenchmarkCity(city.slice(0, 64))
        );

        this.stressTestController = new StressTestController(
            this.dom,
            this.formatter,
            this.chartManager,
            (scenario) => this.analytics.setStressTestScenario(scenario.slice(0, 64))
        );

        this.assetRebalanceController = new AssetRebalanceController(
            this.dom,
            this.formatter
        );

        this.spotlightController = new CardSpotlightController();

        this.goalCommitmentController = new GoalCommitmentController(
            this.dom,
            this.formatter,
            () => this.getInputs(),
            () => this.latestResults
        );

        this.dailyAccrualController = new DailyAccrualController(
            this.dom,
            this.formatter
        );

        this.qrShareModalController = new QrShareModalController(
            this.dom,
            () => this.getInputs(),
            () => this.analytics.setQrModalOpened()
        );

        this.sessionStorageController = new SessionStorageController();
        this.undoRedoController = new UndoRedoController((target) => {
            this.applyRestoredInputs(target);
        });
        
        this.glossaryController = new GlossaryController(() => this.getInputs(), () => this.latestResults);
        this.floatingHudController = new FloatingHudController(this.dom, this.formatter);

        this.longevityGuardianController = new LongevityGuardianController(
            this.dom,
            this.formatter,
            () => this.getInputs(),
            (safeAmount) => {
                this.sliderManager.updateFieldValue('swp_withdrawal', safeAmount);
                this.triggerCalculation();
                this.audioController.playTick(520, 0.02);
            }
        );

        this.taxWaterfallController = new TaxWaterfallController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.lifecycleBridgeController = new LifecycleBridgeController(
            this.dom,
            this.formatter,
            () => this.getInputs(),
            (maturedCorpus, safeMonthlyWithdrawal) => {
                const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = true;
                    this.syncSwpToggleState();
                }
                this.sliderManager.updateFieldValue('lumpsum', maturedCorpus);
                this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                this.sliderManager.updateFieldValue('swp_withdrawal', safeMonthlyWithdrawal);
                this.setGoalMode('grow');
                const tabSwp = this.dom.getElement<HTMLButtonElement>('tab-swp');
                if (tabSwp) tabSwp.click();
                this.triggerCalculation();
                this.celebrationController.triggerMicroBurst();
            }
        );

        this.mobileDeckController = new MobileErgonomicDeckController(
            this.dom,
            this.formatter,
            (mode) => {
                const tabBtn = this.dom.getElement<HTMLButtonElement>(`tab-${mode}`);
                if (tabBtn) tabBtn.click();
            },
            () => {
                new ShareController(this.dom, () => this.getInputs()).shareToWhatsApp(this.latestResults);
            }
        );

        this.keyboardViewportController = new KeyboardViewportController(this.dom, this.formatter);
        this.studioTabController = new StudioTabController(this.dom, (tabId) => this.analytics.setActiveStudioTab(tabId));

        this.keyboardNavController = new KeyboardNavigationController(
            this.dom,
            () => {
                const tabSip = this.dom.getElement<HTMLButtonElement>('tab-sip');
                if (tabSip) tabSip.click();
            },
            () => {
                const tabSwp = this.dom.getElement<HTMLButtonElement>('tab-swp');
                if (tabSwp) tabSwp.click();
            }
        );

        this.initGlobalShortcuts();
    }

    /**
     * Reapply a restored set of parameters across form inputs and trigger recalculation.
     */
    applyRestoredInputs(inputs: InvestmentInputs): void {
        if (inputs.sip !== undefined) this.sliderManager.updateFieldValue('sip', inputs.sip);
        if (inputs.years !== undefined) this.sliderManager.updateFieldValue('years', inputs.years);
        if (inputs.rate !== undefined) this.sliderManager.updateFieldValue('rate', inputs.rate);
        if (inputs.stepup !== undefined) this.sliderManager.updateFieldValue('stepup', inputs.stepup);
        if (inputs.inflation !== undefined) this.sliderManager.updateFieldValue('inflation', inputs.inflation);
        if (inputs.lumpsum !== undefined) {
            this.sliderManager.updateFieldValue('lumpsum', inputs.lumpsum);
            this.sliderManager.updateFieldValue('corpus', inputs.lumpsum);
        }
        if (inputs.swp_withdrawal !== undefined) this.sliderManager.updateFieldValue('swp_withdrawal', inputs.swp_withdrawal);
        if (inputs.swp_years !== undefined) this.sliderManager.updateFieldValue('swp_years', inputs.swp_years);
        if (inputs.swp_rate !== undefined) this.sliderManager.updateFieldValue('swp_rate', inputs.swp_rate);
        if (inputs.swp_stepup !== undefined) this.sliderManager.updateFieldValue('swp_stepup', inputs.swp_stepup);

        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle && swpToggle.checked !== inputs.enable_swp) {
            swpToggle.checked = inputs.enable_swp;
            this.syncSwpToggleState();
        }

        this.triggerCalculation();
    }

    private shortcutsInitialized = false;

    private initGlobalShortcuts(): void {
        if (typeof window === 'undefined' || this.shortcutsInitialized) return;
        this.shortcutsInitialized = true;
        window.addEventListener('keydown', (e: KeyboardEvent) => {
            // Alt + R or Option + R: Reset all fields to factory defaults
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
        this.sessionStorageController.clearDraft();
        this.sliderManager.resetAllToDefaults();
        this.audioController.playTick(280, 0.05);
        this.audioController.vibrate([12, 24, 12]);
        this.triggerCalculation();
    }

    /**
     * Gather form input parameters and run validation constraints.
     */
    getInputs(): InvestmentInputs {
        const appEl = this.dom.getElement('calculator-app');
        const mode = appEl?.dataset?.mode ?? 'sip';
        const isSwpMode = (mode === 'swp');

        const lumpsumVal = isSwpMode
            ? this.validator.validate('corpus', this.dom.getValue('corpus') || 0)
            : this.validator.validate('lumpsum', this.dom.getValue('lumpsum') || 0);

        return {
            sip: this.validator.validate('sip', this.dom.getValue('sip') || 0),
            years: this.validator.validate('years', this.dom.getValue('years') || 0),
            rate: this.validator.validate('rate', this.dom.getValue('rate') || 0),
            stepup: this.validator.validate('stepup', this.dom.getValue('stepup') || 0),
            inflation: this.validator.validate('inflation', this.dom.getValue('inflation') || 0),
            lumpsum: lumpsumVal,
            enable_swp: (this.dom.getElement<HTMLInputElement>('enable_swp')?.checked) || isSwpMode,
            swp_withdrawal: this.validator.validate('swp_withdrawal', this.dom.getValue('swp_withdrawal') || 0),
            swp_years: this.validator.validate('swp_years', this.dom.getValue('swp_years') || 0),
            swp_stepup: this.validator.validate('swp_stepup', this.dom.getValue('swp_stepup') || 0),
            swp_rate: this.validator.validate('swp_rate', this.dom.getValue('swp_rate') || 0)
        };
    }

    /**
     * Publish inputs to calculation event queue.
     */
    triggerCalculation(): void {
        let inputs = this.getInputs();

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

        eventBus.publish('input:changed', inputs);
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
    updateSummaryMetrics(data: YearResult[]): void {
        this.summaryMetricsController.updateSummaryMetrics(data);
    }

    /**
     * Show/Hide SWP withdrawal configurations.
     */
    syncSwpToggleState(): void {
        const appEl = this.dom.getElement('calculator-app');
        const isSwpMode = (appEl?.dataset?.mode === 'swp');
        const toggleEl = this.dom.getElement<HTMLInputElement>('enable_swp');

        let isChecked = false;
        if (isSwpMode) {
            isChecked = true;
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
     * Wire 1-Click SIP Matured Corpus -> SWP Bridge transition
     */
    private initLifecycleBridge(): void {
        const bridgeBtn = this.dom.getElement('apply-sip-to-swp-btn');
        if (bridgeBtn) {
            bridgeBtn.addEventListener('click', () => {
                if (this.latestResults.length === 0) return;
                const lastRow = this.latestResults[this.latestResults.length - 1];
                const maturedCorpus = lastRow.combined_total;
                if (maturedCorpus <= 0) return;

                // Transfer matured corpus into SWP starting corpus
                this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                this.sliderManager.updateFieldValue('lumpsum', maturedCorpus);

                // Enable SWP if disabled
                const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = true;
                    this.syncSwpToggleState();
                }

                // Switch tab to SWP
                const swpTab = this.dom.getElement('tab-swp');
                if (swpTab) {
                    swpTab.click();
                }

                this.audioController.playChime();
                this.audioController.vibrate([15, 30, 15]);
                A11yAnnouncer.announce(`Transferred matured SIP corpus of ${this.formatter.formatDynamic(maturedCorpus)} into SWP initial balance.`);
            });
        }
    }

    /**
     * Initialize app lifecycle.
     */
    init(): void {
        const appEl = this.dom.getElement('calculator-app');
        const mode = appEl?.dataset?.mode || 'sip';
        const urlParams = new URLSearchParams(window.location.search);
        const urlGoal = urlParams.get('goal');

        if (mode === 'target_corpus' || mode === 'target' || urlGoal === 'target_corpus' || urlGoal === 'target') {
            this.activeGoalMode = 'target_corpus';
        }

        this.initSliderSync();
        this.initGoalModeControls();
        this.initSwpHandlers();
        this.initToggles();
        this.initLifecycleBridge();
        this.initGlobalShortcuts();

        new TabController(this.dom, () => {
            this.syncSwpToggleState();
        }).init();
        this.studioTabController.init();
        new StepperController(
            this.dom,
            this.validator,
            (fieldId, val) => this.sliderManager.updateFieldValue(fieldId, val),
            this.audioController
        ).init();
        new SmartNudgeController(this.dom, (rate) => this.setSmartNudgeRate(rate)).init();
        new PdfExportController(
            this.dom,
            this.chartManager,
            this.analytics,
            () => this.getInputs(),
            () => this.latestResults,
            () => this.activeGoalMode,
            () => this.interactionCount,
            this.formatter
        ).init();
        new CsvExportController(
            this.dom,
            this.analytics,
            () => this.getInputs()
        ).init();
        new ShareController(this.dom, () => this.getInputs()).init();
        this.glossaryController.init();
        this.audioController.init();
        this.cityBenchmarkController.init();
        this.summaryMetricsController.initTaxWaterfallModal(() => this.analytics.setTaxWaterfallOpened());
        new CommandPaletteController(this.dom, (params) => {
            if (params.sip !== undefined) this.sliderManager.updateFieldValue('sip', params.sip);
            if (params.years !== undefined) this.sliderManager.updateFieldValue('years', params.years);
            if (params.rate !== undefined) this.sliderManager.updateFieldValue('rate', params.rate);
            this.triggerCalculation();
            const sec = this.dom.getElement('calculator-section');
            if (sec) sec.scrollIntoView({ behavior: 'smooth' });
        }).init();
        new WealthQuizController(
            this.dom,
            this.sliderManager,
            () => this.triggerCalculation(),
            () => this.analytics.setGuidedWizardCompleted()
        ).init();
        this.scenarioDiffController.init();
        this.celebrationController.init();
        this.stressTestController.init();
        this.assetRebalanceController.init();
        this.spotlightController.init();
        this.goalCommitmentController.init();
        this.dailyAccrualController.init();
        this.qrShareModalController.init();
        this.floatingHudController.init();
        this.keyboardNavController.init();
        ModalScrollLockHelper.initGlobalDialogs();

        const corridorToggle = this.dom.getElement<HTMLInputElement>('show_historical_corridor');
        if (corridorToggle) {
            corridorToggle.addEventListener('change', () => {
                this.chartManager.setHistoricalCorridor(corridorToggle.checked);
            });
        }

        const stepupBoostBtn = this.dom.getElement<HTMLButtonElement>('apply-10pct-stepup-btn');
        if (stepupBoostBtn) {
            stepupBoostBtn.addEventListener('click', () => {
                this.sliderManager.updateFieldValue('stepup', 10);
                this.triggerCalculation();
                this.celebrationController.triggerMicroBurst();
                A11yAnnouncer.announce('Applied 10% annual salary appraisal step-up');
            });
        }

        const snapshotBtn = this.dom.getElement('snapshot-scenario-btn');
        if (snapshotBtn) {
            snapshotBtn.addEventListener('click', () => {
                const inputs = this.getInputs();
                this.scenarioDiffController.setSnapshot(inputs, this.latestResults);
                this.analytics.setScenarioDiffSaved();
            });
        }
        this.initPersonaBlueprints();
        this.initPassiveSeoClickListeners();
        this.initResizeListeners();
        new UrlStateController(
            this.dom,
            () => this.syncSwpToggleState(),
            (mode) => this.setGoalMode(mode)
        ).init();
        this.initEventBusSubscribers();
        this.initInitialCalculation();
    }

    private initPersonaBlueprints(): void {
        const buttons = document.querySelectorAll<HTMLButtonElement>('.persona-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const persona = btn.dataset.persona || 'blueprint';
                this.analytics.setStrategyStarterUsed(persona);

                const sip = parseFloat(btn.dataset.sip || '0');
                const years = parseFloat(btn.dataset.years || '0');
                const rate = parseFloat(btn.dataset.rate || '0');
                const stepup = parseFloat(btn.dataset.stepup || '0');
                const lumpsum = parseFloat(btn.dataset.lumpsum || '0');
                const enableSwp = btn.dataset.enableSwp === 'true';

                if (btn.dataset.sip !== undefined) this.sliderManager.updateFieldValue('sip', sip);
                if (btn.dataset.years !== undefined) this.sliderManager.updateFieldValue('years', years);
                if (btn.dataset.rate !== undefined) this.sliderManager.updateFieldValue('rate', rate);
                if (btn.dataset.stepup !== undefined) this.sliderManager.updateFieldValue('stepup', stepup);
                if (btn.dataset.lumpsum !== undefined) this.sliderManager.updateFieldValue('lumpsum', lumpsum);
                if (btn.dataset.corpus !== undefined) this.sliderManager.updateFieldValue('corpus', parseFloat(btn.dataset.corpus));

                if (btn.dataset.swp !== undefined) this.sliderManager.updateFieldValue('swp_withdrawal', parseFloat(btn.dataset.swp));
                if (btn.dataset.swpYears !== undefined) this.sliderManager.updateFieldValue('swp_years', parseFloat(btn.dataset.swpYears));
                if (btn.dataset.swpRate !== undefined) this.sliderManager.updateFieldValue('swp_rate', parseFloat(btn.dataset.swpRate));
                if (btn.dataset.swpHike !== undefined) this.sliderManager.updateFieldValue('swp_stepup', parseFloat(btn.dataset.swpHike));

                const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = enableSwp;
                    this.syncSwpToggleState();
                }

                this.triggerCalculation();
            });
        });
    }

    private initPassiveSeoClickListeners(): void {
        if (typeof document === 'undefined') return;

        // FAQ Details toggles
        document.querySelectorAll('details').forEach(details => {
            details.addEventListener('toggle', () => {
                if (details.open) {
                    const summaryText = details.querySelector('summary')?.textContent?.trim() || details.id || 'faq';
                    this.analytics.setFaqExpanded(summaryText.slice(0, 64));
                }
            });
        });

        // Glossary tooltips
        document.querySelectorAll('[data-glossary]').forEach(el => {
            el.addEventListener('click', () => {
                const term = el.getAttribute('data-glossary') || el.textContent?.trim() || 'term';
                this.analytics.setGlossaryClicked(term.slice(0, 64));
            });
        });

        // Floating Discovery HUD shortcuts
        document.querySelectorAll('#floating-discovery-hud a, .hud-nav-link').forEach(el => {
            el.addEventListener('click', () => {
                const target = el.getAttribute('href') || el.id || 'hud';
                this.analytics.setHudShortcutClicked(target.slice(0, 64));
            });
        });

        // City FIRE Benchmark choices
        document.querySelectorAll('.city-choice-btn, [data-city]').forEach(el => {
            el.addEventListener('click', () => {
                const city = (el as HTMLElement).dataset.city || el.textContent?.trim() || 'city';
                this.analytics.setCityBenchmarkCity(city.slice(0, 64));
            });
        });

        // Stress Test Crisis Scenarios
        document.querySelectorAll('.stress-card, [data-scenario]').forEach(el => {
            el.addEventListener('click', () => {
                const sc = (el as HTMLElement).dataset.scenario || el.textContent?.trim() || 'stress';
                this.analytics.setStressTestScenario(sc.slice(0, 64));
            });
        });

        // Related Resources internal links
        document.querySelectorAll('#related-resources a').forEach(el => {
            el.addEventListener('click', () => {
                const href = el.getAttribute('href') || 'related';
                this.analytics.setInternalHubClicked(href.slice(0, 64));
            });
        });
    }

    private initSliderSync(): void {
        this.sliderManager.syncAll({
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
    }

    private initGoalModeControls(): void {
        const growBtn = this.dom.getElement('goal-grow');
        const targetBtn = this.dom.getElement('goal-target');
        if (growBtn) {
            growBtn.addEventListener('click', () => this.setGoalMode('grow'));
        }
        if (targetBtn) {
            targetBtn.addEventListener('click', () => this.setGoalMode('target'));
        }

        const sipInput = this.dom.getElement('sip');
        const sipRange = this.dom.getElement('sip_range');
        const autoSwitchToGrow = () => {
            if (this.activeGoalMode === 'target') {
                this.setGoalMode('grow');
            }
        };
        if (sipInput) sipInput.addEventListener('input', autoSwitchToGrow);
        if (sipRange) sipRange.addEventListener('input', autoSwitchToGrow);
    }

    private initSwpHandlers(): void {
        const swpWithdrawal = this.dom.getElement('swp_withdrawal');
        const swpWithdrawalRange = this.dom.getElement('swp_withdrawal_range');
        const swpYears = this.dom.getElement('swp_years');
        const swpYearsRange = this.dom.getElement('swp_years_range');
        let swpRaf: number | null = null;
        const handleSwpInput = () => {
            if (swpRaf) cancelAnimationFrame(swpRaf);
            swpRaf = requestAnimationFrame(() => {
                const inputs = this.getInputs();
                if (inputs.enable_swp && inputs.swp_withdrawal > 0 && inputs.swp_years > 0) {
                    const reqCorpus = MathEngine.calculateRequiredStartingCorpusForSwp(inputs);
                    if (this.activeGoalMode === 'target') {
                        this.dom.setValue('target_corpus', reqCorpus);

                        const targetRangeEl = this.dom.getElement<HTMLInputElement>('target_corpus_range');
                        if (targetRangeEl) {
                            const defaultMax = parseFloat(targetRangeEl.getAttribute('max') || '50000000');
                            if (reqCorpus > defaultMax) {
                                targetRangeEl.max = String(reqCorpus);
                            } else {
                                targetRangeEl.max = String(defaultMax);
                            }
                            this.dom.setValue('target_corpus_range', reqCorpus);
                        }
                    }
                }
            });
        };

        if (swpWithdrawal) swpWithdrawal.addEventListener('input', handleSwpInput);
        if (swpWithdrawalRange) swpWithdrawalRange.addEventListener('input', handleSwpInput);
        if (swpYears) swpYears.addEventListener('input', handleSwpInput);
        if (swpYearsRange) swpYearsRange.addEventListener('input', handleSwpInput);
    }

    private initToggles(): void {
        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle) {
            swpToggle.addEventListener('change', () => this.syncSwpToggleState());
        }

        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        if (postTaxToggle) {
            postTaxToggle.addEventListener('change', () => {
                const taxCols = this.dom.getElements<HTMLElement>('tax-col');
                taxCols.forEach(el => {
                    el.style.display = postTaxToggle.checked ? '' : 'none';
                });
                this.triggerCalculation();
            });
        }

        const wealthMapToggle = this.dom.getElement('show_wealth_map');
        if (wealthMapToggle) {
            wealthMapToggle.addEventListener('change', () => this.triggerCalculation());
        }
    }

    private initResizeListeners(): void {
        let resizeTimer: ReturnType<typeof setTimeout> | undefined;
        let lastWidth = window.innerWidth;
        window.addEventListener('resize', () => {
            if (window.innerWidth === lastWidth) {
                return; // Ignore height-only resizes from mobile keyboards to prevent CLS
            }
            lastWidth = window.innerWidth;
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.summaryMetricsController.resetBaseFontCache();
                this.fitSummaryCards();
            }, 150);
        });
    }

    private initEventBusSubscribers(): void {
        eventBus.subscribe('input:changed', (inputs: InvestmentInputs) => {
            this.sessionStorageController.persistDraft(inputs);
            this.undoRedoController.pushState(inputs);

            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;
            this.updateTable(combined, inputs.enable_swp);
            this.updateSummaryMetrics(combined);
            this.scenarioDiffController.updateDiff(combined);

            const lastRow = combined[combined.length - 1];
            if (lastRow) {
                this.celebrationController.checkMilestones(lastRow.combined_total, combined, inputs);
                
                // Update accumulation bridge preview
                const bridgeValEl = this.dom.getElement('bridge-matured-corpus-val');
                if (bridgeValEl) {
                    bridgeValEl.textContent = this.formatter.formatDynamic(lastRow.combined_total);
                }

                // Announce calculation to screen reader with 700ms throttle
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

            this.stressTestController.updateResults(combined, inputs);
            this.cityBenchmarkController.updateResults(combined, inputs);
            this.assetRebalanceController.updateInputs(inputs, combined);
            this.dailyAccrualController.updateResults(combined);
            this.glossaryController.updateArithmeticProof(inputs, combined);
            this.floatingHudController.updateResults(combined);
            this.longevityGuardianController.update(combined);
            this.taxWaterfallController.update(combined);
            this.lifecycleBridgeController.update(combined);
            this.mobileDeckController.update(combined);
            this.keyboardViewportController.update(combined);
            this.updateStudioTelemetry(inputs, combined);

            this.chartManager.updateChart(combined, inputs.enable_swp);

            if (!this.userHasInteracted) return;

            const breakdownEl = this.dom.getElement('yearly-breakdown-section') || this.dom.getElement('breakdown-body');
            const tableViewed = breakdownEl
                ? (breakdownEl.getBoundingClientRect().top < this.dom.getViewportHeight() ? 1 : 0)
                : 0;
            const deviceType = (window.innerWidth < 768) ? 'mobile' : 'desktop';

            this.analytics.logInsight(inputs, combined, this.activeGoalMode, {
                table_viewed: tableViewed,
                device_type: deviceType
            });
        });
    }

    /**
     * Updates real-time telemetry metrics in the Multi-Mode Analytical Studio tab bar.
     */
    private updateStudioTelemetry(inputs: InvestmentInputs, results: YearResult[]): void {
        if (!results || results.length === 0) return;
        const lastRow = results[results.length - 1];
        const finalCorpus = lastRow ? lastRow.combined_total : 0;

        // Mumbai benchmark target is ₹2.55 Cr (2,55,00,000)
        const fireCoverage = Math.min(100, (finalCorpus / 25500000) * 100);

        // Milestone checkpoints: 10L, 25L, 50L, 1Cr, 5Cr
        const milestoneCheckpoints = [1000000, 2500000, 5000000, 10000000, 50000000];
        const unlockedCount = milestoneCheckpoints.filter(target => finalCorpus >= target).length;

        // Context scenario caption update
        const captionEl = this.dom.getElement('studio-active-scenario-caption');
        if (captionEl) {
            const formattedCorpus = this.formatter.formatDynamic(finalCorpus);
            const modeLabel = inputs.enable_swp ? 'SWP Cashflow' : 'SIP Wealth Creation';
            captionEl.textContent = `Simulating ${inputs.years} Yrs @ ${inputs.rate}% p.a. • Projecting ${formattedCorpus} (${modeLabel})`;
        }

        this.studioTabController.updateTelemetry({
            years: inputs.years,
            fireCoveragePercent: fireCoverage,
            fireCityName: 'Mumbai',
            milestonesUnlocked: unlockedCount,
            totalMilestones: milestoneCheckpoints.length,
            maxStressDrawdownPercent: 38,
            targetEquitySplit: 80
        });
    }

    private initInitialCalculation(): void {
        const runInitCalc = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const hasUrlParams = Array.from(urlParams.keys()).length > 0;
            if (!hasUrlParams) {
                const savedDraft = this.sessionStorageController.loadDraft();
                if (savedDraft) {
                    this.applyRestoredInputs(savedDraft);
                    return;
                }
            }

            const urlSwpOn = urlParams.get('swp_on') === '1';
            const initialSwpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
            const isSwpMode = (this.dom.getElement('calculator-app')?.dataset?.mode === 'swp');

            if (initialSwpToggle) {
                if (urlSwpOn || isSwpMode) {
                    if (initialSwpToggle.type === 'checkbox') {
                        initialSwpToggle.checked = true;
                    } else {
                        initialSwpToggle.value = '1';
                    }
                }
                this.syncSwpToggleState();
            } else if (isSwpMode) {
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

            const swpEnabledOnLoad = initialInputs.enable_swp;
            let existingData: YearResult[] = [];
            try {
                existingData = MathEngine.calculate(initialInputs);
            } catch (e) {
                console.error("Initial JS Calculation Failed:", e);
            }

            if (existingData.length > 0) {
                this.latestResults = existingData;

                this.updateTable(existingData, swpEnabledOnLoad);
                this.updateSummaryMetrics(existingData);
                this.floatingHudController.updateResults(existingData);

                this.chartManager.updateChart(existingData, swpEnabledOnLoad);
            }

            this.sliderManager.refreshVisuals();
        };

        if (typeof requestAnimationFrame !== 'undefined') {
            requestAnimationFrame(runInitCalc);
        } else {
            setTimeout(runInitCalc, 0);
        }
    }
}
