import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { CurrencyFormatter } from '../CurrencyHelper.ts';
import { SliderManager } from '../SliderManager.ts';
import type { ChartManager } from '../ChartManager.ts';
import type { ResultsController } from '../controllers/ResultsController.ts';
import type { SummaryMetricsController } from '../controllers/SummaryMetricsController.ts';
import type { OdometerController } from '../controllers/OdometerController.ts';

export interface DriverContext {
    dom: DOMAdapter;
    formatter: CurrencyFormatter;
    sliderManager: SliderManager;
    chartManager: ChartManager;
    resultsController: ResultsController;
    summaryMetricsController: SummaryMetricsController;
    odometer: OdometerController;
    recalculate: () => void;
}

export interface ISpecializedDriver {
    readonly mode: string;
    bindSliders(context: DriverContext): void;
    bindAdditionalControls?(context: DriverContext): void;
    setupCardLabels(context: DriverContext): void;
    calculate(context: DriverContext): void;
}
