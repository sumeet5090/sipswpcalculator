import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { SliderManager } from '../SliderManager.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import type { ResultsController } from '../controllers/ResultsController.ts';
import { MobileErgonomicDeckController } from '../controllers/MobileErgonomicDeckController.ts';
import { KeyboardViewportController } from '../controllers/KeyboardViewportController.ts';
import { KeyboardNavigationController } from '../controllers/KeyboardNavigationController.ts';
import { CommandPaletteController } from '../controllers/CommandPaletteController.ts';
import { GlossaryController } from '../controllers/GlossaryController.ts';
import { FloatingHudController } from '../controllers/FloatingHudController.ts';
import { StudioTabController } from '../controllers/StudioTabController.ts';
import { InvestmentInputs, YearResult } from '../../types';

export interface ErgonomicsSubsystemConfig {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    sliderManager: SliderManager;
    resultsController: ResultsController;
    analytics: AnalyticsService;
    getInputs: () => InvestmentInputs;
    getLatestResults: () => YearResult[];
    triggerCalculation: () => void;
    onWhatsAppShare: () => void;
}

export class ErgonomicsSubsystem {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private sliderManager: SliderManager;
    private resultsController: ResultsController;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];
    private triggerCalculation: () => void;

    private mobileDeckController: MobileErgonomicDeckController;
    private keyboardViewportController: KeyboardViewportController;
    private keyboardNavController: KeyboardNavigationController;
    private commandPaletteController: CommandPaletteController;
    private glossaryController: GlossaryController;
    private floatingHudController: FloatingHudController;
    private studioTabController: StudioTabController;

    constructor(config: ErgonomicsSubsystemConfig) {
        this.dom = config.dom;
        this.formatter = config.formatter;
        this.sliderManager = config.sliderManager;
        this.resultsController = config.resultsController;
        this.analytics = config.analytics;
        this.getInputs = config.getInputs;
        this.getLatestResults = config.getLatestResults;
        this.triggerCalculation = config.triggerCalculation;

        this.mobileDeckController = new MobileErgonomicDeckController(
            this.dom,
            this.formatter,
            (mode) => {
                const tabBtn = this.dom.getElement<HTMLButtonElement>(`tab-${mode}`);
                if (tabBtn) tabBtn.click();
            },
            config.onWhatsAppShare
        );

        this.keyboardViewportController = new KeyboardViewportController(this.dom, this.formatter);
        this.studioTabController = new StudioTabController(
            this.dom,
            (tabId) => this.analytics.setActiveStudioTab(tabId),
            (denomination) => this.resultsController.setDenominationMode(denomination)
        );

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

        this.glossaryController = new GlossaryController(this.getInputs, this.getLatestResults);
        this.floatingHudController = new FloatingHudController(this.dom, this.formatter);

        this.commandPaletteController = new CommandPaletteController(this.dom, (params) => {
            if (params.sip !== undefined) this.sliderManager.updateFieldValue('sip', params.sip);
            if (params.years !== undefined) this.sliderManager.updateFieldValue('years', params.years);
            if (params.rate !== undefined) this.sliderManager.updateFieldValue('rate', params.rate);
            this.triggerCalculation();
            const sec = this.dom.getElement('calculator-section');
            if (sec) sec.scrollIntoView({ behavior: 'smooth' });
        });
    }

    public init(): void {
        this.studioTabController.init();
        this.glossaryController.init();
        this.commandPaletteController.init();
        this.floatingHudController.init();
        this.keyboardNavController.init();
    }

    public updateResults(combined: YearResult[], inputs: InvestmentInputs): void {
        this.glossaryController.updateArithmeticProof(inputs, combined);
        this.floatingHudController.updateResults(combined);
        this.mobileDeckController.update(combined);
        this.keyboardViewportController.update(combined);
        this.updateStudioTelemetry(inputs, combined);
    }

    public updateFloatingHud(results: YearResult[]): void {
        this.floatingHudController.updateResults(results);
    }

    public updateStudioTelemetry(inputs: InvestmentInputs, results: YearResult[]): void {
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
}
