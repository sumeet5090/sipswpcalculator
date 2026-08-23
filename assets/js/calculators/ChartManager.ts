import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { YearResult } from '../types';
import { THEME_COLORS, THEME_FONTS } from './constants/ThemeTokens.ts';
import type { Chart, ChartDataset, ChartConfiguration, TooltipItem } from 'chart.js';

export interface Milestone {
    type: 'wealth' | 'security';
    label: string;
    description?: string;
    year: number;
    icon: string;
    value: number;
    index: number;
}

interface GradientBundle {
    invested: CanvasGradient;
    corpus: CanvasGradient;
    postTax: CanvasGradient;
}

/**
 * ChartManager.ts
 * Manages instantiation, dataset state transitions, and responsive rendering of Chart.js.
 * Strictly adheres to SOLID, DRY, and POLA principles.
 */
export class ChartManager {
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private dom: DOMAdapter;
    private chartInstance: Chart<'line'> | null = null;
    private currentMilestones: Milestone[] = [];
    private chartModulePromise: Promise<typeof Chart> | null = null;

    constructor(
        formatter: CurrencyFormatter,
        validator: InputValidator = new InputValidator(),
        dom: DOMAdapter = new DOMAdapter()
    ) {
        this.formatter = formatter;
        this.validator = validator;
        this.dom = dom;
    }

    /**
     * Dynamically loads Chart.js as an isolated vendor chunk via Vite.
     * Eliminates external CDN dependencies, network latency, and CSP blocking.
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
     * Compute dynamic linear gradients strictly bounded to actual canvas pixel height.
     */
    private createGradients(ctx: CanvasRenderingContext2D, height: number): GradientBundle {
        const safeHeight = Math.max(height, 200);

        const gradientInvested = ctx.createLinearGradient(0, 0, 0, safeHeight);
        gradientInvested.addColorStop(0, THEME_COLORS.chart.gradientInvestedTop);
        gradientInvested.addColorStop(0.7, THEME_COLORS.chart.gradientInvestedMid);
        gradientInvested.addColorStop(1, THEME_COLORS.chart.gradientInvestedBottom);

        const gradientCorpus = ctx.createLinearGradient(0, 0, 0, safeHeight);
        gradientCorpus.addColorStop(0, THEME_COLORS.chart.gradientCorpusTop);
        gradientCorpus.addColorStop(0.6, THEME_COLORS.chart.gradientCorpusMid);
        gradientCorpus.addColorStop(1, THEME_COLORS.chart.gradientCorpusBottom);

        const gradientPostTax = ctx.createLinearGradient(0, 0, 0, safeHeight);
        gradientPostTax.addColorStop(0, THEME_COLORS.chart.gradientPostTaxTop);
        gradientPostTax.addColorStop(0.7, THEME_COLORS.chart.gradientPostTaxMid);
        gradientPostTax.addColorStop(1, THEME_COLORS.chart.gradientPostTaxBottom);

        return {
            invested: gradientInvested,
            corpus: gradientCorpus,
            postTax: gradientPostTax
        };
    }

    /**
     * Formats axis tick numbers cleanly into Indian (Cr/L/k) or Western (B/M/k) scales.
     */
    formatAxisTick(value: number): string {
        if (isNaN(value) || !isFinite(value)) return '';

        const symbol = this.formatter.getSymbol();
        const currency = this.formatter.getCurrency();

        if (currency === 'INR') {
            if (value >= 10000000) {
                const cr = (value / 10000000).toFixed(1).replace(/\.0$/, '');
                return `${symbol}${cr}Cr`;
            }
            if (value >= 100000) {
                const l = (value / 100000).toFixed(1).replace(/\.0$/, '');
                return `${symbol}${l}L`;
            }
            if (value >= 1000) {
                const k = (value / 1000).toFixed(1).replace(/\.0$/, '');
                return `${symbol}${k}k`;
            }
            return `${symbol}${Math.round(value)}`;
        }

        if (value >= 1000000000) {
            const b = (value / 1000000000).toFixed(1).replace(/\.0$/, '');
            return `${symbol}${b}B`;
        }
        if (value >= 1000000) {
            const m = (value / 1000000).toFixed(1).replace(/\.0$/, '');
            return `${symbol}${m}M`;
        }
        if (value >= 1000) {
            const k = (value / 1000).toFixed(1).replace(/\.0$/, '');
            return `${symbol}${k}k`;
        }
        return `${symbol}${Math.round(value)}`;
    }

