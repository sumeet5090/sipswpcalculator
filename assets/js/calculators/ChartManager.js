/**
 * ChartManager.js
 * Handles instantiation and updates of the Chart.js visualization.
 */
import { formatCurrency } from './CurrencyHelper.js';

let chartInstance = null;

function formatAxisTick(value) {
    const symbol = '₹';
    if (value >= 10000000) return symbol + (value / 10000000).toFixed(1) + 'Cr';
    if (value >= 100000) return symbol + (value / 100000).toFixed(1) + 'L';
    if (value >= 1000) return symbol + (value / 1000).toFixed(1) + 'k';
    return symbol + value;
}

/**
 * Initialize or update the chart.
 * @param {Array} results - Computed results array from MathEngine.
 * @param {boolean} enableSwp - If SWP is active.
 */
export function updateChart(results, enableSwp = true) {
    const ctxEl = document.getElementById('corpusChart');
    if (!ctxEl) return;

    const ctx = ctxEl.getContext('2d');

    const years = results.map(r => `Yr ${r.year}`);
    const cumulative = results.map(r => r.cumulative_invested);
    const corpus = results.map(r => r.combined_total);
    const swp = results.map(r => r.annual_withdrawal);

    if (chartInstance) {
        chartInstance.data.labels = years;
        chartInstance.data.datasets[0].data = cumulative;
        chartInstance.data.datasets[1].data = corpus;
        chartInstance.data.datasets[2].data = swp;
        chartInstance.data.datasets[2].hidden = !enableSwp;
        chartInstance.update();
        return;
    }

    const gradientInvested = ctx.createLinearGradient(0, 0, 0, 400);
    gradientInvested.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
    gradientInvested.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

    const gradientCorpus = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCorpus.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    gradientCorpus.addColorStop(1, 'rgba(16, 185, 129, 0.05)');

    const fontFamily = "'Plus Jakarta Sans', sans-serif";
    const gridColor = 'rgba(0, 0, 0, 0.05)';
    const textColor = '#64748b';

    const config = {
        type: 'line',
        data: {
            labels: years,
            datasets: [
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
                    label: 'Wealth Gained',
                    data: corpus,
                    borderColor: '#10b981',
                    backgroundColor: gradientCorpus,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: 0,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                },
                {
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
                }
            ]
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
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        color: textColor,
                        font: {
                            family: fontFamily,
                            size: 12,
                            weight: 600
                        },
                        padding: 20
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
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += formatCurrency(context.parsed.y);
                            }
                            return label;
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
                            family: fontFamily
                        },
                        maxRotation: 0
                    }
                },
                y: {
                    grid: {
                        color: gridColor,
                        borderDash: [5, 5]
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            family: fontFamily
                        },
                        callback: function (value) {
                            return formatAxisTick(value);
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    };

    chartInstance = new window.Chart(ctx, config);
}

/**
 * Reset/Destroy the current chart instance (useful for cleanups or base64 pulls).
 * @returns {Chart|null}
 */
export function getChartInstance() {
    return chartInstance;
}
