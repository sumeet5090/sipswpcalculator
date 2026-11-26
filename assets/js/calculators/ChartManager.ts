import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { YearResult } from '../types';
import { THEME_COLORS, THEME_FONTS } from './constants/ThemeTokens.ts';
import { eventBus } from '../utils/EventBus';
import { ChartGradientFactory } from './chart/ChartGradientFactory';
import { ChartMilestoneCalculator, Milestone } from './chart/ChartMilestoneCalculator';
import { ChartDatasetBuilder } from './chart/ChartDatasetBuilder';
import { createChartPlugins } from './chart/ChartPlugins';
import { ChartTelemetryController } from './chart/ChartTelemetryController';
import {
    computeDonutViewData,
    buildDonutChartConfig,
    buildLineChartConfig
} from './chart/ChartConfigFactory';
import type { ChartScrubbingController } from './controllers/ChartScrubbingController';
import type { Chart, ChartConfiguration, Plugin } from 'chart.js';

/**
 * ChartManager.ts
 * Coordinates Chart.js lifecycle, dataset state transitions, High-DPI scaling, and responsive rendering.
 * Strictly adheres to SOLID, DRY, and POLA principles.
 */
export class ChartManager {
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private dom: DOMAdapter;
    private chartInstance: Chart<'line'> | null = null;
    private currentMilestones: Milestone[] = [];
    private chartModulePromise: Promise<typeof Chart> | null = null;
    private showHistoricalCorridor: boolean = false;
    private scrubbingController: ChartScrubbingController | null = null;

    private activeBenchmark: 'none' | 'nifty' | 'gold' | 'fd' = 'none';
    private activeViewType: 'line' | 'donut' = 'line';
    private currentChartType: 'line' | 'doughnut' | null = null;
    private lastResults: YearResult[] = [];
    private lastEnableSwp: boolean = true;
    private shockOverlayData: { label: string; data: number[] } | null = null;
    private shockOverlayCrashIndex: number | null = null;
    private activeDonutScrubYear: number | null = null;
    private lastMode: string = '';

    private rafId: number | null = null;
    private renderQueueId: number | null = null;
    private controlsInitialized: boolean = false;

    // Sub-modules
    private gradientFactory: ChartGradientFactory;
    private milestoneCalculator: ChartMilestoneCalculator;
    private datasetBuilder: ChartDatasetBuilder;
    private telemetryController: ChartTelemetryController;
    private plugins: ReturnType<typeof createChartPlugins>;
    private unsubscribeEvents: (() => void)[] = [];

    constructor(
        formatter: CurrencyFormatter,
        validator: InputValidator = new InputValidator(),
        dom: DOMAdapter = new DOMAdapter()
    ) {
        this.formatter = formatter;
        this.validator = validator;
        this.dom = dom;

        this.gradientFactory = new ChartGradientFactory();
        this.milestoneCalculator = new ChartMilestoneCalculator(this.formatter, this.validator, this.dom);
        this.telemetryController = new ChartTelemetryController(this.dom, this.formatter);
        this.datasetBuilder = new ChartDatasetBuilder({
            dom: this.dom,
            getActiveBenchmark: () => this.activeBenchmark,
            getShowHistoricalCorridor: () => this.showHistoricalCorridor,
            getShockOverlayData: () => this.shockOverlayData,
            getShockOverlayCrashIndex: () => this.shockOverlayCrashIndex,
        });

        this.plugins = createChartPlugins({
            getActiveBenchmark: () => this.activeBenchmark,
            getLastResults: () => this.lastResults,
            getActiveDonutScrubYear: () => this.activeDonutScrubYear,
            getCurrentMilestones: () => this.currentMilestones,
            computeBenchmarkCurve: (res, rate) => this.datasetBuilder.computeBenchmarkCurve(res, rate),
            formatter: this.formatter,
        });

        this.initEventSubscriptions();
    }

    /**
     * Returns the most recent calculation results schedule.
     */
    public getLastResults(): YearResult[] {
        return this.lastResults;
    }

