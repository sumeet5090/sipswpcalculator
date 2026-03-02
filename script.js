// State
let currentCurrency = 'INR';
const currencyConfig = {
    'INR': { locale: 'en-IN', symbol: '₹' },
    'USD': { locale: 'en-US', symbol: '$' },
    'EUR': { locale: 'en-US', symbol: '€' },
    'GBP': { locale: 'en-GB', symbol: '£' }
};

function updateCurrency(newCurrency) {
    currentCurrency = newCurrency;

    // Update all symbol spans in the form
    const spans = document.querySelectorAll('.currency-symbol');
    spans.forEach(span => {
        span.textContent = currencyConfig[currentCurrency].symbol;
    });

    // Toggle active button styling
    document.querySelectorAll('.currency-btn').forEach(btn => {
        if (btn.dataset.currency === newCurrency) {
            btn.classList.add('bg-indigo-600', 'text-white');
            btn.classList.remove('bg-white', 'text-slate-500', 'hover:bg-slate-50');
        } else {
            btn.classList.remove('bg-indigo-600', 'text-white');
            btn.classList.add('bg-white', 'text-slate-500', 'hover:bg-slate-50');
        }
    });

    // Re-render chart and table with new currency
    calculateAndRender();

    // Force chart axis labels to refresh with new symbol
    if (window.corpusChart) {
        window.corpusChart.update();
    }
}

// ── Tab switching ──────────────────────────────────────────────────────────
function switchFormTab(tab) {
    const sipPanel = document.getElementById('panel-sip');
    const swpPanel = document.getElementById('panel-swp');
    const sipTab = document.getElementById('tab-sip');
    const swpTab = document.getElementById('tab-swp');

    if (tab === 'sip') {
        // Show SIP panel
        sipPanel.classList.remove('hidden');
        swpPanel.classList.add('hidden');
        // Active styles for SIP tab
        sipTab.classList.add('bg-emerald-500', 'text-white');
        sipTab.classList.remove('bg-white', 'text-slate-400');
        sipTab.querySelector('span').classList.add('bg-white/20');
        sipTab.querySelector('span').classList.remove('bg-slate-100');
        // Inactive styles for SWP tab
        swpTab.classList.add('bg-white', 'text-slate-400');
        swpTab.classList.remove('bg-rose-500', 'text-white');
        swpTab.querySelector('span').classList.add('bg-slate-100');
        swpTab.querySelector('span').classList.remove('bg-white/20');
        sipTab.setAttribute('aria-selected', 'true');
        swpTab.setAttribute('aria-selected', 'false');
    } else {
        // Show SWP panel
        swpPanel.classList.remove('hidden');
        sipPanel.classList.add('hidden');
        // Active styles for SWP tab
        swpTab.classList.add('bg-rose-500', 'text-white');
        swpTab.classList.remove('bg-white', 'text-slate-400');
        swpTab.querySelector('span').classList.add('bg-white/20');
        swpTab.querySelector('span').classList.remove('bg-slate-100');
        // Inactive styles for SIP tab
        sipTab.classList.add('bg-white', 'text-slate-400');
        sipTab.classList.remove('bg-emerald-500', 'text-white');
        sipTab.querySelector('span').classList.add('bg-slate-100');
        sipTab.querySelector('span').classList.remove('bg-white/20');
        swpTab.setAttribute('aria-selected', 'true');
        sipTab.setAttribute('aria-selected', 'false');
    }
}

