import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { YearResult } from '../types';
import type { Chart, ChartDataset, ChartConfiguration, TooltipItem } from 'chart.js';

declare global {
    interface Window {
        Chart: typeof Chart;
    }
}

export interface Milestone {
    type: 'wealth' | 'security';
    label: string;
    description?: string;
    year: number;
    icon: string;
    value: number;
    index: number;
}

/**
 * ChartManager.ts
 * Handles instantiation and updates of the Chart.js visualization.
 * Refactored as an Object-Oriented class.
 */
export class ChartManager {
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private chartInstance: Chart<'line'> | null = null;
    private currentMilestones: Milestone[] = [];
    private chartJsPromise: Promise<void> | null = null;

    constructor(formatter: CurrencyFormatter, validator: InputValidator = new InputValidator()) {
        this.formatter = formatter;
        this.validator = validator;
    }

    /**
     * Dynamically loads Chart.js from CDN on demand.
     */
    private loadChartJs(): Promise<void> {
        if (window.Chart) return Promise.resolve();
        if (this.chartJsPromise) return this.chartJsPromise;

        this.chartJsPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => {
                const fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://unpkg.com/chart.js@3.7.1/dist/chart.min.js';
                fallbackScript.async = true;
                fallbackScript.onload = () => resolve();
                fallbackScript.onerror = () => reject(new Error('Failed to load Chart.js script from CDN'));
                document.head.appendChild(fallbackScript);
            };
            document.head.appendChild(script);
        });

        return this.chartJsPromise;
    }

    formatAxisTick(value: number): string {
        const symbol = this.formatter.getSymbol();
        if (value >= 10000000) return symbol + (value / 10000000).toFixed(1) + 'Cr';
        if (value >= 100000) return symbol + (value / 100000).toFixed(1) + 'L';
        if (value >= 1000) return symbol + (value / 1000).toFixed(1) + 'k';
        return symbol + value;
    }

    /**
     * Initialize or update the chart.
     */
    async updateChart(results: YearResult[], enableSwp: boolean = true): Promise<void> {
        const ctxEl = document.getElementById('corpusChart') as HTMLCanvasElement | null;
        if (!ctxEl) return;

        try {
            await this.loadChartJs();
        } catch (e) {
            console.error('Chart.js dynamic load error:', e);
            return;
        }

        const ctx = ctxEl.getContext('2d');
        if (!ctx) return;

        const years = results.map(r => `Yr ${r.year}`);
        const cumulative = results.map(r => r.cumulative_invested);
        const corpus = results.map(r => r.combined_total);
        const swp = results.map(r => r.annual_withdrawal ?? 0);

        const calcApp = document.querySelector<HTMLElement>('[data-js="calculator-app"]');
        const mode = calcApp ? (calcApp.dataset.mode || 'all') : 'all';
        const showPostTax = (document.getElementById('show_post_tax') as HTMLInputElement | null)?.checked || false;
        const showWealthMap = (document.getElementById('show_wealth_map') as HTMLInputElement | null)?.checked || false;

        // Calculate Milestones
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
                if (activeCorpusValue >= tenYearsWithdrawal) {
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

        this.currentMilestones = milestones;

        const postTaxCorpus = results.map(r => r.post_tax_total ?? r.combined_total);

        const milestoneIndices = milestones.map(m => m.index);
        const pointRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 6 : 0);
        const pointHoverRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 10 : 8);
        const pointBgColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? '#fbbf24' : '#10b981');
        const pointBorderColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? '#ffffff' : '#10b981');
        const pointBorderWidths = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 3 : 2);

        if (this.chartInstance) {
            this.chartInstance.data.labels = years;
            this.chartInstance.data.datasets[0].data = cumulative;
            this.chartInstance.data.datasets[1].data = corpus;
            this.chartInstance.data.datasets[1].pointRadius = pointRadii;
            this.chartInstance.data.datasets[1].pointHoverRadius = pointHoverRadii;
            this.chartInstance.data.datasets[1].pointBackgroundColor = pointBgColors;
            this.chartInstance.data.datasets[1].pointBorderColor = pointBorderColors;
            this.chartInstance.data.datasets[1].pointBorderWidth = pointBorderWidths;

            if (this.chartInstance.data.datasets.length > 2) {
                this.chartInstance.data.datasets[2].data = postTaxCorpus;
                this.chartInstance.data.datasets[2].hidden = !showPostTax || showWealthMap;
            }
            if (this.chartInstance.data.datasets.length > 3) {
                this.chartInstance.data.datasets[3].data = swp;
                this.chartInstance.data.datasets[3].hidden = !enableSwp;
            }

            if (this.chartInstance.options.scales && this.chartInstance.options.scales.y) {
                this.chartInstance.options.scales.y.stacked = showWealthMap;
            }

            if (showWealthMap) {
                const interestOnly = corpus.map((c, i) => c - cumulative[i]);
                this.chartInstance.data.datasets[1].data = interestOnly;
                this.chartInstance.data.datasets[1].fill = true;
                this.chartInstance.data.datasets[1].label = 'Interest Earned';
            } else {
                this.chartInstance.data.datasets[1].data = corpus;
                this.chartInstance.data.datasets[1].fill = false;
                this.chartInstance.data.datasets[1].label = 'Pre-Tax Growth';
            }

            this.chartInstance.update();
            this.renderMilestoneGrid(milestones);
            return;
        }

        const gradientInvested = ctx.createLinearGradient(0, 0, 0, 400);
        gradientInvested.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
        gradientInvested.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        const gradientCorpus = ctx.createLinearGradient(0, 0, 0, 400);
        gradientCorpus.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
        gradientCorpus.addColorStop(1, 'rgba(16, 185, 129, 0.05)');

        const gradientPostTax = ctx.createLinearGradient(0, 0, 0, 400);
        gradientPostTax.addColorStop(0, 'rgba(139, 92, 246, 0.2)');
        gradientPostTax.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

        const fontFamily = "'Plus Jakarta Sans', sans-serif";
        const gridColor = 'rgba(0, 0, 0, 0.05)';
        const textColor = '#64748b';

        const datasets: ChartDataset<'line'>[] = [
            {
                label: 'Total Invested',
                data: cumulative,
                borderColor: '#6366f1',
                backgroundColor: gradientInvested,
                borderWidth: 2,
                tension: 0.4,
                fill: 'origin',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointRadius: 0,
                pointHoverRadius: 6,
            },
            {
                label: showWealthMap ? 'Interest Earned' : 'Pre-Tax Corpus',
                data: showWealthMap ? corpus.map((c, i) => c - cumulative[i]) : corpus,
                borderColor: '#10b981',
                backgroundColor: gradientCorpus,
                borderWidth: 3,
                tension: 0.4,
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
                backgroundColor: gradientPostTax,
                borderWidth: 2,
                borderDash: [3, 3],
                tension: 0.4,
                fill: 'origin',
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#8b5cf6',
                pointRadius: 0,
                pointHoverRadius: 6,
                hidden: !showPostTax || showWealthMap,
            }
        ];

        if (mode !== 'sip') {
            datasets.push({
                label: 'Annual Withdrawal',
                data: swp,
                borderColor: '#f43f5e',
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                fill: false,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#f43f5e',
                pointRadius: 0,
                pointHoverRadius: 6,
                hidden: !enableSwp,
            });
        }



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
                    duration: 1000,
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
                                if (context.parsed.y !== null) {
                                    label += this.formatter.format(context.parsed.y);
                                }
                                return label;
                            },
                            afterBody: (tooltipItems: TooltipItem<'line'>[]) => {
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

        this.chartInstance = new window.Chart(ctx, config) as unknown as Chart<'line'>;
        this.renderMilestoneGrid(milestones);
    }

    renderMilestoneGrid(milestones: Milestone[]): void {
        const container = document.getElementById('milestones-container');
        if (!container) return;

        container.replaceChildren();

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

    getChartInstance(): Chart | null {
        return this.chartInstance;
    }
}