    /**
     * Subscribes to global EventBus topics to achieve loose coupling.
     */
    private initEventSubscriptions(): void {
        const unsubHighlight = eventBus.subscribe<{ index: number }>('chart:highlight', data => {
            if (typeof data?.index === 'number') {
                this.highlightYear(data.index);
            }
        });

        const unsubClear = eventBus.subscribe('chart:clearHighlight', () => {
            this.clearHighlight();
        });

        const unsubScrub = eventBus.subscribe<{ index: number; row?: YearResult }>('chart:scrub', data => {
            if (typeof data?.index === 'number') {
                if (this.activeViewType === 'donut') {
                    this.updateDonutForYear(data.index);
                } else {
                    this.highlightYear(data.index);
                    this.announceCurrentPoint(data.index);
                }
            }
        });

        this.unsubscribeEvents.push(unsubHighlight, unsubClear, unsubScrub);
    }

    /**
     * Injects the optional scrubbing controller for legacy/direct sync.
     */
    public setScrubbingController(scrubbingController: ChartScrubbingController): void {
        this.scrubbingController = scrubbingController;
        this.scrubbingController.setOnScrubCallback((index: number) => {
            if (this.activeViewType === 'donut') {
                this.updateDonutForYear(index);
            } else {
                this.highlightYear(index);
                this.announceCurrentPoint(index);
            }
            eventBus.publish('table:highlight', { index, scrollIntoView: false });
        });
    }

    /**
     * Dynamically loads Chart.js as an isolated vendor chunk via Vite.
     */
    private async loadChartModule(): Promise<typeof Chart> {
        if (this.chartModulePromise) return this.chartModulePromise;

        this.chartModulePromise = (async () => {
            const module = await import('chart.js/auto');
            return module.Chart || module.default;
        })();

        return this.chartModulePromise;
    }

    /**
     * Formats axis tick numbers cleanly into Indian (Cr/L/k) or Western (B/M/k) scales.
     */
    formatAxisTick(value: number): string {
        return this.telemetryController.formatAxisTick(value);
    }

    /**
     * Updates active lens text indicator in the header dock.
     */
    public updateActiveLensIndicator(): void {
        this.telemetryController.updateActiveLensIndicator();
    }

    /**
     * Plot or clear historical market shock trajectory overlay.
     */
    setShockOverlay(overlay: { label: string; data: number[]; crashIndex: number } | null): void {
        this.shockOverlayData = overlay ? { label: overlay.label, data: overlay.data } : null;
        this.shockOverlayCrashIndex = overlay ? overlay.crashIndex : null;
        if (this.lastResults.length > 0) {
            this.updateChart(this.lastResults, this.lastEnableSwp);
        }
    }

    /**
     * Toggle Historical Volatility Corridor (10th-90th percentile Nifty rolling band).
     */
    setHistoricalCorridor(show: boolean): void {
        this.showHistoricalCorridor = show;
        this.updateActiveLensIndicator();
        if (this.lastResults.length > 0) {
            this.updateChart(this.lastResults, this.lastEnableSwp);
        }
    }

