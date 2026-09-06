import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { SliderManager } from '../SliderManager.ts';
import { ChartManager } from '../ChartManager.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import { StressTestController } from '../controllers/StressTestController.ts';
import { AssetRebalanceController } from '../controllers/AssetRebalanceController.ts';
import { TaxWaterfallController } from '../controllers/TaxWaterfallController.ts';
import { LongevityGuardianController } from '../controllers/LongevityGuardianController.ts';
import { LifecycleBridgeController } from '../controllers/LifecycleBridgeController.ts';
import { StrategyBlueprintController } from '../controllers/StrategyBlueprintController.ts';
import { ScenarioDiffController } from '../controllers/ScenarioDiffController.ts';
import { SessionStorageController } from '../controllers/SessionStorageController.ts';
import { UndoRedoController } from '../controllers/UndoRedoController.ts';
import { InvestmentInputs, YearResult } from '../../types';

export interface LifecycleSubsystemConfig {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    sliderManager: SliderManager;
    chartManager: ChartManager;
    analytics: AnalyticsService;
    getInputs: () => InvestmentInputs;
    triggerCalculation: () => void;
    syncSwpToggleState: () => void;
    setGoalMode: (mode: string) => void;
    applyRestoredInputs: (inputs: InvestmentInputs) => void;
    onSafeSwpAdjusted?: (safeAmount: number) => void;
    onLifecycleTransferred?: (maturedCorpus: number, safeMonthlyWithdrawal: number) => void;
}

export class LifecycleSubsystem {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private sliderManager: SliderManager;
    private chartManager: ChartManager;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;

    private stressTestController: StressTestController;
    private assetRebalanceController: AssetRebalanceController;
    private taxWaterfallController: TaxWaterfallController;
    private longevityGuardianController: LongevityGuardianController;
    private lifecycleBridgeController: LifecycleBridgeController;
    private strategyBlueprintController: StrategyBlueprintController;
    private scenarioDiffController: ScenarioDiffController;
    private sessionStorageController: SessionStorageController;
    private undoRedoController: UndoRedoController;

    constructor(config: LifecycleSubsystemConfig) {
        this.dom = config.dom;
        this.formatter = config.formatter;
        this.sliderManager = config.sliderManager;
        this.chartManager = config.chartManager;
        this.analytics = config.analytics;
        this.getInputs = config.getInputs;

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

        this.taxWaterfallController = new TaxWaterfallController(
            this.dom,
            this.formatter,
            this.getInputs
        );

        this.longevityGuardianController = new LongevityGuardianController(
            this.dom,
            this.formatter,
            this.getInputs,
            (safeAmount) => {
                if (config.onSafeSwpAdjusted) {
                    config.onSafeSwpAdjusted(safeAmount);
                } else {
                    this.sliderManager.updateFieldValue('swp_withdrawal', safeAmount);
                    config.triggerCalculation();
                }
            }
        );

        this.lifecycleBridgeController = new LifecycleBridgeController(
            this.dom,
            this.formatter,
            this.getInputs,
            (maturedCorpus, safeMonthlyWithdrawal) => {
                if (config.onLifecycleTransferred) {
                    config.onLifecycleTransferred(maturedCorpus, safeMonthlyWithdrawal);
                } else {
                    const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
                    if (swpToggle) {
                        swpToggle.checked = true;
                        config.syncSwpToggleState();
                    }
                    this.sliderManager.updateFieldValue('lumpsum', maturedCorpus);
                    this.sliderManager.updateFieldValue('corpus', maturedCorpus);
                    this.sliderManager.updateFieldValue('swp_withdrawal', safeMonthlyWithdrawal);
                    config.setGoalMode('grow');
                    const tabSwp = this.dom.getElement<HTMLButtonElement>('tab-swp');
                    if (tabSwp) tabSwp.click();
                    config.triggerCalculation();
                }
            }
        );

        this.strategyBlueprintController = new StrategyBlueprintController(
            this.dom,
            this.sliderManager,
            this.analytics,
            () => config.syncSwpToggleState(),
            () => config.triggerCalculation()
        );

        this.scenarioDiffController = new ScenarioDiffController(
            this.dom,
            this.formatter,
            this.getInputs
        );

        this.sessionStorageController = new SessionStorageController();
        this.undoRedoController = new UndoRedoController((target) => {
            config.applyRestoredInputs(target);
        });
    }

    public init(): void {
        this.strategyBlueprintController.init();
        this.scenarioDiffController.init();
        this.stressTestController.init();
        this.assetRebalanceController.init();
    }

    public updateResults(combined: YearResult[], inputs: InvestmentInputs): void {
        this.stressTestController.updateResults(combined, inputs);
        this.assetRebalanceController.updateInputs(inputs, combined);
        this.scenarioDiffController.updateDiff(combined);
        this.longevityGuardianController.update(combined);
        this.taxWaterfallController.update(combined);
        this.lifecycleBridgeController.update(combined);
    }

    public syncBlueprintWithInputs(inputs: InvestmentInputs): void {
        this.strategyBlueprintController.syncWithInputs(inputs);
    }

    public persistDraft(inputs: InvestmentInputs): void {
        this.sessionStorageController.persistDraft(inputs);
    }

    public loadDraft(): InvestmentInputs | null {
        return this.sessionStorageController.loadDraft();
    }

    public clearDraft(): void {
        this.sessionStorageController.clearDraft();
    }

    public pushUndoState(inputs: InvestmentInputs): void {
        this.undoRedoController.pushState(inputs);
    }

    public saveScenarioDiffSnapshot(inputs: InvestmentInputs, results: YearResult[]): void {
        this.scenarioDiffController.setSnapshot(inputs, results);
        this.analytics.setScenarioDiffSaved();
    }
}