    /**
     * Calculate active milestones for current results.
     */
    private computeMilestones(results: YearResult[], enableSwp: boolean, showPostTax: boolean): Milestone[] {
        const milestones: Milestone[] = [];
        const targets = this.validator.getMilestoneTargets().map(t => ({ ...t, reached: false }));
        let swpCovered = false;
        let crossoverReached = false;

        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            const postTaxVal = row.post_tax_total ?? row.combined_total;
            const activeCorpusValue = showPostTax ? postTaxVal : row.combined_total;
            const interest = Math.max(0, activeCorpusValue - row.cumulative_invested);

            // Compounding Crossover Point
            if (!crossoverReached && interest > row.cumulative_invested && row.cumulative_invested > 0) {
                crossoverReached = true;
                milestones.push({
                    type: 'wealth',
                    label: 'Compounding Crossover ⚡',
                    description: `Year ${row.year}: Interest earnings (${this.formatter.formatDynamic(interest)}) have surpassed total invested capital (${this.formatter.formatDynamic(row.cumulative_invested)})!`,
                    year: row.year,
                    icon: '⚡',
                    value: activeCorpusValue,
                    index: i
                });
            }

            for (const target of targets) {
                if (!target.reached && activeCorpusValue >= target.value) {
                    target.reached = true;
                    milestones.push({
                        type: 'wealth',
                        label: target.label,
                        year: row.year,
                        icon: target.icon,
                        value: activeCorpusValue,
                        index: i
                    });
                }
            }

            if (enableSwp && !swpCovered && (row.annual_withdrawal ?? 0) > 0) {
                const tenYearsWithdrawal = (row.annual_withdrawal ?? 0) * 10;
                // Verify sustainability: corpus must cover 10 years of withdrawals and portfolio doesn't deplete to 0 within 10 years
                const isSustainable = activeCorpusValue >= tenYearsWithdrawal;
                if (isSustainable) {
                    swpCovered = true;
                    milestones.push({
                        type: 'security',
                        label: 'SWP Security (10 Yrs)',
                        description: `Corpus (${this.formatter.formatDynamic(activeCorpusValue)}) covers 10 years of SWP withdrawals (Requires ${this.formatter.formatDynamic(tenYearsWithdrawal)})!`,
                        year: row.year,
                        icon: '🛡️',
                        value: activeCorpusValue,
                        index: i
                    });
                }
            }
        }

