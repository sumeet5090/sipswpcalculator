import { THEME_COLORS, THEME_FONTS } from '../constants/ThemeTokens.ts';
import type { CurrencyFormatter } from '../CurrencyHelper';
import type { YearResult } from '../../types';
import type { ChartConfiguration, ChartDataset, Plugin } from 'chart.js';

export interface DonutViewData {
    data: number[];
    labels: string[];
    colors: string[];
}

export function computeDonutViewData(
    results: YearResult[],
    targetRow: YearResult | undefined,
    appMode: string,
    showPostTax: boolean
): DonutViewData {
    if (appMode === 'emi') {
        const totalInterest = results.reduce((acc, r) => acc + (r.interest ?? 0), 0);
        const principal = targetRow ? targetRow.cumulative_invested : 0;
        return {
            data: [principal, totalInterest],
            labels: ['Principal Loan Amount', 'Total Interest Payable'],
            colors: [THEME_COLORS.financial.tax, '#f43f5e'],
        };
    }

    if (appMode === 'inflation') {
        const baseCost = targetRow ? targetRow.cumulative_invested : 0;
        const escalation = targetRow ? Math.max(0, targetRow.combined_total - baseCost) : 0;
        return {
            data: [baseCost, escalation],
            labels: ['Current Living Cost', 'Inflation Escalation'],
            colors: [THEME_COLORS.financial.invested, '#f59e0b'],
        };
    }

    if (appMode === 'cagr' && targetRow && targetRow.combined_total < targetRow.cumulative_invested) {
        return {
            data: [targetRow.combined_total, targetRow.cumulative_invested - targetRow.combined_total],
            labels: ['Retained Capital', 'Capital Loss'],
            colors: [THEME_COLORS.financial.invested, '#f43f5e'],
        };
    }

    const totalInvested = targetRow ? targetRow.cumulative_invested : 0;
    const finalCorpus = targetRow ? (showPostTax ? (targetRow.post_tax_total ?? targetRow.combined_total) : targetRow.combined_total) : 0;
    const totalWithdrawals = targetRow ? (targetRow.cumulative_withdrawals ?? 0) : 0;
    const totalGains = Math.max(0, (finalCorpus + totalWithdrawals) - totalInvested);

    if (totalWithdrawals > 0) {
        return {
            data: [totalInvested, totalGains, totalWithdrawals],
            labels: ['Total Invested', 'Estimated Returns', 'Total Withdrawn'],
            colors: [THEME_COLORS.financial.invested, THEME_COLORS.financial.growth, THEME_COLORS.financial.withdrawal],
        };
    }

    return {
        data: [totalInvested, totalGains],
        labels: ['Total Invested', 'Estimated Returns'],
        colors: [THEME_COLORS.financial.invested, THEME_COLORS.financial.growth],
    };
}

export function buildDonutChartConfig(
    donut: DonutViewData,
    isDragging: boolean,
    donutCenterPlugin: Plugin<'doughnut'>,
    formatter: CurrencyFormatter,
    themeOverrides?: { textColor: string; headingFont: string }
): ChartConfiguration<'doughnut'> {
    const textColor = themeOverrides?.textColor ?? THEME_COLORS.chart.textMuted;
    const headingFont = themeOverrides?.headingFont ?? THEME_FONTS.heading;

    return {
        type: 'doughnut',
        data: {
            labels: donut.labels,
            datasets: [{
                data: donut.data,
                backgroundColor: donut.colors,
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 6,
            }],
        },
        plugins: [donutCenterPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            animation: {
                duration: isDragging ? 0 : 500,
                easing: 'easeOutQuart',
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        font: { family: headingFont, size: 11, weight: 600 },
                        color: textColor,
                        padding: 16,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (tooltipItem) => {
                            const val = Number(tooltipItem.raw) || 0;
                            const sum = donut.data.reduce((a, b) => a + b, 0);
                            const pct = sum > 0 ? ((val / sum) * 100).toFixed(1) : '0';
                            return ` ${tooltipItem.label}: ${formatter.format(val)} (${pct}%)`;
                        },
                    },
                },
            },
        },
    };
}

export function buildLineChartConfig(params: {
    years: string[];
    datasets: ChartDataset<'line'>[];
    plugins: Plugin<'line'>[];
    showWealthMap: boolean;
    allowedTicks: number[];
    formatter: CurrencyFormatter;
    formatAxisTick: (val: number) => string;
    onHoverRowIndex: (index: number) => void;
}): ChartConfiguration<'line'> {
    const textColor = THEME_COLORS.chart.textMuted;
    const gridColor = THEME_COLORS.chart.gridLine;

    return {
        type: 'line',
        data: {
            labels: params.years,
            datasets: params.datasets,
        },
        plugins: params.plugins,
        options: {
            clip: false,
            responsive: true,
            maintainAspectRatio: false,
            devicePixelRatio: Math.min(typeof window !== 'undefined' ? (window.devicePixelRatio || 1) : 1, 2.5),
            onHover: (_event, activeElements) => {
                if (activeElements && activeElements.length > 0) {
                    params.onHoverRowIndex(activeElements[0].index);
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
                    onClick: (_e, legendItem, legend) => {
                        const index = legendItem.datasetIndex;
                        const ci = legend.chart;
                        if (typeof index === 'number') {
                            if (ci.isDatasetVisible(index)) {
                                ci.hide(index);
                                legendItem.hidden = true;
                            } else {
                                ci.show(index);
                                legendItem.hidden = false;
                            }
                        }
                    },
                    labels: {
                        filter: (legendItem, chartData) => {
                            if (typeof legendItem.datasetIndex !== 'number') return true;
                            if (!chartData || !chartData.datasets) return true;
                            return chartData.datasets[legendItem.datasetIndex] !== undefined;
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
                        label: (context) => {
                            const val = context.raw;
                            if (val === null || val === undefined) return '';
                            return ` ${context.dataset.label}: ${params.formatter.format(Number(val))}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: textColor,
                        font: { family: THEME_FONTS.mono, size: 10, weight: 500 },
                        padding: 6,
                        maxRotation: 0,
                        autoSkip: false,
                        callback: (_val, index) => {
                            const yearNum = index + 1;
                            return params.allowedTicks.includes(yearNum) ? `Yr ${yearNum}` : '';
                        },
                    },
                },
                y: {
                    position: 'right',
                    stacked: params.showWealthMap,
                    grid: {
                        color: gridColor,
                        tickBorderDash: [4, 4],
                    },
                    ticks: {
                        color: textColor,
                        font: { family: THEME_FONTS.mono, size: 10, weight: 500 },
                        padding: 6,
                        callback: (value) => params.formatAxisTick(typeof value === 'number' ? value : Number(value)),
                    },
                    beginAtZero: true,
                },
            },
        },
    };
}
