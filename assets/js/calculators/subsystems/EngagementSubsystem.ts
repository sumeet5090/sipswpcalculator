import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { SliderManager } from '../SliderManager.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import { WealthQuizController } from '../controllers/WealthQuizController.ts';
import { MilestoneCelebrationController } from '../controllers/MilestoneCelebrationController.ts';
import { AudioFeedbackController } from '../controllers/AudioFeedbackController.ts';
import { CityBenchmarkController } from '../controllers/CityBenchmarkController.ts';
import { DailyAccrualController } from '../controllers/DailyAccrualController.ts';
import { SmartNudgeController } from '../controllers/SmartNudgeController.ts';
import { CardSpotlightController } from '../controllers/CardSpotlightController.ts';
import { GoalCommitmentController } from '../controllers/GoalCommitmentController.ts';
import { InvestmentInputs, YearResult } from '../../types';

export interface EngagementSubsystemConfig {
    dom: DOMAdapter;
    sliderManager: SliderManager;
    formatter: CurrencyFormatter;
    analytics: AnalyticsService;
    triggerCalculation: () => void;
    getInputs: () => InvestmentInputs;
    getLatestResults: () => YearResult[];
    onSmartNudgeRate: (rate: number) => void;
}

export class EngagementSubsystem {
    private dom: DOMAdapter;
    private sliderManager: SliderManager;
    private formatter: CurrencyFormatter;
    private analytics: AnalyticsService;
    private triggerCalculation: () => void;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];

    private quizController: WealthQuizController;
    private celebrationController: MilestoneCelebrationController;
    private audioController: AudioFeedbackController;
    private cityBenchmarkController: CityBenchmarkController;
    private dailyAccrualController: DailyAccrualController;
    private smartNudgeController: SmartNudgeController;
    private spotlightController: CardSpotlightController;
    private goalCommitmentController: GoalCommitmentController;

    constructor(config: EngagementSubsystemConfig) {
        this.dom = config.dom;
        this.sliderManager = config.sliderManager;
        this.formatter = config.formatter;
        this.analytics = config.analytics;
        this.triggerCalculation = config.triggerCalculation;
        this.getInputs = config.getInputs;
        this.getLatestResults = config.getLatestResults;

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
            this.triggerCalculation,
            (city) => this.analytics.setCityBenchmarkCity(city.slice(0, 64))
        );

        this.dailyAccrualController = new DailyAccrualController(
            this.dom,
            this.formatter
        );

        this.smartNudgeController = new SmartNudgeController(
            this.dom,
            config.onSmartNudgeRate
        );

        this.spotlightController = new CardSpotlightController();

        this.goalCommitmentController = new GoalCommitmentController(
            this.dom,
            this.formatter,
            this.getInputs,
            this.getLatestResults
        );

        this.quizController = new WealthQuizController(
            this.dom,
            this.sliderManager,
            this.triggerCalculation,
            () => this.analytics.setGuidedWizardCompleted()
        );
    }

    public init(): void {
        this.audioController.init();
        this.cityBenchmarkController.init();
        this.dailyAccrualController.init();
        this.smartNudgeController.init();
        this.spotlightController.init();
        this.goalCommitmentController.init();
        this.quizController.init();
        this.celebrationController.init();
    }

    public updateResults(combined: YearResult[], inputs: InvestmentInputs): void {
        const lastRow = combined[combined.length - 1];
        if (lastRow) {
            this.celebrationController.checkMilestones(lastRow.combined_total, combined, inputs);
        }
        this.cityBenchmarkController.updateResults(combined, inputs);
        this.dailyAccrualController.updateResults(combined);
    }

    public triggerMicroBurst(): void {
        this.celebrationController.triggerMicroBurst();
    }

    public playTick(frequency: number = 440, durationSec: number = 0.03): void {
        this.audioController.playTick(frequency, durationSec);
    }

    public playChime(): void {
        this.audioController.playChime();
    }

    public vibrate(pattern: number | number[] = 10): void {
        this.audioController.vibrate(pattern);
    }

    public getAudioController(): AudioFeedbackController {
        return this.audioController;
    }
}
