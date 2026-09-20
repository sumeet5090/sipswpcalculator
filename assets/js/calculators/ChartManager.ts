import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { YearResult } from '../types';
import { THEME_COLORS, THEME_FONTS } from './constants/ThemeTokens.ts';
import { A11yAnnouncer } from './helpers/A11yAnnouncer';
import { eventBus } from '../utils/EventBus';
import { ChartGradientFactory } from './chart/ChartGradientFactory';
import { ChartMilestoneCalculator, Milestone } from './chart/ChartMilestoneCalculator';
import { ChartDatasetBuilder } from './chart/ChartDatasetBuilder';
import { createChartPlugins } from './chart/ChartPlugins';
import type { ChartScrubbingController } from './controllers/ChartScrubbingController';
import type { Chart, ChartConfiguration } from 'chart.js';

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

    private rafId: number | null = null;
    private renderQueueId: number | null = null;
    private controlsInitialized: boolean = false;

    // Sub-modules
    private gradientFactory: ChartGradientFactory;
    private milestoneCalculator: ChartMilestoneCalculator;
    private datasetBuilder: ChartDatasetBuilder;
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
        if (isNaN(value) || !isFinite(value) || value < 0) return '';

        const symbol = this.formatter.getSymbol();
        const currency = this.formatter.getCurrency();

        if (currency === 'INR') {
            if (value === 0) return `${symbol}0`;
            if (value >= 10000000) {
                const cr = value / 10000000;
                return `${symbol}${Number.isInteger(cr) || cr >= 10 ? cr.toFixed(0) : cr.toFixed(1)} Cr`;
            }
            if (value >= 100000) {
                const l = value / 100000;
                return `${symbol}${Number.isInteger(l) || l >= 10 ? l.toFixed(0) : l.toFixed(1)} L`;
            }
            if (value >= 1000) {
                return `${symbol}${(value / 1000).toFixed(0)}k`;
            }
            return `${symbol}${value.toFixed(0)}`;
        }

        if (value >= 1000000000) {
            return `${symbol}${(value / 1000000000).toFixed(1)}B`;
        }
        if (value >= 1000000) {
            return `${symbol}${(value / 1000000).toFixed(1)}M`;
        }
        if (value >= 1000) {
            return `${symbol}${(value / 1000).toFixed(0)}k`;
        }
        return `${symbol}${value.toFixed(0)}`;
    }

    /**
     * Updates active lens text indicator in the header dock.
     */
    public updateActiveLensIndicator(): void {
        const indicator = this.dom.getElement('active-lens-indicator');
        if (!indicator) return;

        const showCorridor = this.dom.getElement<HTMLInputElement>('show_historical_corridor')?.checked || false;
        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const showWealthMap = this.dom.getElement<HTMLInputElement>('show_wealth_map')?.checked || false;

        const active: string[] = [];
        if (showCorridor) active.push('Corridor');
        if (showPostTax) active.push('§112A Tax');
        if (showWealthMap) active.push('Decomp');

        if (active.length === 0) {
            indicator.textContent = 'Standard View';
            indicator.className = 'hidden sm:inline-flex items-center text-[10px] font-bold text-slate-500 bg-slate-100/90 px-2 py-0.5 rounded-full border border-slate-200/70';
        } else {
            indicator.textContent = active.join(' + ');
            indicator.className = 'hidden sm:inline-flex items-center text-[10px] font-bold text-emerald-800 bg-emerald-100/90 px-2 py-0.5 rounded-full border border-emerald-200/80 shadow-2xs';
        }
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
            if (c.dataset.benchmark === benchmark) {
                c.classList.add('is-active', 'bg-emerald-600', 'text-white', 'border-emerald-600');
                c.classList.remove('bg-slate-50', 'text-slate-600');
            } else {
                c.classList.remove('is-active', 'bg-emerald-600', 'text-white', 'border-emerald-600');
                c.classList.add('bg-slate-50', 'text-slate-600');
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
        if (this.scrubbingController) {
            this.scrubbingController.inspect(row, this.lastResults.length);
            return;
        }

        const rYear = this.dom.getElement('ribbon-inspect-year');
        const rInvested = this.dom.getElement('ribbon-inspect-invested');
        const rGains = this.dom.getElement('ribbon-inspect-gains');
        const rCorpus = this.dom.getElement('ribbon-inspect-corpus');
        const statusDot = this.dom.getElement('hud-status-dot');

        const hudYearLabel = this.dom.getElement('hud-year-label');
        const hudInvested = this.dom.getElement('hud-invested-metric');
        const hudGains = this.dom.getElement('hud-gains-metric');
        const hudTotal = this.dom.getElement('hud-total-metric');
        const hudTimelineIndicator = this.dom.getElement('hud-timeline-indicator');

        if (row) {
            const totalYears = this.lastResults?.length || row.year;
            const investedStr = this.formatter.format(row.cumulative_invested);
            const corpusStr = this.formatter.format(row.combined_total);
            const gains = Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested);
            const gainsStr = this.formatter.format(gains);

            if (rYear) rYear.textContent = `Year ${row.year} of ${totalYears}`;
            if (rInvested) rInvested.textContent = investedStr;
            if (rCorpus) rCorpus.textContent = corpusStr;
            if (rGains) rGains.textContent = `+${gainsStr}`;
            if (statusDot) statusDot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';

            if (hudYearLabel) hudYearLabel.textContent = `Year ${row.year} of ${totalYears}`;
            if (hudInvested) hudInvested.textContent = `Invested: ${investedStr}`;
            if (hudGains) hudGains.textContent = `Gains: +${gainsStr}`;
            if (hudTotal) hudTotal.textContent = `Total: ${corpusStr}`;
            if (hudTimelineIndicator) hudTimelineIndicator.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0';
        }
    }

    /**
     * Announce current data point for assistive technologies and update HUD.
     */
    private announceCurrentPoint(index: number): void {
        const row = this.lastResults[index];
        if (!row) return;
        const invested = this.formatter.format(row.cumulative_invested);
        const corpus = this.formatter.format(row.combined_total);
        const gains = this.formatter.format(Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested));
        A11yAnnouncer.announceYearInspection(row.year, invested, corpus, gains);
        this.updateInspectionRibbon(row);
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

        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const invested = row.cumulative_invested || 0;
        const finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
        const withdrawals = row.cumulative_withdrawals || 0;
        const gains = Math.max(0, (finalCorpus + withdrawals) - invested);

        const dataset = this.chartInstance.data.datasets[0];
        if (dataset) {
            dataset.data = withdrawals > 0 ? [invested, gains, withdrawals] : [invested, gains];
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
            this.chartInstance.setActiveElements([{ datasetIndex: 1, index }]);
            if (this.chartInstance.tooltip) {
                this.chartInstance.tooltip.setActiveElements([{ datasetIndex: 1, index }], { x: 0, y: 0 });
            }
            this.chartInstance.update('none');
            eventBus.publish('table:highlight', { index, scrollIntoView: false });
        } catch {
            // Ignore if chart is updating
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
            // Ignore if chart is updating
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

        const ctxEl = this.dom.getElement<HTMLCanvasElement>('results-chart');
        if (!ctxEl) return;

        const ChartClass = await this.loadChartModule();
        const ctx = ctxEl.getContext('2d');
        if (!ctx) return;

        const activeMode = this.dom.getElement<HTMLInputElement>('goal_mode')?.value || 'grow';
        const mode = activeMode.toLowerCase().includes('target') ? 'target' : (enableSwp ? 'swp' : 'sip');

        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const showWealthMap = this.dom.getElement<HTMLInputElement>('show_wealth_map')?.checked || false;

        const milestones = this.milestoneCalculator.computeMilestones(results, enableSwp, showPostTax);
        this.currentMilestones = milestones;

        const years = results.map(r => `Yr ${r.year}`);
        const allowedTicks = this.milestoneCalculator.computeHarmonicYearTicks(results.length);

        const textColor = THEME_COLORS.chart.textMuted;
        const gridColor = THEME_COLORS.chart.gridLine;

        // ── DONUT VIEW (Asset Allocation) ──
        if (this.activeViewType === 'donut') {
            const finalRow = results[results.length - 1];
            const activeYear = this.activeDonutScrubYear || results.length;
            const targetRow = results.find(r => r.year === activeYear) || finalRow;

            const totalInvested = targetRow ? targetRow.cumulative_invested : 0;
            const finalCorpus = targetRow ? (showPostTax ? (targetRow.post_tax_total ?? targetRow.combined_total) : targetRow.combined_total) : 0;
            const totalWithdrawals = targetRow ? (targetRow.cumulative_withdrawals ?? 0) : 0;
            const totalGains = Math.max(0, (finalCorpus + totalWithdrawals) - totalInvested);

            const donutData = totalWithdrawals > 0
                ? [totalInvested, totalGains, totalWithdrawals]
                : [totalInvested, totalGains];

            const donutLabels = totalWithdrawals > 0
                ? ['Total Invested', 'Estimated Returns', 'Total Withdrawn']
                : ['Total Invested', 'Estimated Returns'];

            const donutColors = totalWithdrawals > 0
                ? [THEME_COLORS.financial.invested, THEME_COLORS.financial.growth, THEME_COLORS.financial.withdrawal]
                : [THEME_COLORS.financial.invested, THEME_COLORS.financial.growth];

            const donutConfig = {
                type: 'doughnut' as const,
                data: {
                    labels: donutLabels,
                    datasets: [{
                        data: donutData,
                        backgroundColor: donutColors,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }],
                },
                plugins: [this.plugins.donutCenterTextPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '74%',
                    animation: {
                        duration: isDragging ? 0 : 500,
                        easing: 'easeOutQuart' as const,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom' as const,
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                font: { family: THEME_FONTS.heading, size: 11, weight: 600 },
                                color: textColor,
                                padding: 16,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: (tooltipItem: any) => {
                                    const val = Number(tooltipItem.raw) || 0;
                                    const sum = donutData.reduce((a, b) => a + b, 0);
                                    const pct = sum > 0 ? ((val / sum) * 100).toFixed(1) : '0';
                                    return ` ${tooltipItem.label}: ${this.formatter.format(val)} (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            };

            if (this.chartInstance && this.currentChartType === 'doughnut' && this.chartInstance.ctx.canvas === ctxEl) {
                this.chartInstance.data.labels = donutLabels;
                this.chartInstance.data.datasets[0].data = donutData;
                this.chartInstance.data.datasets[0].backgroundColor = donutColors;
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

        const existingChart = ChartClass.getChart(ctxEl);
        if (existingChart) {
            existingChart.destroy();
        }

        const datasets = this.datasetBuilder.buildLineDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);

        const config: ChartConfiguration<'line'> = {
            type: 'line' as const,
            data: {
                labels: years,
                datasets: datasets,
            },
            plugins: [
                this.plugins.clipGuardPlugin,
                this.plugins.crosshairPlugin,
                this.plugins.splineMilestonesPlugin,
                this.plugins.compoundingIgnitionPlugin,
                this.plugins.croreMilestoneLinePlugin,
                this.plugins.fdAlphaDeltaPlugin,
            ],
            options: {
                clip: false,
                responsive: true,
                maintainAspectRatio: false,
                devicePixelRatio: Math.min(typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1, 2.5),
                onHover: (_event: any, activeElements: any[]) => {
                    if (activeElements && activeElements.length > 0) {
                        const index = activeElements[0].index;
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
                    }
                },
                animation: {
                    duration: 650,
                    easing: 'easeOutQuart',
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'center',
                        onClick: () => {},
                        labels: {
                            filter: (legendItem, chartData) => {
                                if (typeof legendItem.datasetIndex !== 'number') return true;
                                if (!chartData || !chartData.datasets) return true;
                                const ds = chartData.datasets[legendItem.datasetIndex];
                                return ds !== undefined && !ds.hidden;
                            },
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 12,
                            boxWidth: 8,
                            boxHeight: 8,
                            color: textColor,
                            font: {
                                family: THEME_FONTS.heading,
                                size: 11,
                                weight: 600,
                            },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.98)',
                        titleColor: '#0f172a',
                        bodyColor: '#334155',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        titleFont: {
                            family: THEME_FONTS.heading,
                            size: 13,
                            weight: 700,
                        },
                        bodyFont: {
                            family: THEME_FONTS.mono,
                            size: 11,
                        },
                        callbacks: {
                            label: (context: any) => {
                                const val = context.raw;
                                if (val === null || val === undefined) return '';
                                return ` ${context.dataset.label}: ${this.formatter.format(Number(val))}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: THEME_FONTS.mono,
                                size: 10,
                                weight: 500,
                            },
                            padding: 6,
                            maxRotation: 0,
                            autoSkip: false,
                            callback: (_val: string | number, index: number) => {
                                const yearNum = index + 1;
                                if (allowedTicks.includes(yearNum)) {
                                    return `Yr ${yearNum}`;
                                }
                                return '';
                            },
                        },
                    },
                    y: {
                        position: 'right',
                        stacked: showWealthMap,
                        grid: {
                            color: gridColor,
                            tickBorderDash: [4, 4],
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: THEME_FONTS.mono,
                                size: 10,
                                weight: 500,
                            },
                            padding: 6,
                            callback: (value: string | number) => {
                                return this.formatAxisTick(typeof value === 'number' ? value : Number(value));
                            },
                        },
                        beginAtZero: true,
                    },
                },
            },
        };

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