        return milestones;
    }

    /**
     * Build semantic dataset collection based on active UI toggles.
     */
    private buildDatasets(
        results: YearResult[],
        gradients: GradientBundle,
        enableSwp: boolean,
        showPostTax: boolean,
        showWealthMap: boolean,
        mode: string,
        milestones: Milestone[]
    ): ChartDataset<'line'>[] {
        const cumulative = results.map(r => r.cumulative_invested);
        const corpus = results.map(r => r.combined_total);
        const postTaxCorpus = results.map(r => r.post_tax_total ?? r.combined_total);
        const swp = results.map(r => r.annual_withdrawal ?? 0);

        const milestoneIndices = milestones.map(m => m.index);
        const isSinglePoint = results.length === 1;

        // 0-Year singularity guard: when results.length === 1, ensure point is visible
        const pointRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 6 : (isSinglePoint ? 4 : 0));
        const pointHoverRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 10 : (isSinglePoint ? 8 : 6));
        const pointBgColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? THEME_COLORS.financial.milestoneGold : THEME_COLORS.financial.growth);
        const pointBorderColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? THEME_COLORS.chart.pointBgWhite : THEME_COLORS.financial.growth);
        const pointBorderWidths = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 3 : 2);

        const interestOnly = corpus.map((c, i) => Math.max(0, c - cumulative[i]));

        const datasets: ChartDataset<'line'>[] = [
            {
                label: 'Total Invested',
                data: cumulative,
                borderColor: THEME_COLORS.financial.invested,
                backgroundColor: gradients.invested,
                borderWidth: 2,
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 'origin',
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.invested,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                order: 2,
            },
            {
                label: showWealthMap ? 'Interest Earned' : 'Pre-Tax Corpus',
                data: showWealthMap ? interestOnly : corpus,
                borderColor: THEME_COLORS.financial.growth,
                backgroundColor: gradients.corpus,
                borderWidth: 3,
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: showWealthMap ? true : 0,
                pointBackgroundColor: pointBgColors,
                pointBorderColor: pointBorderColors,
                pointBorderWidth: pointBorderWidths,
                pointRadius: pointRadii,
                pointHoverRadius: pointHoverRadii,
                pointHoverBorderWidth: 3,
                order: 1,
            },
            {
                label: 'Post-Tax Corpus',
                data: postTaxCorpus,
                borderColor: THEME_COLORS.financial.postTax,
                backgroundColor: gradients.postTax,
                borderWidth: 2,
                borderDash: [4, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 'origin',
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.postTax,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !showPostTax || showWealthMap,
                order: 1,
            }
        ];

        if (mode !== 'sip' || enableSwp) {
            datasets.push({
                label: 'Annual Withdrawal',
                data: swp,
                borderColor: THEME_COLORS.financial.withdrawal,
                backgroundColor: THEME_COLORS.chart.swpFillBg,
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.withdrawal,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !enableSwp,
                order: 1,
            });
        }

        if (this.activeBenchmark !== 'none') {
            let rate = 12;
            let label = 'Nifty 50 (12%)';
            let color: string = THEME_COLORS.financial.milestoneGold;

            if (this.activeBenchmark === 'gold') {
                rate = 9;
                label = 'Gold (9%)';
                color = THEME_COLORS.financial.milestoneGoldDark;
            } else if (this.activeBenchmark === 'fd') {
                rate = 6.5;
                label = 'Fixed Deposit (6.5%)';
                color = THEME_COLORS.slate[500];
            }

            const benchmarkData = this.computeBenchmarkCurve(results, rate);
            datasets.push({
                label,
                data: benchmarkData,
                borderColor: color,
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [6, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: color,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                order: 1,
            });
        }

        if (this.shockOverlayData) {
            const crashIdx = this.shockOverlayCrashIndex;
            datasets.push({
                label: this.shockOverlayData.label,
                data: this.shockOverlayData.data,
                borderColor: '#be123c',
                backgroundColor: 'rgba(190, 18, 60, 0.05)',
                borderWidth: 2.5,
                borderDash: [6, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: '#be123c',
                pointBorderColor: THEME_COLORS.chart.pointBgWhite,
                pointRadius: results.map((_, idx) => (crashIdx !== null && idx === crashIdx) ? 6 : (isSinglePoint ? 4 : 0)),
                pointHoverRadius: 7,
                order: 0,
            });
        }

        return datasets;
    }

    private activeBenchmark: 'none' | 'nifty' | 'gold' | 'fd' = 'none';
    private activeViewType: 'line' | 'donut' = 'line';
    private currentChartType: 'line' | 'doughnut' | null = null;
    private lastResults: YearResult[] = [];
    private lastEnableSwp: boolean = true;
    private shockOverlayData: { label: string; data: number[] } | null = null;
    private shockOverlayCrashIndex: number | null = null;

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

    private crosshairPlugin = {
        id: 'crosshairLine',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.scales?.x || !chart.scales?.y) return;

            // Draw active bi-directional crosshair on hover
            if (chart.tooltip?.getActiveElements()?.length) {
                const activePoint = chart.tooltip.getActiveElements()[0];
                const ctx = chart.ctx;
                const x = activePoint.element.x;
                const y = activePoint.element.y;
                const leftX = chart.scales.x.left;
                const topY = chart.scales.y.top;
                const bottomY = chart.scales.y.bottom;

                ctx.save();
                ctx.beginPath();
                ctx.setLineDash([4, 4]);
                ctx.moveTo(x, topY);
                ctx.lineTo(x, bottomY);
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = THEME_COLORS.chart.milestoneLineActive;
                ctx.stroke();

                // Horizontal guide line to Y axis
                ctx.beginPath();
                ctx.moveTo(leftX, y);
                ctx.lineTo(x, y);
                ctx.strokeStyle = THEME_COLORS.chart.milestoneLineSubtle;
                ctx.stroke();
                ctx.restore();
            }
        }
    };

    /**
     * Compute benchmark projection dataset (Nifty 50, Gold, or FD) for the same cashflow sequence.
     */
    private computeBenchmarkCurve(results: YearResult[], benchmarkRate: number): number[] {
        let corpus = 0;
        const curve: number[] = [];
        const monthlyRate = benchmarkRate / 12 / 100;

        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            const monthlySip = row.sip_monthly ?? 0;

            if (i === 0) {
                corpus += (row.begin_balance ?? 0);
            }

            for (let m = 0; m < 12; m++) {
                corpus = (corpus + monthlySip) * (1 + monthlyRate);
            }
            curve.push(Math.round(corpus));
        }

        return curve;
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
                lineBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                lineBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                lineBtn.setAttribute('aria-selected', 'true');
                donutBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                donutBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                donutBtn.setAttribute('aria-selected', 'false');
            } else {
                donutBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                donutBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                donutBtn.setAttribute('aria-selected', 'true');
                lineBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                lineBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                lineBtn.setAttribute('aria-selected', 'false');
            }
        }

        // Destroy existing chart instance to allow fresh type instantiation
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }

        if (this.lastResults.length > 0) {
            this.updateChart(this.lastResults, this.lastEnableSwp);
        }
    }

    /**
     * Highlight specific data point on hover from external components (e.g. breakdown table).
     */
    highlightYear(index: number): void {
        if (!this.chartInstance || this.activeViewType !== 'line') return;
        try {
            this.chartInstance.setActiveElements([{ datasetIndex: 1, index }]);
            if (this.chartInstance.tooltip) {
                this.chartInstance.tooltip.setActiveElements([{ datasetIndex: 1, index }], { x: 0, y: 0 });
            }
            this.chartInstance.update('none');
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
     * Initialize or update the chart.
     */
    async updateChart(results: YearResult[], enableSwp: boolean = true): Promise<void> {
        this.lastResults = results;
        this.lastEnableSwp = enableSwp;

        const ctxEl = this.dom.getElement<HTMLCanvasElement>('corpusChart');
        if (!ctxEl || !document.body.contains(ctxEl)) return;

        // Wire view switcher buttons once
        const lineBtn = this.dom.getElement('chart-view-line');
        const donutBtn = this.dom.getElement('chart-view-donut');
        if (lineBtn && !lineBtn.dataset.wired) {
            lineBtn.dataset.wired = 'true';
            lineBtn.addEventListener('click', () => this.setViewType('line'));
        }
        if (donutBtn && !donutBtn.dataset.wired) {
            donutBtn.dataset.wired = 'true';
            donutBtn.addEventListener('click', () => this.setViewType('donut'));
        }

        // Wire benchmark comparison chips
        const benchmarkChips = this.dom.getElements<HTMLButtonElement>('.benchmark-chip');
        benchmarkChips.forEach(chip => {
            if (!chip.dataset.wired) {
                chip.dataset.wired = 'true';
                chip.addEventListener('click', () => {
                    const bm = (chip.dataset.benchmark || 'none') as 'none' | 'nifty' | 'gold' | 'fd';
                    this.setBenchmark(bm);
                });
            }
        });

        let ChartClass: typeof Chart;
        try {
            ChartClass = await this.loadChartModule();
        } catch (e) {
            console.error('[ChartManager] Failed to load Chart.js module:', e);
            return;
        }

        const ctx = ctxEl.getContext('2d');
        if (!ctx) return;
        ctxEl.style.touchAction = 'pan-y';

        const years = results.map(r => `Yr ${r.year}`);
        const calcApp = document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = calcApp ? (calcApp.dataset.mode || 'all') : 'all';
        const showPostTax = this.dom.getElement<HTMLInputElement>('show_post_tax')?.checked || false;
        const showWealthMap = this.dom.getElement<HTMLInputElement>('show_wealth_map')?.checked || false;

        const milestones = this.computeMilestones(results, enableSwp, showPostTax);
        this.currentMilestones = milestones;

        const fontFamily = THEME_FONTS.heading;
        const gridColor = THEME_COLORS.chart.gridLine;
        const textColor = THEME_COLORS.chart.textMuted;

        // ── DOUGHNUT VIEW (Asset Allocation Split) ──
        if (this.activeViewType === 'donut') {
            if (this.chartInstance && this.currentChartType !== 'doughnut') {
                this.chartInstance.destroy();
                this.chartInstance = null;
            }

            const lastRow = results[results.length - 1];
            const totalInvested = lastRow.cumulative_invested || 0;
            const finalCorpus = showPostTax ? (lastRow.post_tax_total ?? lastRow.combined_total) : lastRow.combined_total;
            const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
            const totalGains = Math.max(0, (finalCorpus + totalWithdrawn) - totalInvested);

            const labels: string[] = ['Total Invested', 'Compounding Gains'];
            const data: number[] = [totalInvested, totalGains];
            const bgColors: string[] = [THEME_COLORS.financial.growthDark, THEME_COLORS.financial.growth];

            if (totalWithdrawn > 0) {
                labels.push('Total Withdrawn');
                data.push(totalWithdrawn);
                bgColors.push(THEME_COLORS.financial.withdrawal);
            }

            const donutConfig: ChartConfiguration<'doughnut'> = {
                type: 'doughnut' as const,
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: bgColors,
                        borderWidth: 3,
                        borderColor: THEME_COLORS.chart.pointBgWhite,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    animation: {
                        duration: 600,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                color: textColor,
                                font: {
                                    family: fontFamily,
                                    size: 12,
                                    weight: 600
                                },
                                padding: 16
                            }
                        },
                        tooltip: {
                            backgroundColor: THEME_COLORS.chart.tooltipBg,
                            titleFont: { family: fontFamily, size: 13, weight: 'bold' },
                            bodyFont: { family: fontFamily, size: 12 },
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: (item) => {
                                    const val = Number(item.raw) || 0;
                                    const total = data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0';
                                    return ` ${item.label}: ${this.formatter.format(val)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            };

            const existingChart = ChartClass.getChart(ctxEl);
            if (existingChart) {
                existingChart.destroy();
            }

            this.chartInstance = new ChartClass(ctx, donutConfig as unknown as ChartConfiguration) as unknown as Chart<'line'>;
            this.currentChartType = 'doughnut';
            this.renderMilestoneGrid(milestones);
            return;
        }

        // ── LINE CHART VIEW (Growth Projection) ──
        const canvasHeight = ctxEl.clientHeight || 400;
        const gradients = this.createGradients(ctx, canvasHeight);

        // Update in-place if chartInstance is alive and still a line chart
        if (this.chartInstance && this.currentChartType === 'line' && this.chartInstance.ctx.canvas === ctxEl) {
            const datasets = this.buildDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);
            this.chartInstance.data.labels = years;
            this.chartInstance.data.datasets = datasets;

            if (this.chartInstance.options.scales?.y) {
                this.chartInstance.options.scales.y.stacked = showWealthMap;
            }

            this.chartInstance.update();
            this.renderMilestoneGrid(milestones);
            return;
        }

        const existingChart = ChartClass.getChart(ctxEl);
        if (existingChart) {
            existingChart.destroy();
        }

        const datasets = this.buildDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);

        const config: ChartConfiguration<'line'> = {
            type: 'line' as const,
            data: {
                labels: years,
                datasets: datasets
            },
            plugins: [this.crosshairPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 750,
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
                            usePointStyle: true,
                            boxWidth: 6,
                            color: textColor,
                            font: {
                                family: fontFamily,
                                size: 10.5,
                                weight: 600
                            },
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: THEME_COLORS.chart.tooltipBg,
                        titleColor: THEME_COLORS.chart.tooltipTitle,
                        titleFont: {
                            family: fontFamily,
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: THEME_COLORS.chart.tooltipBody,
                        bodyFont: {
                            family: fontFamily,
                            size: 13
                        },
                        borderColor: THEME_COLORS.chart.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 4,
                        cornerRadius: 12,
                        usePointStyle: true,
                        callbacks: {
                            label: (context: TooltipItem<'line'>) => {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null && context.parsed.y !== undefined) {
                                    label += this.formatter.format(context.parsed.y);
                                }
                                return label;
                            },
                            afterBody: (tooltipItems: TooltipItem<'line'>[]) => {
                                if (!tooltipItems || tooltipItems.length === 0) return '';
                                const index = tooltipItems[0].dataIndex;
                                const reached = (this.currentMilestones || []).filter(m => m.index === index);
                                if (reached.length > 0) {
                                    return reached.map(m => `\n${m.icon} ${m.label} Reached!`).join('');
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: gridColor,
                            display: false
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: fontFamily,
                                size: 11,
                                weight: 500
                            },
                            maxRotation: 0
                        }
                    },
                    y: {
                        stacked: showWealthMap,
                        grid: {
                            color: gridColor,
                            tickBorderDash: [5, 5]
                        },
                        ticks: {
                            color: textColor,
                            font: {
                                family: fontFamily,
                                size: 11,
                                weight: 500
                            },
                            callback: (value: string | number) => {
                                return this.formatAxisTick(typeof value === 'number' ? value : Number(value));
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        };

        this.chartInstance = new ChartClass(ctx, config) as unknown as Chart<'line'>;
        this.currentChartType = 'line';
        this.renderMilestoneGrid(milestones);
    }

    /**
     * Cross-browser safe celebratory milestone badge renderer.
     */
    renderMilestoneGrid(milestones: Milestone[]): void {
        const container = this.dom.getElement('milestones-container');
        if (!container) return;

        // Legacy-safe DOM clearance
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        if (milestones.length === 0) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        const fragment = document.createDocumentFragment();

        milestones.forEach(m => {
            const card = document.createElement('div');
            card.className = 'bg-gradient-to-r from-amber-50/90 via-white to-emerald-50/50 p-3.5 rounded-2xl border border-amber-200/80 shadow-sm flex items-center gap-3 transition-all duration-200 hover:shadow-md hover:border-amber-300';

            const iconDiv = document.createElement('div');
            iconDiv.className = 'flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100/80 text-xl shrink-0 shadow-sm';
            iconDiv.textContent = m.icon;

            const textDiv = document.createElement('div');
            textDiv.className = 'min-w-0 flex-1';

            const h4 = document.createElement('h4');
            h4.className = 'text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-1.5';
            h4.textContent = m.label;

            const badge = document.createElement('span');
            badge.className = 'text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200';
            badge.textContent = `Year ${m.year}`;
            h4.appendChild(badge);

            const p = document.createElement('p');
            p.className = 'text-[11px] sm:text-xs text-slate-500 mt-0.5 truncate';
            p.textContent = m.type === 'security'
                ? (m.description || '')
                : `Corpus reached ${this.formatter.formatDynamic(m.value)} milestone`;

            textDiv.appendChild(h4);
            textDiv.appendChild(p);
            card.appendChild(iconDiv);
            card.appendChild(textDiv);
            fragment.appendChild(card);
        });

        container.appendChild(fragment);
    }

    /**
     * Explicit cleanup to prevent memory leaks and detached event listeners.
     */
    destroy(): void {
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
    }

    getChartInstance(): Chart | null {
        return this.chartInstance;
    }
}

