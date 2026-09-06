import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { SliderManager } from '../SliderManager.ts';
import type { ChartManager } from '../ChartManager.ts';
import type { ResultsController } from './ResultsController.ts';
import type { SummaryMetricsController } from './SummaryMetricsController.ts';
import { OdometerController } from './OdometerController.ts';
import type { ISpecializedDriver, DriverContext } from '../drivers/index.ts';
import {
    CompoundInterestDriver,
    CagrDriver,
    EmiDriver,
    InflationDriver,
    PpfDriver,
    FdDriver
} from '../drivers/index.ts';

export class SpecializedCalculatorController {
    private mode: string;
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private sliderManager: SliderManager;
    private chartManager: ChartManager;
    private resultsController: ResultsController;
    private summaryMetricsController: SummaryMetricsController;
    private odometer: OdometerController;
    private driver: ISpecializedDriver | null = null;

    private static readonly DRIVER_REGISTRY: Record<string, () => ISpecializedDriver> = {
        compound_interest: () => new CompoundInterestDriver(),
        cagr: () => new CagrDriver(),
        emi: () => new EmiDriver(),
        inflation: () => new InflationDriver(),
        ppf: () => new PpfDriver(),
        fd: () => new FdDriver()
    };

    constructor(
        mode: string,
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        sliderManager: SliderManager,
        chartManager: ChartManager,
        resultsController: ResultsController,
        summaryMetricsController: SummaryMetricsController
    ) {
        this.mode = mode;
        this.dom = dom;
        this.formatter = formatter;
        this.sliderManager = sliderManager;
        this.chartManager = chartManager;
        this.resultsController = resultsController;
        this.summaryMetricsController = summaryMetricsController;
        this.odometer = new OdometerController(dom, formatter);

        const driverFactory = SpecializedCalculatorController.DRIVER_REGISTRY[this.mode];
        if (driverFactory) {
            this.driver = driverFactory();
        }
    }

    public init(): void {
        this.sliderManager.setTriggerFn(() => this.calculate());

        if (this.driver) {
            const ctx = this.createDriverContext();
            this.adjustSummaryCardLayout();
            this.driver.bindSliders(ctx);
            this.driver.bindAdditionalControls?.(ctx);
            this.driver.setupCardLabels(ctx);
            this.bindFormListeners();
            this.calculate();
        }
    }

    private adjustSummaryCardLayout(): void {
        const summaryGrid = this.dom.getElement('summary-cards-grid');
        const cardWithdrawn = this.dom.getElement('card-withdrawn');
        if (summaryGrid && cardWithdrawn) {
            summaryGrid.className = 'grid grid-cols-2 sm:grid-cols-3 gap-3 transition-all duration-300';
            cardWithdrawn.classList.add('hidden');
        }
    }

    private bindFormListeners(): void {
        const form = this.dom.getElement('calculator-form');
        if (form) {
            form.addEventListener('input', () => this.calculate());
            form.addEventListener('change', () => this.calculate());
        }
    }

    public calculate(): void {
        if (!this.driver) {
            return;
        }
        const ctx = this.createDriverContext();
        this.driver.calculate(ctx);
    }

    private createDriverContext(): DriverContext {
        return {
            dom: this.dom,
            formatter: this.formatter,
            sliderManager: this.sliderManager,
            chartManager: this.chartManager,
            resultsController: this.resultsController,
            summaryMetricsController: this.summaryMetricsController,
            odometer: this.odometer,
            recalculate: () => this.calculate()
        };
    }
}
