document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Function to set a cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Set initial icon state based on the `dark` class on `<html>`
    if (document.documentElement.classList.contains('dark')) {
        themeToggleLightIcon.classList.remove('hidden');
        themeToggleDarkIcon.classList.add('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
        themeToggleLightIcon.classList.add('hidden');
    }

    themeToggleBtn.addEventListener('click', function () {
        // Toggle the 'dark' class on the root HTML element
        const isDarkMode = document.documentElement.classList.toggle('dark');

        // Update theme in localStorage and as a cookie
        const newTheme = isDarkMode ? 'dark' : 'light';
        localStorage.setItem('theme', newTheme);
        setCookie('theme', newTheme, 365); // Set a cookie for 1 year

        // Toggle the visibility of the theme icons
        themeToggleDarkIcon.classList.toggle('hidden', isDarkMode);
        themeToggleLightIcon.classList.toggle('hidden', !isDarkMode);

        // Update the chart to reflect the theme change
        if (window.corpusChartInstance) {
            updateChartTheme(window.corpusChartInstance);
        }
    });

    // Chart.js
    const ctx = document.getElementById('corpusChart');
    if (ctx && window.chartData && window.chartData.years.length > 0) {
        const chartConfig = getChartConfig(window.chartData);
        window.corpusChartInstance = new Chart(ctx, chartConfig);
    }
});

function getChartConfig({ years, cumulative, corpus, swp }) {
    const isDarkMode = document.documentElement.classList.contains('dark');
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const textColor = isDarkMode ? '#e2e8f0' : '#334155';
    const chartContainer = document.getElementById('corpusChart').parentElement.parentElement;
    chartContainer.style.backgroundColor = isDarkMode ? 'rgba(30, 41, 59, 0.5)' : 'white';

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

function updateChartTheme(chart) {
    const isDarkMode = document.documentElement.classList.contains('dark');
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const textColor = isDarkMode ? '#e2e8f0' : '#334155';
    const chartContainer = document.getElementById('corpusChart').parentElement.parentElement;
    chartContainer.style.backgroundColor = isDarkMode ? 'rgba(30, 41, 59, 0.5)' : 'white';

    chart.options.scales.x.grid.color = gridColor;
    chart.options.scales.x.ticks.color = textColor;
    chart.options.scales.x.title.color = textColor;
    chart.options.scales.y.grid.color = gridColor;
    chart.options.scales.y.ticks.color = textColor;
    chart.options.scales.y.title.color = textColor;
    chart.options.plugins.legend.labels.color = textColor;
    
    chart.update();
}