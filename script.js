document.addEventListener('DOMContentLoaded', () => {
    // Chart.js
    const ctx = document.getElementById('corpusChart');
    if (ctx && window.chartData && window.chartData.years.length > 0) {
        const chartConfig = getChartConfig(window.chartData);
        window.corpusChartInstance = new Chart(ctx, chartConfig);
    }
});

function getChartConfig({ years, cumulative, corpus, swp }) {
    const gridColor = 'rgba(255, 255, 255, 0.1)';
    const textColor = '#e2e8f0';

    return {
        type: 'line',
        data: {
            labels: years,
            datasets: [
                {
                    label: 'Total Invested',
                    data: cumulative,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Corpus Value',
                    data: corpus,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Annual Withdrawal',
                    data: swp,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointBackgroundColor: '#ef4444',
                    pointRadius: 0,
                    pointHoverRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                x: {
                    grid: {
                        color: gridColor,
                    },
                    ticks: {
                        color: textColor,
                    },
                    title: {
                        display: true,
                        text: 'Year',
                        color: textColor,
                    }
                },
                y: {
                    grid: {
                        color: gridColor,
                    },
                    ticks: {
                        color: textColor,
                        callback: function(value) {
                            if (value >= 10000000) return (value / 10000000).toFixed(2) + ' Cr';
                            if (value >= 100000) return (value / 100000).toFixed(2) + ' L';
                            if (value >= 1000) return (value / 1000).toFixed(2) + ' K';
                            return value;
                        }
                    },
                    title: {
                        display: true,
                        text: 'Amount (INR)',
                        color: textColor,
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: textColor,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    };
}