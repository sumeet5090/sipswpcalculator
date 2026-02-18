// State
let currentCurrency = 'INR';
const currencyConfig = {
    'INR': { locale: 'en-IN', symbol: '₹' },
    'USD': { locale: 'en-US', symbol: '$' },
    'EUR': { locale: 'en-IE', symbol: '€' }
};

function updateCurrency(newCurrency) {
    currentCurrency = newCurrency;
    // Re-render any displayed values that use formatCurrency
    // For example, if you have a results display function:
    calculateAndRender();

    // Update all symbol spans
    const spans = document.querySelectorAll('.currency-symbol');
    spans.forEach(span => {
        span.textContent = currencyConfig[currentCurrency].symbol;
    });
}

function formatCurrency(value) {
    const config = currencyConfig[currentCurrency];
    return new Intl.NumberFormat(config.locale, {
        style: 'currency',
        currency: currentCurrency,
        maximumFractionDigits: 0
    }).format(value);
}

// 2. Donut Chart Logic
function updateAllocationChart(data) {
    const ctx = document.getElementById('allocationChart');
    if (!ctx) return;

    const lastRow = data[data.length - 1];
    const invested = lastRow.cumulative_invested;
    const gains = (lastRow.combined_total + (lastRow.cumulative_withdrawals || 0)) - invested;

    if (!window.allocationChartInstance) {
        window.allocationChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Invested', 'Gains'],
                datasets: [{
                    data: [invested, gains],
                    backgroundColor: ['#334155', '#10b981'], // Slate 700, Emerald 500
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false } // Minimalist
                }
            }
        });
    } else {
        window.allocationChartInstance.data.datasets[0].data = [invested, gains];
        window.allocationChartInstance.update('none');
    }
}

// 3. Timestamp
function updateTimestamp() {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    const dateStr = now.toLocaleDateString('en-IN', options);
    const el = document.getElementById('last-updated');
    if (el) el.textContent = dateStr;
}

// Fixed formatAxisTick
function formatAxisTick(value) {
    const symbol = currencyConfig[currentCurrency].symbol;
    if (currentCurrency === 'INR') {
        if (value >= 10000000) return symbol + (value / 10000000).toFixed(1) + 'Cr';
        if (value >= 100000) return symbol + (value / 100000).toFixed(1) + 'L';
        if (value >= 1000) return symbol + (value / 1000).toFixed(1) + 'k';
        return symbol + value;
    } else {
        if (value >= 1000000000) return symbol + (value / 1000000000).toFixed(1) + 'B';
        if (value >= 1000000) return symbol + (value / 1000000).toFixed(1) + 'M';
        if (value >= 1000) return symbol + (value / 1000).toFixed(1) + 'k';
        return symbol + value;
    }
}

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

    // Initialize Range Sliders
    setupRangeSliders();
});



// --- Core Calculation Logic ---
function calculateAndRender() {
    const inputs = getInputs();
    const data = calculateCorpus(inputs);
    updateChart(data);
    updateTable(data, inputs.enable_swp);
    updateSummaryMetrics(data);
}

function getInputs() {
    const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

    return {
        sip: clamp(parseFloat(document.getElementById('sip').value) || 0, 500, 1000000),
        years: clamp(parseInt(document.getElementById('years').value) || 0, 1, 50),
        rate: clamp(parseFloat(document.getElementById('rate').value) || 0, 1, 30),
        stepup: clamp(parseFloat(document.getElementById('stepup').value) || 0, 0, 50),
        enable_swp: document.getElementById('enable_swp').checked,
        swp_withdrawal: clamp(parseFloat(document.getElementById('swp_withdrawal').value) || 0, 0, 1000000),
        swp_stepup: clamp(parseFloat(document.getElementById('swp_stepup').value) || 0, 0, 20),
        swp_years: clamp(parseInt(document.getElementById('swp_years').value) || 0, 0, 50)
    };
}

