import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { YearResult } from '../types';
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
    private chartInstance: Chart<'line'> | null = null;
    private currentMilestones: Milestone[] = [];
    private chartModulePromise: Promise<typeof Chart> | null = null;

    constructor(formatter: CurrencyFormatter, validator: InputValidator = new InputValidator()) {
        this.formatter = formatter;
        this.validator = validator;
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
        gradientInvested.addColorStop(0, 'rgba(79, 70, 229, 0.22)');
        gradientInvested.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        const gradientCorpus = ctx.createLinearGradient(0, 0, 0, safeHeight);
        gradientCorpus.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
        gradientCorpus.addColorStop(1, 'rgba(16, 185, 129, 0.03)');

        const gradientPostTax = ctx.createLinearGradient(0, 0, 0, safeHeight);
        gradientPostTax.addColorStop(0, 'rgba(139, 92, 246, 0.22)');
        gradientPostTax.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

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

        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            const postTaxVal = row.post_tax_total ?? row.combined_total;
            const activeCorpusValue = showPostTax ? postTaxVal : row.combined_total;

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
        const pointBgColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? '#fbbf24' : '#10b981');
        const pointBorderColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? '#ffffff' : '#10b981');
        const pointBorderWidths = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 3 : 2);

        const interestOnly = corpus.map((c, i) => Math.max(0, c - cumulative[i]));

        const datasets: ChartDataset<'line'>[] = [
            {
                label: 'Total Invested',
                data: cumulative,
                borderColor: '#6366f1',
                backgroundColor: gradients.invested,
                borderWidth: 2,
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 'origin',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
            },
            {
                label: showWealthMap ? 'Interest Earned' : 'Pre-Tax Corpus',
                data: showWealthMap ? interestOnly : corpus,
                borderColor: '#10b981',
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
            },
            {
                label: 'Post-Tax Corpus',
                data: postTaxCorpus,
                borderColor: '#8b5cf6',
                backgroundColor: gradients.postTax,
                borderWidth: 2,
                borderDash: [4, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 'origin',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#8b5cf6',
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !showPostTax || showWealthMap,
            }
        ];

        if (mode !== 'sip' || enableSwp) {
            datasets.push({
                label: 'Annual Withdrawal',
                data: swp,
                borderColor: '#f43f5e',
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#f43f5e',
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !enableSwp,
            });
        }

        return datasets;
    }

    /**
     * Initialize or update the chart.
     */
    async updateChart(results: YearResult[], enableSwp: boolean = true): Promise<void> {
        const ctxEl = document.getElementById('corpusChart') as HTMLCanvasElement | null;
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

        const years = results.map(r => `Yr ${r.year}`);
        const calcApp = document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = calcApp ? (calcApp.dataset.mode || 'all') : 'all';
        const showPostTax = (document.getElementById('show_post_tax') as HTMLInputElement | null)?.checked || false;
        const showWealthMap = (document.getElementById('show_wealth_map') as HTMLInputElement | null)?.checked || false;

        const milestones = this.computeMilestones(results, enableSwp, showPostTax);
        this.currentMilestones = milestones;

        const canvasHeight = ctxEl.clientHeight || 400;
        const gradients = this.createGradients(ctx, canvasHeight);

        // Update in-place if chartInstance is alive and still bound to this canvas
        if (this.chartInstance && this.chartInstance.ctx.canvas === ctxEl) {
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

        // Clean up any existing chart attached to the canvas element before creating a new one
        const existingChart = ChartClass.getChart(ctxEl);
        if (existingChart) {
            existingChart.destroy();
        }

        const fontFamily = "'Plus Jakarta Sans', sans-serif";
        const gridColor = 'rgba(0, 0, 0, 0.05)';
        const textColor = '#64748b';

        const datasets = this.buildDatasets(results, gradients, enableSwp, showPostTax, showWealthMap, mode, milestones);

        const config: ChartConfiguration<'line'> = {
            type: 'line' as const,
            data: {
                labels: years,
                datasets: datasets
            },
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
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#f8fafc',
                        titleFont: {
                            family: fontFamily,
                            size: 14,
                            weight: 'bold'
                        },
                        bodyColor: '#cbd5e1',
                        bodyFont: {
                            family: fontFamily,
                            size: 13
                        },
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 4,
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
        this.renderMilestoneGrid(milestones);
    }

    /**
     * Cross-browser safe milestone badge renderer.
     */
    renderMilestoneGrid(milestones: Milestone[]): void {
        const container = document.getElementById('milestones-container');
        if (!container) return;

        // Legacy-safe DOM clearance (avoids replaceChildren() bugs on older iOS Safari)
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
            card.className = 'glass-card p-4 flex items-start gap-3 border border-slate-100/85 shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5';

            const iconDiv = document.createElement('div');
            iconDiv.className = 'flex items-center justify-center w-10 h-10 rounded-xl bg-amber-50 text-xl shrink-0';
            iconDiv.textContent = m.icon;

            const textDiv = document.createElement('div');

            const h4 = document.createElement('h4');
            h4.className = 'text-sm font-bold text-slate-800';
            h4.textContent = m.label;

            const p = document.createElement('p');
            p.className = 'text-xs text-slate-500 mt-1';
            p.textContent = m.type === 'security'
                ? (m.description || '')
                : `Crossed in Year ${m.year} with ${this.formatter.formatDynamic(m.value)}`;

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
