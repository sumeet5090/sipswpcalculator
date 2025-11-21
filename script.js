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
            animation: false, // Important for consistent chart rendering for PDF
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