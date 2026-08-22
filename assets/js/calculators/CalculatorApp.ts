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

export class CalculatorApp {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private chartManager: ChartManager;
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
        this.analytics = analytics;
        this.userHasInteracted = false;
        this.interactionCount = 0;
        this.latestResults = [];
        this.activeGoalMode = 'grow';

        // Strategy instances
        this.strategies = {
            'grow': new GrowStrategy(this.dom, this.validator),
            'target': new TargetCorpusStrategy(this.dom, this.validator)
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

        this.summaryMetricsController = new SummaryMetricsController(
            this.dom,
            this.formatter,
            () => this.getInputs()
        );

        this.scenarioDiffController = new ScenarioDiffController(
            this.dom,
            this.formatter
        );

        this.celebrationController = new MilestoneCelebrationController(
            this.dom
        );

        this.audioController = new AudioFeedbackController(
            this.dom
        );

        this.cityBenchmarkController = new CityBenchmarkController(
            this.dom,
            this.sliderManager,
            this.formatter,
            () => this.triggerCalculation()
        );

        this.stressTestController = new StressTestController(
            this.dom,
            this.formatter
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
            this.dom
        );
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
                sipContainer.style.opacity = '0.65';
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
        const appContainer = this.dom.getElement('calculator-app');
        if (appContainer && appContainer.dataset.mode === 'target_corpus') {
            this.activeGoalMode = 'target';
        }

        this.initSliderSync();
        this.initGoalModeControls();
        this.initSwpHandlers();
        this.initToggles();

        new TabController(this.dom).init();
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
        new GlossaryController().init();
        this.audioController.init();
        this.cityBenchmarkController.init();
        this.summaryMetricsController.initTaxWaterfallModal();
        new CommandPaletteController(this.dom, (params) => {
            if (params.sip !== undefined) this.sliderManager.updateFieldValue('sip', params.sip);
            if (params.years !== undefined) this.sliderManager.updateFieldValue('years', params.years);
            if (params.rate !== undefined) this.sliderManager.updateFieldValue('rate', params.rate);
            this.triggerCalculation();
            const sec = this.dom.getElement('calculator-section');
            if (sec) sec.scrollIntoView({ behavior: 'smooth' });
        }).init();
        new WealthQuizController(this.dom, this.sliderManager, () => this.triggerCalculation()).init();
        this.scenarioDiffController.init();
        this.celebrationController.init();
        this.stressTestController.init();
        this.assetRebalanceController.init();
        this.spotlightController.init();
        this.goalCommitmentController.init();
        this.dailyAccrualController.init();
        this.qrShareModalController.init();
        const snapshotBtn = this.dom.getElement('snapshot-scenario-btn');
        if (snapshotBtn) {
            snapshotBtn.addEventListener('click', () => {
                const inputs = this.getInputs();
                this.scenarioDiffController.setSnapshot(inputs, this.latestResults);
            });
        }
        this.initPersonaBlueprints();
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
            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;
            this.updateTable(combined, inputs.enable_swp);
            this.updateSummaryMetrics(combined);
            this.scenarioDiffController.updateDiff(combined);

            const lastRow = combined[combined.length - 1];
            if (lastRow) {
                this.celebrationController.checkMilestones(lastRow.combined_total);
            }

            this.stressTestController.updateResults(combined);
            this.assetRebalanceController.updateInputs(inputs);
            this.dailyAccrualController.updateResults(combined);

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

    private initInitialCalculation(): void {
        const runInitCalc = () => {
            const urlSwpOn = (new URLSearchParams(window.location.search)).get('swp_on') === '1';
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