    /**
     * Switch active historical benchmark comparison (Nifty 50, Gold, FD, or None).
     */
    setBenchmark(benchmark: 'none' | 'nifty' | 'gold' | 'fd'): void {
        this.activeBenchmark = benchmark;
        const chips = this.dom.getElements<HTMLButtonElement>('.benchmark-chip');
        chips.forEach(c => {
            const isMatch = c.dataset.benchmark === benchmark;
            const rateSpan = c.querySelector<HTMLSpanElement>('.benchmark-rate');
            if (isMatch) {
                c.classList.add('is-active', 'bg-white', 'text-growth-emphasis', 'shadow-flat', 'border-slate-200/80', 'font-bold');
                c.classList.remove(
                    'bg-transparent',
                    'text-slate-600',
                    'border-transparent',
                    'hover:text-slate-900',
                    'hover:bg-white/60',
                    'font-semibold',
                    'bg-slate-50',
                    'bg-emerald-600',
                    'text-white',
                    'border-emerald-600'
                );
                if (rateSpan) {
                    rateSpan.classList.add('text-growth', 'font-semibold');
                    rateSpan.classList.remove('text-slate-500', 'font-normal');
                }
            } else {
                c.classList.remove(
                    'is-active',
                    'bg-white',
                    'text-growth-emphasis',
                    'shadow-flat',
                    'border-slate-200/80',
                    'font-bold',
                    'bg-emerald-600',
                    'text-white',
                    'border-emerald-600'
                );
                c.classList.add('bg-transparent', 'text-slate-600', 'border-transparent', 'hover:text-slate-900', 'hover:bg-white/60', 'font-semibold');
                if (rateSpan) {
                    rateSpan.classList.remove('text-growth', 'font-semibold');
                    rateSpan.classList.add('text-slate-500', 'font-normal');
                }
            }
        });

        if (this.lastResults.length > 0) {
            this.updateChart(this.lastResults, this.lastEnableSwp);
        }
    }

    /**
     * Update persistent Zero-CLS Heads-Up Display (HUD) telemetry console.
     */
    public updateInspectionRibbon(row: YearResult): void {
        this.telemetryController.updateInspectionRibbon(row, this.lastResults, this.scrubbingController);
    }

    /**
     * Announce current data point for assistive technologies and update HUD.
     */
    private announceCurrentPoint(index: number): void {
        this.telemetryController.announceCurrentPoint(index, this.lastResults, this.scrubbingController);
    }

    /**
     * Switch between Line (Growth Curve) and Donut (Asset Allocation) views.
     */
    setViewType(type: 'line' | 'donut'): void {
        if (this.activeViewType === type) return;
        this.activeViewType = type;

        const lineBtn = this.dom.getElement('chart-view-line');
        const donutBtn = this.dom.getElement('chart-view-donut');
        if (lineBtn && donutBtn) {
            if (type === 'line') {
                lineBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                lineBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                lineBtn.setAttribute('aria-selected', 'true');
                donutBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                donutBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                donutBtn.setAttribute('aria-selected', 'false');
            } else {
                donutBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                donutBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                donutBtn.setAttribute('aria-selected', 'true');
                lineBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                lineBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                lineBtn.setAttribute('aria-selected', 'false');
            }
        }

        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }

