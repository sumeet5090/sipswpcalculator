import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { ChartManager } from '../ChartManager.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import { PdfExportController } from '../controllers/PdfExportController.ts';
import { CsvExportController } from '../controllers/CsvExportController.ts';
import { ShareController } from '../controllers/ShareController.ts';
import { QrShareModalController } from '../controllers/QrShareModalController.ts';
import { InvestmentInputs, YearResult } from '../../types';

export interface ExportSubsystemConfig {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    chartManager: ChartManager;
    analytics: AnalyticsService;
    getInputs: () => InvestmentInputs;
    getLatestResults: () => YearResult[];
    getActiveGoalMode: () => string;
    getInteractionCount: () => number;
}

export class ExportSubsystem {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private chartManager: ChartManager;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];
    private getActiveGoalMode: () => string;
    private getInteractionCount: () => number;

    private pdfExportController: PdfExportController;
    private csvExportController: CsvExportController;
    private shareController: ShareController;
    private qrShareModalController: QrShareModalController;

    constructor(config: ExportSubsystemConfig) {
        this.dom = config.dom;
        this.formatter = config.formatter;
        this.chartManager = config.chartManager;
        this.analytics = config.analytics;
        this.getInputs = config.getInputs;
        this.getLatestResults = config.getLatestResults;
        this.getActiveGoalMode = config.getActiveGoalMode;
        this.getInteractionCount = config.getInteractionCount;

        this.pdfExportController = new PdfExportController(
            this.dom,
            this.chartManager,
            this.analytics,
            this.getInputs,
            this.getLatestResults,
            this.getActiveGoalMode,
            this.getInteractionCount,
            this.formatter
        );

        this.csvExportController = new CsvExportController(
            this.dom,
            this.analytics,
            this.getInputs
        );

        this.shareController = new ShareController(
            this.dom,
            this.getInputs,
            this.getLatestResults
        );

        this.qrShareModalController = new QrShareModalController(
            this.dom,
            this.getInputs,
            () => this.analytics.setQrModalOpened()
        );
    }

    public init(): void {
        this.pdfExportController.init();
        this.csvExportController.init();
        this.shareController.init();
        this.qrShareModalController.init();
    }

    public shareToWhatsApp(results: YearResult[]): void {
        this.shareController.shareToWhatsApp(results);
    }
}
