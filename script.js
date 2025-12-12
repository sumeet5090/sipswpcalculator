document.addEventListener('DOMContentLoaded', () => {
    // Chart.js
    const ctx = document.getElementById('corpusChart');
    if (ctx && window.chartData && window.chartData.years.length > 0) {
        const chartConfig = getChartConfig(window.chartData);
        // Make the chart instance globally accessible
        window.corpusChart = new Chart(ctx, chartConfig);
    }

    // PDF Modal Logic
    const pdfModal = document.getElementById('pdfModal');
    const openPdfModalBtn = document.getElementById('openPdfModalBtn');
    const closePdfModalBtn = document.getElementById('closePdfModalBtn');
    const pdfForm = document.getElementById('pdfForm');

    if (openPdfModalBtn) {
        openPdfModalBtn.addEventListener('click', () => {
            if (!window.corpusChart) {
                alert('Please calculate the results first before generating a report.');
                return;
            }
            pdfModal.classList.remove('hidden');
        });
    }

    if (closePdfModalBtn) {
        closePdfModalBtn.addEventListener('click', () => {
            pdfModal.classList.add('hidden');
        });
    }

    if (pdfModal) {
        pdfModal.addEventListener('click', (e) => {
            if (e.target === pdfModal) {
                pdfModal.classList.add('hidden');
            }
        });
    }

    if (pdfForm) {
        pdfForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const generatePdfBtn = document.getElementById('generatePdfBtn');
            generatePdfBtn.disabled = true;
            generatePdfBtn.textContent = 'Generating...';

            // 1. Collect all data
            const chartDataURL = window.corpusChart.toBase64Image();
            const tableHtml = document.getElementById('results-table')?.innerHTML || 'No table data available.';

            const formData = new FormData(pdfForm);

            // Append calculator inputs
            formData.append('sip', document.getElementById('sip').value);
            formData.append('years', document.getElementById('years').value);
            formData.append('rate', document.getElementById('rate').value);
            formData.append('stepup', document.getElementById('stepup').value);
            formData.append('swp_withdrawal', document.getElementById('swp_withdrawal').value);
            formData.append('swp_stepup', document.getElementById('swp_stepup').value);
            formData.append('swp_years', document.getElementById('swp_years').value);

            // Append chart and table data
            formData.append('chartData', chartDataURL);
            formData.append('tableHtml', tableHtml);

            // 2. Fetch request to the server
            fetch('generate-pdf.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.ok) {
                        return response.blob();
                    }
                    throw new Error('PDF generation failed.');
                })
                .then(blob => {
                    // 3. Trigger download
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `Financial_Report_for_${formData.get('clientName') || 'Client'}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    // Reset button and close modal
                    generatePdfBtn.disabled = false;
                    generatePdfBtn.textContent = 'Download PDF';
                    pdfModal.classList.add('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while generating the PDF. Please check the console.');
                    generatePdfBtn.disabled = false;
                    generatePdfBtn.textContent = 'Download PDF';
                });
        });
    }
});

function getChartConfig({ years, cumulative, corpus, swp }) {
    const ctx = document.getElementById('corpusChart').getContext('2d');

    // Gradients
    const gradientInvested = ctx.createLinearGradient(0, 0, 0, 400);
    gradientInvested.addColorStop(0, 'rgba(168, 85, 247, 0.4)'); // Purple 500
    gradientInvested.addColorStop(1, 'rgba(168, 85, 247, 0.0)');

    const gradientCorpus = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCorpus.addColorStop(0, 'rgba(79, 70, 229, 0.4)'); // Indigo 600
    gradientCorpus.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

    const fontFamily = "'Plus Jakarta Sans', sans-serif";
    const gridColor = 'rgba(0, 0, 0, 0.03)';
    const textColor = '#6b7280'; // Gray 500

    return {
        type: 'line',
        data: {
            labels: years,
            datasets: [
                {
                    label: 'Total Invested',
                    data: cumulative,
                    borderColor: '#a855f7', // Purple 500
                    backgroundColor: gradientInvested,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#a855f7',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                },
                {
                    label: 'Corpus Value',
                    data: corpus,
                    borderColor: '#4f46e5', // Indigo 600
                    backgroundColor: gradientCorpus,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBorderWidth: 3,
                },
                {
                    label: 'Annual Withdrawal',
                    data: swp,
                    borderColor: '#f43f5e', // Rose 500
                    backgroundColor: 'rgba(244, 63, 94, 0.1)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    tension: 0.4,
                    fill: false,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#f43f5e',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 6,
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
                        color: '#374151', // Gray 700
                        font: {
                            family: fontFamily,
                            size: 12,
                            weight: 600
                        },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#111827',
                    titleFont: {
                        family: fontFamily,
                        size: 14,
                        weight: 'bold'
                    },
                    bodyColor: '#4b5563',
                    bodyFont: {
                        family: fontFamily,
                        size: 13
                    },
                    borderColor: '#e5e7eb',
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
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            family: fontFamily,
                            size: 11
                        }
                    },
                },
                y: {
                    grid: {
                        color: gridColor,
                        borderDash: [4, 4],
                        drawBorder: false,
                    },
                    ticks: {
                        color: textColor,
                        font: {
                            family: fontFamily,
                            size: 11
                        },
                        callback: function (value) {
                            if (value >= 1000000000) return '$' + (value / 1000000000).toFixed(1) + 'B';
                            if (value >= 1000000) return '$' + (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return '$' + (value / 1000).toFixed(1) + 'k';
                            return '$' + value;
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    };
}

function toggleSwpFields() {
    const isChecked = document.getElementById('enable_swp').checked;
    const fields = document.getElementById('swp-fields');

    // Safety check if elements exist
    if (!fields) return;

    if (isChecked) {
        fields.style.display = 'grid';
        setTimeout(() => {
            fields.style.opacity = '1';
        }, 10);
        fields.style.pointerEvents = 'auto';

        // Show SWP dataset in chart if it exists
        if (window.corpusChart) {
            window.corpusChart.data.datasets.forEach(ds => {
                if (ds.label === 'Annual Withdrawal') ds.hidden = false;
            });
            window.corpusChart.update();
        }
    } else {
        fields.style.opacity = '0.5'; // Fade out first
        fields.style.pointerEvents = 'none';
        fields.style.display = 'none'; // Then hide

        // Hide SWP dataset in chart
        if (window.corpusChart) {
            window.corpusChart.data.datasets.forEach(ds => {
                if (ds.label === 'Annual Withdrawal') ds.hidden = true;
            });
            window.corpusChart.update();
        }
    }
}