        if (this.lastResults.length > 0) {
            this.updateChart(this.lastResults, this.lastEnableSwp);
        }
    }

    /**
     * Morphs donut proportions for any selected year with zero chart re-instantiation.
     */
    public updateDonutForYear(yearIndex: number): void {
        if (!this.chartInstance || this.currentChartType !== 'doughnut') return;
        const row = this.lastResults[yearIndex];
        if (!row) return;

        const appMode = this.dom.getElement('calculator-app')?.dataset?.mode || '';
        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const invested = row.cumulative_invested || 0;
        const finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
        const withdrawals = row.cumulative_withdrawals || 0;
        const gains = Math.max(0, (finalCorpus + withdrawals) - invested);

        const dataset = this.chartInstance.data.datasets[0];
        if (dataset) {
            if (appMode === 'emi') {
                const totalInterest = this.lastResults.reduce((acc, r) => acc + (r.interest ?? 0), 0);
                dataset.data = [invested, totalInterest];
            } else if (appMode === 'inflation') {
                dataset.data = [invested, Math.max(0, finalCorpus - invested)];
            } else if (appMode === 'cagr' && finalCorpus < invested) {
                dataset.data = [finalCorpus, invested - finalCorpus];
            } else {
                dataset.data = withdrawals > 0 ? [invested, gains, withdrawals] : [invested, gains];
            }
            this.activeDonutScrubYear = row.year;
            this.chartInstance.update('none');
        }

        this.updateInspectionRibbon(row);
    }

    /**
     * Highlight specific data point on hover from external components.
     */
    highlightYear(index: number): void {
        if (!this.chartInstance || this.activeViewType !== 'line') return;
        try {
            const datasets = this.chartInstance.data?.datasets;
            if (!datasets || datasets.length === 0) return;

            // Target the primary corpus/growth dataset (dataset 1 if present and valid, otherwise dataset 0)
            let targetDatasetIndex = (datasets.length > 1) ? 1 : 0;
            let meta = this.chartInstance.getDatasetMeta(targetDatasetIndex);

            // If the target dataset has no controller or is hidden, fallback to dataset 0
            if (!meta || !meta.controller || meta.hidden) {
                targetDatasetIndex = 0;
                meta = this.chartInstance.getDatasetMeta(0);
            }

            if (!meta || !meta.controller) return;

            const targetData = datasets[targetDatasetIndex]?.data;
            if (!targetData || index < 0 || index >= targetData.length) return;
            if (!meta.data || !meta.data[index]) return;

            this.chartInstance.setActiveElements([{ datasetIndex: targetDatasetIndex, index }]);
            if (this.chartInstance.tooltip) {
                this.chartInstance.tooltip.setActiveElements([{ datasetIndex: targetDatasetIndex, index }], { x: 0, y: 0 });
            }
            this.chartInstance.update('none');
            eventBus.publish('table:highlight', { index, scrollIntoView: false });
        } catch {
            // Defensively swallow any transient Chart.js render/animation race condition
        }
    }

    /**
     * Clear highlighted data point.
     */
    clearHighlight(): void {
        if (!this.chartInstance || this.activeViewType !== 'line') return;
        try {
            this.chartInstance.setActiveElements([]);
            if (this.chartInstance.tooltip) {
                this.chartInstance.tooltip.setActiveElements([], { x: 0, y: 0 });
            }
            this.chartInstance.update('none');
        } catch {
            // Defensively swallow any transient Chart.js race condition
        }
    }

    /**
     * Bind view switcher buttons and benchmark comparison chips once during initialization.
     */
    public initControls(): void {
        if (this.controlsInitialized) return;
        this.controlsInitialized = true;

        const lineBtn = this.dom.getElement('chart-view-line');
        const donutBtn = this.dom.getElement('chart-view-donut');
        if (lineBtn) {
            lineBtn.addEventListener('click', () => this.setViewType('line'));
        }
        if (donutBtn) {
            donutBtn.addEventListener('click', () => this.setViewType('donut'));
        }

        const benchmarkChips = this.dom.getElements<HTMLButtonElement>('.benchmark-chip');
        benchmarkChips.forEach(chip => {
            chip.addEventListener('click', () => {
                const bm = (chip.dataset.benchmark || 'none') as 'none' | 'nifty' | 'gold' | 'fd';
                this.setBenchmark(bm);
            });
        });

        // Overlay chips sync
        const corridorInput = this.dom.getElement<HTMLInputElement>('show_historical_corridor');
        if (corridorInput) {
            corridorInput.addEventListener('change', () => {
                this.setHistoricalCorridor(corridorInput.checked);
            });
        }

        const postTaxInput = this.dom.getElement<HTMLInputElement>('show_post_tax');
        if (postTaxInput) {
            postTaxInput.addEventListener('change', () => {
                this.updateActiveLensIndicator();
                if (this.lastResults.length > 0) {
                    this.updateChart(this.lastResults, this.lastEnableSwp);
                }
            });
        }

        const wealthMapInput = this.dom.getElement<HTMLInputElement>('show_wealth_map');
        if (wealthMapInput) {
            wealthMapInput.addEventListener('change', () => {
                this.updateActiveLensIndicator();
                if (this.lastResults.length > 0) {
                    this.updateChart(this.lastResults, this.lastEnableSwp);
                }
            });
        }

        this.updateActiveLensIndicator();
    }

    /**
     * Throttled chart rendering queue via requestAnimationFrame.
     */
    public updateChartThrottled(results: YearResult[], enableSwp: boolean = true): void {
        if (this.renderQueueId) {
            cancelAnimationFrame(this.renderQueueId);
        }
        this.renderQueueId = requestAnimationFrame(() => {
            this.updateChart(results, enableSwp, true);
            this.renderQueueId = null;
        });
    }

    /**
     * Main chart rendering function. Updates existing chart instance or constructs a new one.
     */
    async updateChart(results: YearResult[], enableSwp: boolean, isDragging: boolean = false): Promise<void> {
        this.lastResults = results;
        this.lastEnableSwp = enableSwp;

        const appMode = this.dom.getElement('calculator-app')?.dataset?.mode || '';
        this.telemetryController.updateChartHeaderSummary(results);

        if (this.scrubbingController) {
            this.scrubbingController.syncResults(results);
        }

        const ctxEl = this.dom.getElement<HTMLCanvasElement>('corpusChart') || this.dom.getElement<HTMLCanvasElement>('results-chart');
        if (!ctxEl || !document.body.contains(ctxEl)) return;

        let ChartClass: typeof Chart;
        try {
            ChartClass = await this.loadChartModule();
        } catch (e) {
            console.error('[ChartManager] Failed to load Chart.js module:', e);
            return;
        }

        const ctx = ctxEl.getContext('2d');
        if (!ctx) return;

        const activeMode = this.dom.getElement<HTMLInputElement>('goal_mode')?.value || 'grow';
        const mode = activeMode.toLowerCase().includes('target') ? 'target' : (enableSwp ? 'swp' : 'sip');

        if (this.lastMode && this.lastMode !== mode && this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
        this.lastMode = mode;

        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const showWealthMap = this.dom.getElement<HTMLInputElement>('show_wealth_map')?.checked || false;

        const milestones = this.milestoneCalculator.computeMilestones(results, enableSwp, showPostTax);
        this.currentMilestones = milestones;

        const years = results.map(r => `Yr ${r.year}`);
        const allowedTicks = this.milestoneCalculator.computeHarmonicYearTicks(results.length);

        // ── DONUT VIEW (Asset Allocation) ──
        if (this.activeViewType === 'donut') {
            const finalRow = results[results.length - 1];
            const activeYear = this.activeDonutScrubYear || results.length;
            const targetRow = results.find(r => r.year === activeYear) || finalRow;

            const donut = computeDonutViewData(results, targetRow, appMode, showPostTax);
            const donutConfig = buildDonutChartConfig(
                donut,
                isDragging,
                this.plugins.donutCenterTextPlugin as unknown as Plugin<'doughnut'>,
                this.formatter,
                { textColor: THEME_COLORS.chart.textMuted, headingFont: THEME_FONTS.heading }
            );

            if (this.chartInstance && this.currentChartType === 'doughnut' && this.chartInstance.ctx.canvas === ctxEl) {
                this.chartInstance.data.labels = donut.labels;
                this.chartInstance.data.datasets[0].data = donut.data;
                this.chartInstance.data.datasets[0].backgroundColor = donut.colors;
                this.chartInstance.update(isDragging ? 'none' : undefined);
                this.milestoneCalculator.renderMilestoneGrid(
                    milestones,
                    idx => this.highlightYear(idx),
                    () => this.clearHighlight()
                );
                return;
            }

            const existingChart = ChartClass.getChart(ctxEl);
            if (existingChart) {
                existingChart.destroy();
            }

            this.chartInstance = new ChartClass(ctx, donutConfig as unknown as ChartConfiguration) as unknown as Chart<'line'>;
            this.currentChartType = 'doughnut';
            this.milestoneCalculator.renderMilestoneGrid(
                milestones,
                idx => this.highlightYear(idx),
                () => this.clearHighlight()
            );
            return;
        }

        // ── LINE CHART VIEW (Growth Projection) ──
        const yTop = this.chartInstance?.scales?.y?.top ?? 0;
        const yBottom = this.chartInstance?.scales?.y?.bottom ?? (ctxEl.clientHeight || 400);
        const gradients = this.gradientFactory.createGradients(ctx, yTop, yBottom);

        if (this.chartInstance && this.currentChartType === 'line' && this.chartInstance.ctx.canvas === ctxEl) {
            const datasets = this.datasetBuilder.buildLineDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);

            const previousDatasetCount = this.chartInstance.data.datasets?.length ?? 0;
            const previousStacked = !!this.chartInstance.options.scales?.y?.stacked;
            const schemaChanged = previousDatasetCount !== datasets.length || previousStacked !== showWealthMap;

            if (schemaChanged) {
                this.chartInstance.destroy();
                this.chartInstance = null;
            } else {
                this.chartInstance.data.labels = years;
                this.chartInstance.data.datasets = datasets;

                if (this.chartInstance.options.scales?.y) {
                    this.chartInstance.options.scales.y.stacked = showWealthMap;
                }

                if (isDragging) {
                    if (this.rafId) cancelAnimationFrame(this.rafId);
                    this.rafId = requestAnimationFrame(() => {
                        if (this.chartInstance) {
                            this.chartInstance.update('none');
                        }
                    });
                } else {
                    this.chartInstance.update();
                }
                this.milestoneCalculator.renderMilestoneGrid(
                    milestones,
                    idx => this.highlightYear(idx),
                    () => this.clearHighlight()
                );
                return;
            }
        }

        const existingChart = ChartClass.getChart(ctxEl);
        if (existingChart) {
            existingChart.destroy();
        }

        const datasets = this.datasetBuilder.buildLineDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);
        const config = buildLineChartConfig({
            years,
            datasets,
            plugins: [
                this.plugins.clipGuardPlugin as unknown as Plugin<'line'>,
                this.plugins.crosshairPlugin as unknown as Plugin<'line'>,
                this.plugins.splineMilestonesPlugin as unknown as Plugin<'line'>,
                this.plugins.compoundingIgnitionPlugin as unknown as Plugin<'line'>,
                this.plugins.croreMilestoneLinePlugin as unknown as Plugin<'line'>,
                this.plugins.fdAlphaDeltaPlugin as unknown as Plugin<'line'>,
            ],
            showWealthMap,
            allowedTicks,
            formatter: this.formatter,
            formatAxisTick: (val) => this.formatAxisTick(val),
            onHoverRowIndex: (index) => {
                const row = this.lastResults[index];
                if (row) {
                    this.updateInspectionRibbon(row);
                    if (typeof navigator !== 'undefined' && 'vibrate' in navigator && (row.year % 5 === 0 || row.year === this.lastResults.length)) {
                        try {
                            navigator.vibrate(10);
                        } catch {
                            // Silent ignore
                        }
                    }
                }
            },
        });

        this.chartInstance = new ChartClass(ctx, config) as unknown as Chart<'line'>;
        this.currentChartType = 'line';
        this.milestoneCalculator.renderMilestoneGrid(
            milestones,
            idx => this.highlightYear(idx),
            () => this.clearHighlight()
        );

        if (results.length > 0) {
            const finalRow = results[results.length - 1];
            if (finalRow) {
                this.updateInspectionRibbon(finalRow);
                const statusDot = this.dom.getElement('hud-status-dot');
                if (statusDot) {
                    statusDot.className = 'w-1.5 h-1.5 rounded-full bg-slate-400';
                }
            }
        }
    }

    /**
     * Explicit cleanup to prevent memory leaks and detached event listeners.
     */
    destroy(): void {
        this.unsubscribeEvents.forEach(unsub => unsub());
        this.unsubscribeEvents = [];

        if (this.rafId) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        if (this.renderQueueId) {
            cancelAnimationFrame(this.renderQueueId);
            this.renderQueueId = null;
        }
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
        this.gradientFactory.clearCache();
    }

    getChartInstance(): Chart | null {
        return this.chartInstance;
    }
}