function calculateCorpus(inp) {
    const monthlyRate = inp.rate / 100 / 12;
    const swpStartYear = inp.years + 1;
    const totalYears = inp.years + inp.swp_years;

    let netBalance = 0.0;
    let cumulativeInvested = 0.0;
    let cumulativeWithdrawals = 0.0;

    const results = [];

    for (let y = 1; y <= totalYears; y++) {
        // Monthly SIP amount for this year
        let monthlySip = 0.0;
        if (y <= inp.years) {
            monthlySip = inp.sip * Math.pow(1 + inp.stepup / 100, y - 1);
        }

        // Monthly SWP amount for this year
        let monthlySwp = 0.0;
        if (inp.enable_swp && y >= swpStartYear) {
            monthlySwp = inp.swp_withdrawal * Math.pow(1 + inp.swp_stepup / 100, y - swpStartYear);
        }

        let actualYearWithdrawn = 0.0;
        let annualContribution = monthlySip * 12; // Approximation, actual is sum of months
        // Recalculate annual contribution precisely if needed, but stepup is annual.
        // PHP logic: monthly_sip is constant for the year.

        let yearBegin = netBalance;

        for (let m = 1; m <= 12; m++) {
            let contrib = (y <= inp.years) ? monthlySip : 0.0;
            let potentialBalance = netBalance + contrib;

            let withdraw = 0.0;
            if (inp.enable_swp && y >= swpStartYear && monthlySwp > 0) {
                withdraw = Math.min(monthlySwp, potentialBalance); // No negative balance
            }

            actualYearWithdrawn += withdraw;
            netBalance = (netBalance + contrib - withdraw) * (1 + monthlyRate);
            if (netBalance < 0) netBalance = 0;
        }

        cumulativeInvested += annualContribution;
        if (inp.enable_swp && y >= swpStartYear) {
            cumulativeWithdrawals += actualYearWithdrawn;
        }

        let annualWithdrawal = actualYearWithdrawn;
        let interestEarned = netBalance - (yearBegin + annualContribution - annualWithdrawal);

        results.push({
            year: y,
            begin_balance: Math.round(yearBegin),
            sip_monthly: (y <= inp.years) ? monthlySip : null,
            annual_contribution: annualContribution,
            cumulative_invested: cumulativeInvested,
            swp_monthly: (inp.enable_swp && y >= swpStartYear) ? monthlySwp : null,
            annual_withdrawal: (inp.enable_swp && y >= swpStartYear) ? annualWithdrawal : null,
            cumulative_withdrawals: (inp.enable_swp && y >= swpStartYear) ? cumulativeWithdrawals : 0,
            interest: Math.round(interestEarned),
            combined_total: Math.round(netBalance)
        });
    }

    return results;
}

// --- Update UI ---
function updateChart(data) {
    if (!window.corpusChart) return;

    const yearsLabels = data.map(r => r.year);
    const investedData = data.map(r => r.cumulative_invested);
    const corpusData = data.map(r => r.combined_total);
    const swpData = data.map(r => r.annual_withdrawal || 0);

    window.corpusChart.data.labels = yearsLabels;
    window.corpusChart.data.datasets[0].data = investedData;
    window.corpusChart.data.datasets[1].data = corpusData;
    window.corpusChart.data.datasets[2].data = swpData;

    window.corpusChart.update('none'); // 'none' mode for smooth animation
}

function updateTable(data, enableSwp) {
    const tbody = document.getElementById('breakdown-body');
    if (!tbody) return;

    tbody.innerHTML = ''; // Clear current rows

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-white/5 border-b border-white/5 transition-colors text-slate-300";

        // Helper for currency
        const fmt = (v) => v !== null ? formatCurrency(v) : '-';

        // SWP Columns HTML
        let swpCols = '';
        if (enableSwp) {
            swpCols = `
                <td class="px-6 py-4 text-right text-rose-400 font-medium">${fmt(row.annual_withdrawal)}</td>
                <td class="px-6 py-4 text-right text-slate-400">${fmt(row.cumulative_withdrawals)}</td>
            `;
        }

        tr.innerHTML = `
            <td class="px-6 py-4 font-medium text-slate-400">${row.year}</td>
            <td class="px-6 py-4 text-right">${formatCurrency(row.begin_balance)}</td>
            <td class="px-6 py-4 text-right text-emerald-400">${formatCurrency(row.annual_contribution)}</td>
            <td class="px-6 py-4 text-right text-slate-400">${formatCurrency(row.cumulative_invested)}</td>
            ${swpCols}
            <td class="px-6 py-4 text-right text-emerald-400 font-medium">${formatCurrency(row.interest)}</td>
            <td class="px-6 py-4 text-right font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">${formatCurrency(row.combined_total)}</td>
        `;

        tbody.appendChild(tr);
    });
}

function updateSummaryMetrics(data) {
    if (!data || data.length === 0) return;

    const lastRow = data[data.length - 1];

    // Calculate totals
    // Note: total invested is cumulative_invested of last row
    // Interest is calculated as Final Value - Invested + Withdrawn? 
    // Or from row directly?
    // In our logic: 
    // Interest = NetBalance - (YearBegin + AnnualContrib - AnnualWithdrawal) is for ONE year.
    // Total Interest = NetBalance + TotalWithdrawals - TotalInvested.

    const totalInvested = lastRow.cumulative_invested;
    const finalCorpus = lastRow.combined_total;
    const totalWithdrawn = lastRow.cumulative_withdrawals || 0;

    // Total Gains = (Final Value + Total Withdrawn) - Total Invested
    const totalGains = (finalCorpus + totalWithdrawn) - totalInvested;

    // Update DOM
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = formatCurrency(val);
    };

    setVal('summary-invested', totalInvested);
    setVal('summary-interest', totalGains);
    setVal('summary-withdrawn', totalWithdrawn);
    setVal('summary-corpus', finalCorpus);
}