// Initialise tab on page load
document.addEventListener('DOMContentLoaded', function () {
    switchFormTab('sip'); // default to SIP tab
});

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
                    backgroundColor: ['#cbd5e1', '#10b981'], // Slate 300, Emerald 500
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
        const enableSwp = document.getElementById('enable_swp')?.checked || false;
        const chartConfig = getChartConfig(window.chartData, enableSwp);
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

    // ── Restore from shared URL params ────────────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sip')) {
        const paramMap = {
            'sip': 'sip', 'years': 'years', 'rate': 'rate', 'stepup': 'stepup',
            'swp': 'swp_withdrawal', 'swp_years': 'swp_years', 'swp_stepup': 'swp_stepup'
        };
        for (const [param, inputId] of Object.entries(paramMap)) {
            if (urlParams.has(param)) {
                const el = document.getElementById(inputId);
                if (el) el.value = urlParams.get(param);
                const rangeEl = document.getElementById(inputId + '_range');
                if (rangeEl) rangeEl.value = urlParams.get(param);
            }
        }
        if (urlParams.get('swp_on') === '1') {
            const toggle = document.getElementById('enable_swp');
            if (toggle) { toggle.checked = true; toggleSwpFields(); }
        }
        if (urlParams.has('cur')) {
            updateCurrency(urlParams.get('cur'));
        }
    }

    // Initial calculation on page load (syncs JS table/chart with PHP defaults)
    calculateAndRender();

    // ── Share button ─────────────────────────────────────────────────
    const shareBtn = document.getElementById('shareCalcBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            const inputs = getInputs();
            const params = new URLSearchParams();
            params.set('sip', inputs.sip);
            params.set('years', inputs.years);
            params.set('rate', inputs.rate);
            params.set('stepup', inputs.stepup);
            params.set('cur', currentCurrency);
            if (inputs.enable_swp) {
                params.set('swp_on', '1');
                params.set('swp', inputs.swp_withdrawal);
                params.set('swp_years', inputs.swp_years);
                params.set('swp_stepup', inputs.swp_stepup);
            }
            const shareUrl = window.location.origin + '/?' + params.toString();

            navigator.clipboard.writeText(shareUrl).then(() => {
                const btnText = document.getElementById('shareBtnText');
                btnText.textContent = 'Copied!';
                shareBtn.classList.remove('text-indigo-600', 'border-indigo-200');
                shareBtn.classList.add('text-emerald-600', 'border-emerald-300', 'bg-emerald-50');
                setTimeout(() => {
                    btnText.textContent = 'Share';
                    shareBtn.classList.add('text-indigo-600', 'border-indigo-200');
                    shareBtn.classList.remove('text-emerald-600', 'border-emerald-300', 'bg-emerald-50');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                prompt('Copy this link:', shareUrl);
            });
        });
    }

    // Re-fit summary cards on resize (debounced to prevent excessive reflows)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'].forEach(id => {
                const el = document.getElementById(id);
                if (el) delete el.dataset.baseFont;
            });
            fitSummaryCards();
        }, 150);
    });
});



// --- Core Calculation Logic ---
function calculateAndRender() {
    const inputs = getInputs();
    const data = calculateCorpus(inputs);
    updateChart(data, inputs.enable_swp);
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
    const totalYears = inp.enable_swp ? (inp.years + inp.swp_years) : inp.years;

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
function updateChart(data, enableSwp) {
    if (!window.corpusChart) return;

    const yearsLabels = data.map(r => 'Year ' + r.year);
    const investedData = data.map(r => r.cumulative_invested);
    const corpusData = data.map(r => r.combined_total);
    const swpData = data.map(r => r.annual_withdrawal || 0);

    window.corpusChart.data.labels = yearsLabels;
    window.corpusChart.data.datasets[0].data = investedData;
    window.corpusChart.data.datasets[1].data = corpusData;
    window.corpusChart.data.datasets[2].data = swpData;
    window.corpusChart.data.datasets[2].hidden = !enableSwp;

    window.corpusChart.update();
}

function updateTable(data, enableSwp) {
    const tbody = document.getElementById('breakdown-body');
    if (!tbody) return;

    // Build all rows in a DocumentFragment (off-DOM) to trigger only ONE reflow
    const fragment = document.createDocumentFragment();

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 border-b border-slate-100 transition-colors";

        // Helper for currency
        const fmt = (v) => v !== null ? formatCurrency(v) : '-';

        // SWP Columns HTML
        let swpCols = '';
        if (enableSwp) {
            swpCols = `
                <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap">${fmt(row.annual_withdrawal)}</td>
                <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap">${fmt(row.cumulative_withdrawals)}</td>
            `;
        }

        tr.innerHTML = `
            <td class="px-6 py-4 font-medium text-slate-700 whitespace-nowrap">${row.year}</td>
            <td class="px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap">${formatCurrency(row.begin_balance)}</td>
            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${formatCurrency(row.annual_contribution)}</td>
            <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap">${formatCurrency(row.cumulative_invested)}</td>
            ${swpCols}
            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${formatCurrency(row.interest)}</td>
            <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap">${formatCurrency(row.combined_total)}</td>
        `;

        fragment.appendChild(tr);
    });

    // Single DOM operation: clear + append
    tbody.innerHTML = '';
    tbody.appendChild(fragment);
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

    // Auto-scale card values to fit
    fitSummaryCards();
}

