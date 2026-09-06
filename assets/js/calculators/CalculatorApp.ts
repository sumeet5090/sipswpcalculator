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
import { A11yAnnouncer } from './helpers/A11yAnnouncer.ts';
import { ModalScrollLockHelper } from './helpers/ModalScrollLockHelper.ts';
import { SpecializedCalculatorController } from './controllers/SpecializedCalculatorController.ts';
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
    private specializedController: SpecializedCalculatorController | null = null;

    private exportSubsystem: ExportSubsystem;
    private engagementSubsystem: EngagementSubsystem;
    private lifecycleSubsystem: LifecycleSubsystem;
    private ergonomicsSubsystem: ErgonomicsSubsystem;

    private shortcutsInitialized = false;

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

        // Subsystems orchestration
        this.exportSubsystem = new ExportSubsystem({
            dom: this.dom,
            formatter: this.formatter,
            chartManager: this.chartManager,
            analytics: this.analytics,
            getInputs: () => this.getInputs(),
            getLatestResults: () => this.latestResults,
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
            getLatestResults: () => this.latestResults,
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
                this.sliderManager.updateFieldValue('lumpsum', maturedCorpus);
                this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                this.sliderManager.updateFieldValue('swp_withdrawal', safeMonthlyWithdrawal);
                this.setGoalMode('grow');
                const tabSwp = this.dom.getElement<HTMLButtonElement>('tab-swp');
                if (tabSwp) tabSwp.click();
                this.triggerCalculation();
                this.engagementSubsystem.triggerMicroBurst();
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
        if (inputs.sip !== undefined) this.sliderManager.updateFieldValue('sip', inputs.sip, true);
        if (inputs.years !== undefined) this.sliderManager.updateFieldValue('years', inputs.years, true);
        if (inputs.rate !== undefined) this.sliderManager.updateFieldValue('rate', inputs.rate, true);
        if (inputs.stepup !== undefined) this.sliderManager.updateFieldValue('stepup', inputs.stepup, true);
        if (inputs.inflation !== undefined) this.sliderManager.updateFieldValue('inflation', inputs.inflation, true);
        if (inputs.lumpsum !== undefined) {
            this.sliderManager.updateFieldValue('lumpsum', inputs.lumpsum, true);
            this.sliderManager.updateFieldValue('corpus', inputs.lumpsum, true);
        }
        if (inputs.swp_withdrawal !== undefined) this.sliderManager.updateFieldValue('swp_withdrawal', inputs.swp_withdrawal, true);
        if (inputs.swp_years !== undefined) this.sliderManager.updateFieldValue('swp_years', inputs.swp_years, true);
        if (inputs.swp_rate !== undefined) this.sliderManager.updateFieldValue('swp_rate', inputs.swp_rate, true);
        if (inputs.swp_stepup !== undefined) this.sliderManager.updateFieldValue('swp_stepup', inputs.swp_stepup, true);

        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle && swpToggle.checked !== inputs.enable_swp) {
            swpToggle.checked = inputs.enable_swp;
            this.syncSwpToggleState();
        }

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
        this.lifecycleSubsystem.clearDraft();
        this.sliderManager.resetAllToDefaults();
        this.engagementSubsystem.playTick(280, 0.05);
        this.engagementSubsystem.vibrate([12, 24, 12]);
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

                this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                this.sliderManager.updateFieldValue('lumpsum', maturedCorpus);

                const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = true;
                    this.syncSwpToggleState();
                }

                const swpTab = this.dom.getElement('tab-swp');
                if (swpTab) {
                    swpTab.click();
                }

                this.engagementSubsystem.playChime();
                this.engagementSubsystem.vibrate([15, 30, 15]);
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
        const specializedModes = ['compound_interest', 'cagr', 'emi', 'inflation', 'ppf', 'fd'];

        if (specializedModes.includes(mode)) {
            new StepperController(
                this.dom,
                this.validator,
                (fieldId, val) => this.sliderManager.updateFieldValue(fieldId, val),
                this.engagementSubsystem.getAudioController()
            ).init();
            this.initGlobalShortcuts();
            this.initPassiveSeoClickListeners();
            this.initResizeListeners();
            ModalScrollLockHelper.initGlobalDialogs();

            this.specializedController = new SpecializedCalculatorController(
                mode,
                this.dom,
                this.formatter,
                this.sliderManager,
                this.chartManager,
                this.resultsController,
                this.summaryMetricsController
            );
            this.specializedController.init();
            return;
        }

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
                this.engagementSubsystem.triggerMicroBurst();
                A11yAnnouncer.announce('Applied 10% annual salary appraisal step-up');
            });
        }

        const snapshotBtn = this.dom.getElement('snapshot-scenario-btn');
        if (snapshotBtn) {
            snapshotBtn.addEventListener('click', () => {
                const inputs = this.getInputs();
                this.lifecycleSubsystem.saveScenarioDiffSnapshot(inputs, this.latestResults);
            });
        }

        this.initPassiveSeoClickListeners();
        this.initResizeListeners();

        new UrlStateController(
            this.dom,
            () => this.syncSwpToggleState(),
            (goalMode) => this.setGoalMode(goalMode)
        ).init();

        this.initEventBusSubscribers();
        this.initInitialCalculation();
    }

    private initPassiveSeoClickListeners(): void {
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
            this.lifecycleSubsystem.persistDraft(inputs);
            this.lifecycleSubsystem.pushUndoState(inputs);

            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;
            this.updateTable(combined, inputs.enable_swp);
            this.updateSummaryMetrics(combined);

            this.engagementSubsystem.updateResults(combined, inputs);
            this.lifecycleSubsystem.updateResults(combined, inputs);
            this.ergonomicsSubsystem.updateResults(combined, inputs);

            const lastRow = combined[combined.length - 1];
            if (lastRow) {
                const bridgeValEl = this.dom.getElement('bridge-matured-corpus-val');
                if (bridgeValEl) {
                    bridgeValEl.textContent = this.formatter.formatDynamic(lastRow.combined_total);
                }

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
            const urlParams = new URLSearchParams(window.location.search);
            const hasUrlParams = Array.from(urlParams.keys()).length > 0;
            if (!hasUrlParams) {
                const savedDraft = this.lifecycleSubsystem.loadDraft();
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
                this.ergonomicsSubsystem.updateFloatingHud(existingData);

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