// Setup listeners
function setupRangeSliders() {
    const pairs = [
        { range: 'sip_range', input: 'sip' },
        { range: 'years_range', input: 'years' },
        { range: 'rate_range', input: 'rate' },
        { range: 'stepup_range', input: 'stepup' },
        { range: 'swp_withdrawal_range', input: 'swp_withdrawal' },
        { range: 'swp_stepup_range', input: 'swp_stepup' },
        { range: 'swp_years_range', input: 'swp_years' }
    ];

    pairs.forEach(pair => {
        const range = document.getElementById(pair.range);
        const input = document.getElementById(pair.input);

        if (range && input) {
            // Range listener
            range.addEventListener('input', () => {
                input.value = range.value;
                calculateAndRender(); // Trigger calculation
            });

            // Input listener
            input.addEventListener('input', () => {
                range.value = input.value;
                calculateAndRender(); // Trigger calculation
            });
        }
    });

    // Also listen to SWP toggle
    const swpToggle = document.getElementById('enable_swp');
    if (swpToggle) {
        // Sync state immediately on load
        toggleSwpFields();
        swpToggle.addEventListener('change', () => {
            toggleSwpFields(); // existing visual toggle
            calculateAndRender(); // Re-calculate
        });
    }
}

function getChartConfig({ years, cumulative, corpus, swp }) {
    const ctx = document.getElementById('corpusChart').getContext('2d');

    // Gradients & Colors matching styles.css
    const gradientInvested = ctx.createLinearGradient(0, 0, 0, 400);
    gradientInvested.addColorStop(0, 'rgba(51, 65, 85, 0.5)'); // Slate 700
    gradientInvested.addColorStop(1, 'rgba(51, 65, 85, 0.1)');

    const gradientCorpus = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCorpus.addColorStop(0, 'rgba(16, 185, 129, 0.6)'); // Emerald 500
    gradientCorpus.addColorStop(1, 'rgba(16, 185, 129, 0.1)');

    const fontFamily = "'Plus Jakarta Sans', sans-serif";
    const gridColor = 'rgba(255, 255, 255, 0.08)';
    const textColor = '#94a3b8'; // Slate 400

    return {
        type: 'line',
        data: {
            labels: years,
            datasets: [
                {
                    label: 'Total Invested',
                    data: cumulative,
                    borderColor: '#94a3b8', // Slate 400
                    backgroundColor: gradientInvested,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: 'origin',
                    pointBackgroundColor: '#0f172a', // Slate 900
                    pointBorderColor: '#94a3b8',
                    pointRadius: 0,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Wealth Gained',
                    data: corpus,
                    borderColor: '#10b981', // Emerald 500
                    backgroundColor: gradientCorpus,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: 0, // Fill to dataset 0
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                },
                {
                    label: 'Annual Withdrawal',
                    data: swp,
                    borderColor: '#fb7185', // Rose 400
                    backgroundColor: 'rgba(251, 113, 133, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#fb7185',
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
                    backgroundColor: 'rgba(15, 23, 42, 0.95)', // Slate 900
                    titleColor: '#f8fafc', // Slate 50
                    titleFont: {
                        family: fontFamily,
                        size: 14,
                        weight: 'bold'
                    },
                    bodyColor: '#cbd5e1', // Slate 300
                    bodyFont: {
                        family: fontFamily,
                        size: 13
                    },
                    borderColor: '#334155', // Slate 700
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
                            if (value >= 10000000) return '₹' + (value / 10000000).toFixed(1) + 'Cr';
                            if (value >= 100000) return '₹' + (value / 100000).toFixed(1) + 'L';
                            return '₹' + (value / 1000) + 'k';
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
        fields.style.display = 'block';
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

        // Wait for fade out to complete before display:none
        // But since we want immediate feel, let's keep it simple or use timeout
        fields.style.display = 'none';

        // Hide SWP dataset in chart
        if (window.corpusChart) {
            window.corpusChart.data.datasets.forEach(ds => {
                if (ds.label === 'Annual Withdrawal') ds.hidden = true;
            });
            window.corpusChart.update();
        }
    }
}

/**
 * Adjusts the value of an input field by a given delta.
 * @param {string} elementId - The ID of the input element.
 * @param {number} delta - The amount to add (can be negative).
 */
function adjustValue(elementId, delta) {
    const input = document.getElementById(elementId);
    if (!input) return;

    let currentValue = parseFloat(input.value) || 0;
    let newValue = currentValue + delta;

    // Enforce min/max if defined
    if (input.min !== '' && newValue < parseFloat(input.min)) {
        newValue = parseFloat(input.min);
    }
    if (input.max !== '' && newValue > parseFloat(input.max)) {
        newValue = parseFloat(input.max);
    }

    input.value = newValue;

    // Trigger input event to update slider and calculations
    input.dispatchEvent(new Event('input'));

    // Also update the slider explicitly if it exists
    const slider = document.getElementById(elementId + '_range');
    if (slider) {
        slider.value = newValue;
        // Visual feedback on slider track if we were using custom BG, 
        // but for now the native input event handling in main script should cover it
    }
}