// Scale down summary card values when text overflows the card
// OPTIMIZED: batch all DOM reads, then all DOM writes (no forced reflow)
function fitSummaryCards() {
    const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
    const elements = ids.map(id => document.getElementById(id)).filter(Boolean);
    if (elements.length === 0) return;

    // ── WRITE PASS 1: Reset all elements to base state ──
    elements.forEach(el => {
        el.style.whiteSpace = 'nowrap';
        el.style.overflow = 'hidden';
        if (!el.dataset.baseFont) {
            el.style.fontSize = '';
        }
    });

    // ── READ PASS: Measure everything in one batch ──
    const measurements = elements.map(el => {
        if (!el.dataset.baseFont) {
            el.dataset.baseFont = getComputedStyle(el).fontSize;
        }
        const basePx = parseFloat(el.dataset.baseFont);
        el.style.fontSize = basePx + 'px';
        return { el, basePx };
    });

    // Force a single layout calculation for all elements at once
    const results = measurements.map(({ el, basePx }) => {
        const parent = el.parentElement;
        const cs = getComputedStyle(parent);
        const availableW = parent.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        const textW = el.scrollWidth;
        return { el, basePx, availableW, textW };
    });

    // ── WRITE PASS 2: Apply all computed font sizes ──
    results.forEach(({ el, basePx, availableW, textW }) => {
        if (textW > availableW && availableW > 0) {
            el.style.fontSize = Math.max((availableW / textW) * basePx, 10) + 'px';
        } else {
            el.style.fontSize = basePx + 'px';
        }
    });
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

function getChartConfig({ years, cumulative, corpus, swp }, enableSwp = true) {
    const ctx = document.getElementById('corpusChart').getContext('2d');

    // Gradients & Colors matching Light Theme
    const gradientInvested = ctx.createLinearGradient(0, 0, 0, 400);
    gradientInvested.addColorStop(0, 'rgba(79, 70, 229, 0.2)'); // Indigo 600 low opacity
    gradientInvested.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

    const gradientCorpus = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCorpus.addColorStop(0, 'rgba(16, 185, 129, 0.4)'); // Emerald 500
    gradientCorpus.addColorStop(1, 'rgba(16, 185, 129, 0.05)');

    const fontFamily = "'Plus Jakarta Sans', sans-serif";
    const gridColor = 'rgba(0, 0, 0, 0.05)'; // Subtle dark grid
    const textColor = '#64748b'; // Slate 500

    return {
        type: 'line',
        data: {
            labels: years,
            datasets: [
                {
                    label: 'Total Invested',
                    data: cumulative,
                    borderColor: '#6366f1', // Indigo 500
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
                    borderColor: '#10b981', // Emerald 500
                    backgroundColor: gradientCorpus,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: 0, // Fill to dataset 0
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
                    borderColor: '#f43f5e', // Rose 500
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
                            return formatAxisTick(value);
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

    // Toggle all .swp-col elements (thead columns + table body cells)
    document.querySelectorAll('.swp-col').forEach(el => {
        el.style.display = isChecked ? '' : 'none';
    });

    if (isChecked) {
        fields.style.display = 'block';
        setTimeout(() => {
            fields.style.opacity = '1';
        }, 10);
        fields.style.pointerEvents = 'auto';
    } else {
        fields.style.opacity = '0.5';
        fields.style.pointerEvents = 'none';
        fields.style.display = 'none';
    }

    // Full recalculation — updates chart, table, and summary cards
    calculateAndRender();
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
