import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { YearResult } from '../types';
import { THEME_COLORS, THEME_FONTS } from './constants/ThemeTokens.ts';
import { ChartPatternHelper } from './helpers/ChartPatternHelper.ts';
import { A11yAnnouncer } from './helpers/A11yAnnouncer.ts';
import type { ChartScrubbingController } from './controllers/ChartScrubbingController';
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
 * Manages instantiation, dataset state transitions, High-DPI scaling, and responsive rendering of Chart.js.
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
     * Injects the dedicated scrubbing controller for bi-directional synchronization.
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
        });
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
     * Configures hardware-accelerated canvas sizing capped at 2.5x DPR to prevent mobile GPU throttling.
     */
    private configureCanvasDPI(canvas: HTMLCanvasElement): void {
        const dpr = Math.min(typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1, 2.5);
        const rect = canvas.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            canvas.width = Math.round(rect.width * dpr);
            canvas.height = Math.round(rect.height * dpr);
        }
    }

    /**
     * Compute dynamic linear gradients strictly bounded to active Y-axis scale bounds.
     */
    private createGradients(ctx: CanvasRenderingContext2D, top: number = 0, bottom: number = 400): GradientBundle {
        const safeTop = Math.max(0, top);
        const safeBottom = Math.max(safeTop + 60, bottom);

        const gradientInvested = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
        gradientInvested.addColorStop(0, THEME_COLORS.chart.gradientInvestedTop);
        gradientInvested.addColorStop(0.7, THEME_COLORS.chart.gradientInvestedMid);
        gradientInvested.addColorStop(1, THEME_COLORS.chart.gradientInvestedBottom);

        const gradientCorpus = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
        gradientCorpus.addColorStop(0, THEME_COLORS.chart.gradientCorpusTop);
        gradientCorpus.addColorStop(0.6, THEME_COLORS.chart.gradientCorpusMid);
        gradientCorpus.addColorStop(1, THEME_COLORS.chart.gradientCorpusBottom);

        const gradientPostTax = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
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
                const cr = value / 10000000;
                return `${symbol}${cr >= 10 ? cr.toFixed(0) : cr.toFixed(1)} Cr`;
            }
            if (value >= 100000) {
                const l = value / 100000;
                return `${symbol}${l >= 10 ? l.toFixed(0) : l.toFixed(1)} L`;
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
     * Builds synthesized datasets adhering to mutual exclusivity and clean visual layering.
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
                pointStyle: ChartPatternHelper.getPointStyle('invested'),
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.invested,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                order: 3,
            },
            {
                label: showWealthMap ? 'Interest Earned' : 'Pre-Tax Corpus',
                data: showWealthMap ? interestOnly : corpus,
                borderColor: THEME_COLORS.financial.growth,
                backgroundColor: gradients.corpus,
                borderWidth: 3,
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: showWealthMap ? true : (showPostTax ? '+1' : 0),
                pointStyle: ChartPatternHelper.getPointStyle('corpus'),
                pointBackgroundColor: pointBgColors,
                pointBorderColor: pointBorderColors,
                pointBorderWidth: pointBorderWidths,
                pointRadius: pointRadii,
                pointHoverRadius: pointHoverRadii,
                pointHoverBorderWidth: 3,
                order: 1,
            },
            {
                label: 'Post-Tax Corpus (§112A Net)',
                data: postTaxCorpus,
                borderColor: THEME_COLORS.financial.postTax,
                backgroundColor: 'rgba(139, 92, 246, 0.09)',
                borderWidth: 2,
                borderDash: [4, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 1, // Fill between dataset 1 (Pre-tax) and dataset 2 (Post-tax)
                pointStyle: ChartPatternHelper.getPointStyle('postTax'),
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.postTax,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !showPostTax || showWealthMap,
                order: 2,
            }
        ];

        // Historical Volatility Corridor (Suppressed when Post-Tax is active to prevent color clutter)
        if (this.showHistoricalCorridor && !showWealthMap && !showPostTax && results.length > 1) {
            const lowerCorridor = this.computeBenchmarkCurve(results, 10.2);
            const upperCorridor = this.computeBenchmarkCurve(results, 15.8);

            datasets.push({
                label: 'Historical 10th Percentile (10.2% CAGR)',
                data: lowerCorridor,
                borderColor: 'rgba(5, 150, 105, 0.4)',
                backgroundColor: 'rgba(5, 150, 105, 0.06)',
                borderWidth: 1.5,
                borderDash: [2, 2],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: '+1',
                pointRadius: 0,
                pointHoverRadius: 4,
                order: 4,
            });

            datasets.push({
                label: 'Historical 90th Percentile (15.8% CAGR)',
                data: upperCorridor,
                borderColor: 'rgba(5, 150, 105, 0.4)',
                backgroundColor: 'transparent',
                borderWidth: 1.5,
                borderDash: [2, 2],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointRadius: 0,
                pointHoverRadius: 4,
                order: 4,
            });
        }

        const hasStepUp = !enableSwp && results.length > 1 && ((results[1].annual_contribution ?? 0) > (results[0].annual_contribution ?? 0));
        if (hasStepUp && !showWealthMap) {
            const yr1 = results[0];
            const baseMonthlySip = yr1.annual_contribution ? (yr1.annual_contribution / 12) : 0;
            if (baseMonthlySip > 0) {
                const yr1Interest = yr1.interest ?? 0;
                const approxAnnualRate = yr1.annual_contribution > 0 ? ((yr1Interest * 2) / yr1.annual_contribution) : 0.12;
                const rm = approxAnnualRate / 12;

                const flatData = results.map(r => {
                    const months = r.year * 12;
                    if (rm > 0) {
                        return Math.round(baseMonthlySip * ((Math.pow(1 + rm, months) - 1) / rm) * (1 + rm));
                    }
                    return Math.round(baseMonthlySip * months);
                });

                datasets.push({
                    label: 'Flat SIP Baseline (0% Step-Up)',
                    data: flatData,
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    tension: 0.4,
                    cubicInterpolationMode: 'monotone' as const,
                    fill: false,
                    pointRadius: isSinglePoint ? 4 : 0,
                    pointHoverRadius: 5,
                    pointHoverBorderColor: '#94a3b8',
                    pointHoverBackgroundColor: '#ffffff',
                    order: 3,
                });
            }
        }

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

    private crosshairPlugin = {
        id: 'crosshairLine',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.scales?.x || !chart.scales?.y) return;

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
     * Compounding Ignition Zone Plugin: Illuminates the inflection zone where annual interest surpasses annual SIP contributions.
     */
    private compoundingIgnitionPlugin = {
        id: 'compoundingIgnitionZone',
        beforeDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.chartArea) return;
            const meta = chart.getDatasetMeta(1);
            if (!meta || !meta.data || meta.data.length === 0) return;

            const results = this.lastResults;
            if (results.length < 2) return;

            const crossoverIdx = results.findIndex((r, idx) => {
                if (idx === 0) return false;
                const annualInterest = r.interest || 0;
                const annualContribution = r.annual_contribution || 0;
                return annualInterest >= annualContribution && annualContribution > 0;
            });

            if (crossoverIdx === -1 || !meta.data[crossoverIdx]) return;

            const ctx = chart.ctx;
            const xPos = meta.data[crossoverIdx].x;
            const { top, bottom, right } = chart.chartArea;

            ctx.save();
            // Ambient aurora ignition glow
            const gradient = ctx.createLinearGradient(xPos, 0, right, 0);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.08)');
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

            ctx.fillStyle = gradient;
            ctx.fillRect(xPos, top, right - xPos, bottom - top);

            // Demarcation dotted line
            ctx.beginPath();
            ctx.setLineDash([3, 3]);
            ctx.moveTo(xPos, top);
            ctx.lineTo(xPos, bottom);
            ctx.lineWidth = 1.5;
            ctx.strokeStyle = '#059669';
            ctx.stroke();

            // Ignition Pill annotation
            ctx.font = '700 9px Inter, sans-serif';
            ctx.fillStyle = '#047857';
            ctx.fillText('⚡ GAINS OUTPACE SIP', xPos + 6, top + 14);
            ctx.restore();
        }
    };

    /**
     * Spatial Cursor Badge Plugin: Pins a dynamic, edge-clamped coordinate badge directly above active point.
     */
    private spatialCursorBadgePlugin = {
        id: 'spatialCursorBadge',
        afterDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.tooltip?.getActiveElements()?.length || !chart.chartArea) return;

            const activePoint = chart.tooltip.getActiveElements()[0];
            const { ctx, chartArea } = chart;
            const { x, y } = activePoint.element;
            const dataIndex = activePoint.index;
            const row = this.lastResults[dataIndex];
            if (!row) return;

            ctx.save();
            const text = `Yr ${row.year}: ${this.formatter.format(row.combined_total)}`;
            ctx.font = '700 11px Inter, sans-serif';
            const textWidth = ctx.measureText(text).width;
            const badgeWidth = textWidth + 16;
            const badgeHeight = 22;

            let badgeX = x - (badgeWidth / 2);
            if (badgeX < chartArea.left + 4) badgeX = chartArea.left + 4;
            if (badgeX + badgeWidth > chartArea.right - 4) badgeX = chartArea.right - 4 - badgeWidth;

            let badgeY = y - 28;
            if (badgeY < chartArea.top + 4) badgeY = y + 12;

            ctx.fillStyle = '#0f172a';
            ctx.beginPath();
            if (typeof ctx.roundRect === 'function') {
                ctx.roundRect(badgeX, badgeY, badgeWidth, badgeHeight, 6);
            } else {
                ctx.rect(badgeX, badgeY, badgeWidth, badgeHeight);
            }
            ctx.fill();

            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, badgeX + 8, badgeY + (badgeHeight / 2));
            ctx.restore();
        }
    };

    private donutCenterTextPlugin = {
        id: 'donutCenterText',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'doughnut') return;
            const ctx = chart.ctx;
            const chartArea = chart.chartArea;
            if (!chartArea) return;

            const datasets = chart.data.datasets;
            if (!datasets || datasets.length === 0) return;

            const data = datasets[0].data as number[];
            if (!data || data.length < 2) return;

            const totalInvested = data[0] || 0;
            const totalGains = data[1] || 0;
            const totalWithdrawals = (data.length > 2 ? data[2] : 0) || 0;
            const finalValue = totalGains + totalInvested + totalWithdrawals;
            const multiplier = totalInvested > 0 ? (finalValue / totalInvested).toFixed(1) : '1.0';

            const centerX = (chartArea.left + chartArea.right) / 2;
            const centerY = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            ctx.font = `800 22px ${THEME_FONTS.heading}`;
            ctx.fillStyle = '#047857';
            ctx.fillText(`${multiplier}×`, centerX, centerY - 7);

            ctx.font = `700 9px ${THEME_FONTS.heading}`;
            ctx.fillStyle = '#64748b';
            const yearLabel = this.activeDonutScrubYear ? `YR ${this.activeDonutScrubYear} ROI` : 'ROI MULTIPLIER';
            ctx.fillText(yearLabel, centerX, centerY + 12);
            ctx.restore();
        }
    };

    private splineMilestonesPlugin = {
        id: 'splineMilestones',
        afterDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line') return;
            const meta = chart.getDatasetMeta(1);
            if (!meta || !meta.data) return;

            const ctx = chart.ctx;
            const milestones = this.currentMilestones || [];

            milestones.forEach(m => {
                if (m.index === undefined || !meta.data[m.index]) return;
                const point = meta.data[m.index];

                ctx.save();
                ctx.beginPath();
                ctx.arc(point.x, point.y, 11, 0, Math.PI * 2);
                ctx.fillStyle = m.type === 'security' ? 'rgba(245, 158, 11, 0.22)' : 'rgba(16, 185, 129, 0.22)';
                ctx.fill();

                ctx.beginPath();
                ctx.arc(point.x, point.y, 5.5, 0, Math.PI * 2);
                ctx.fillStyle = m.type === 'security' ? '#d97706' : '#10b981';
                ctx.fill();
                ctx.lineWidth = 2;
                ctx.strokeStyle = '#ffffff';
                ctx.stroke();
                ctx.restore();
            });
        }
    };

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

        if (row) {
            const totalYears = this.lastResults?.length || row.year;
            if (rYear) rYear.textContent = `Year ${row.year} of ${totalYears}`;
            if (rInvested) rInvested.textContent = this.formatter.format(row.cumulative_invested);
            if (rCorpus) rCorpus.textContent = this.formatter.format(row.combined_total);
            if (rGains) {
                const gains = Math.max(0, (row.combined_total + (row.cumulative_withdrawals ?? 0)) - row.cumulative_invested);
                rGains.textContent = `+${this.formatter.format(gains)}`;
            }
            if (statusDot) {
                statusDot.className = 'w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse';
            }
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
        const postTaxInput = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const wealthMapInput = this.dom.getElement<HTMLInputElement>('show_wealth_map');

        [corridorInput, postTaxInput, wealthMapInput].forEach(input => {
            if (input) {
                input.addEventListener('change', () => {
                    this.updateActiveLensIndicator();
                });
            }
        });

        const canvasContainer = this.dom.getElement<HTMLElement>('chart-canvas-container');
        if (canvasContainer) {
            let activeKeyboardIndex = 0;
            canvasContainer.addEventListener('keydown', (e: KeyboardEvent) => {
                if (!this.lastResults || this.lastResults.length === 0) return;
                const maxIndex = this.lastResults.length - 1;

                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeKeyboardIndex = Math.min(maxIndex, activeKeyboardIndex + 1);
                    this.highlightYear(activeKeyboardIndex);
                    this.announceCurrentPoint(activeKeyboardIndex);
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeKeyboardIndex = Math.max(0, activeKeyboardIndex - 1);
                    this.highlightYear(activeKeyboardIndex);
                    this.announceCurrentPoint(activeKeyboardIndex);
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    activeKeyboardIndex = 0;
                    this.highlightYear(activeKeyboardIndex);
                    this.announceCurrentPoint(activeKeyboardIndex);
                } else if (e.key === 'End') {
                    e.preventDefault();
                    activeKeyboardIndex = maxIndex;
                    this.highlightYear(activeKeyboardIndex);
                    this.announceCurrentPoint(activeKeyboardIndex);
                }
            });
        }
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
     * Initialize or update the chart.
     */
    async updateChart(results: YearResult[], enableSwp: boolean = true, isDragging: boolean = false): Promise<void> {
        this.initControls();
        this.lastResults = results;
        this.lastEnableSwp = enableSwp;

        if (this.scrubbingController) {
            this.scrubbingController.syncResults(results);
        }

        const ctxEl = this.dom.getElement<HTMLCanvasElement>('corpusChart');
        if (!ctxEl || !document.body.contains(ctxEl)) return;

        this.configureCanvasDPI(ctxEl);

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
        this.updateActiveLensIndicator();

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
            const totalInvested = lastRow?.cumulative_invested || 0;
            const finalCorpus = showPostTax ? (lastRow?.post_tax_total ?? lastRow?.combined_total ?? 0) : (lastRow?.combined_total ?? 0);
            const totalWithdrawn = lastRow?.cumulative_withdrawals || 0;
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
                plugins: [this.donutCenterTextPlugin],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    animation: {
                        duration: isDragging ? 0 : 500,
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
        const yTop = this.chartInstance?.scales?.y?.top ?? 0;
        const yBottom = this.chartInstance?.scales?.y?.bottom ?? (ctxEl.clientHeight || 400);
        const gradients = this.createGradients(ctx, yTop, yBottom);

        if (this.chartInstance && this.currentChartType === 'line' && this.chartInstance.ctx.canvas === ctxEl) {
            const datasets = this.buildDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);
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
            plugins: [
                this.crosshairPlugin,
                this.splineMilestonesPlugin,
                this.compoundingIgnitionPlugin,
                this.spatialCursorBadgePlugin
            ],
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
     * Cross-browser safe celebratory milestone badge renderer with bidirectional chart highlight sync.
     */
    renderMilestoneGrid(milestones: Milestone[]): void {
        const container = this.dom.getElement('milestones-container');
        if (!container) return;

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
            card.className = 'bg-gradient-to-r from-amber-50/90 via-white to-emerald-50/50 p-3.5 rounded-2xl border border-amber-200/80 shadow-sm flex items-center gap-3 transition-all duration-200 hover:shadow-md hover:border-amber-300 cursor-pointer';

            card.addEventListener('mouseenter', () => this.highlightYear(m.index));
            card.addEventListener('mouseleave', () => this.clearHighlight());

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
    }

    getChartInstance(): Chart | null {
        return this.chartInstance;
    }
}
