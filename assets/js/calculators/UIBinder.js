/**
 * UIBinder.js
 * Orchestrates event listeners, DOM bindings, and Pub/Sub EventBus communications.
 */
import { eventBus } from '../utils/EventBus.js';
import { calculateCorpus } from './MathEngine.js';
import { formatCurrency } from './CurrencyHelper.js';
import { validateField } from './InputValidator.js';
import { updateChart, getChartInstance } from './ChartManager.js';
import { logDebouncedInsight } from './AnalyticsLogger.js';

let userHasInteracted = false;

// ── DOM Elements Cache ──
const elements = {
    sip: () => document.getElementById('sip'),
    sipRange: () => document.getElementById('sip_range'),
    years: () => document.getElementById('years'),
    yearsRange: () => document.getElementById('years_range'),
    rate: () => document.getElementById('rate'),
    rateRange: () => document.getElementById('rate_range'),
    stepup: () => document.getElementById('stepup'),
    stepupRange: () => document.getElementById('stepup_range'),
    enableSwp: () => document.getElementById('enable_swp'),
    swpFields: () => document.getElementById('swp-fields'),
    swpWithdrawal: () => document.getElementById('swp_withdrawal'),
    swpWithdrawalRange: () => document.getElementById('swp_withdrawal_range'),
    swpYears: () => document.getElementById('swp_years'),
    swpYearsRange: () => document.getElementById('swp_years_range'),
    swpStepup: () => document.getElementById('swp_stepup'),
    swpStepupRange: () => document.getElementById('swp_stepup_range'),
    tbody: () => document.getElementById('breakdown-body'),
    shareBtn: () => document.getElementById('shareCalcBtn'),
    shareBtnText: () => document.getElementById('shareBtnText'),
    rateNudgeBtn: () => document.getElementById('rate-nudge-btn'),
    rateNudgePopover: () => document.getElementById('rate-nudge-popover'),
    rateNudgeClose: () => document.getElementById('rate-nudge-close'),
    useIndiaRate: () => document.getElementById('use-india-rate'),
    useUsRate: () => document.getElementById('use-us-rate'),
    pdfModal: () => document.getElementById('pdfModal'),
    openPdfModalBtn: () => document.getElementById('openPdfModalBtn'),
    closePdfModalBtn: () => document.getElementById('closePdfModalBtn'),
    pdfForm: () => document.getElementById('pdfForm'),
    generatePdfBtn: () => document.getElementById('generatePdfBtn'),
    canvas: () => document.getElementById('corpusChart')
};

/**
 * Extract input parameters from form inputs and run them through validation boundaries.
 * @returns {object} validated inputs
 */
function getInputs() {
    return {
        sip: validateField('sip', elements.sip()?.value),
        years: validateField('years', elements.years()?.value),
        rate: validateField('rate', elements.rate()?.value),
        stepup: validateField('stepup', elements.stepup()?.value),
        enable_swp: elements.enableSwp()?.checked || false,
        swp_withdrawal: validateField('swp_withdrawal', elements.swpWithdrawal()?.value),
        swp_years: validateField('swp_years', elements.swpYears()?.value),
        swp_stepup: validateField('swp_stepup', elements.swpStepup()?.value)
    };
}

/**
 * Publish input changes to EventBus.
 */
function triggerCalculation() {
    const inputs = getInputs();
    eventBus.publish('input:changed', inputs);
}

/**
 * Synchronize a range slider with its numeric input box.
 * @param {string} inputId 
 * @param {string} rangeId 
 */
function setupRangeSync(inputId, rangeId) {
    const input = document.getElementById(inputId);
    const range = document.getElementById(rangeId);
    if (!input || !range) return;

    range.addEventListener('input', () => {
        input.value = range.value;
        userHasInteracted = true;
        triggerCalculation();
    });

    input.addEventListener('input', () => {
        range.value = input.value;
        userHasInteracted = true;
        triggerCalculation();
    });
}

/**
 * Handle formatting of large metric numbers to avoid text wrap.
 */
function fitSummaryCards() {
    const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
    const cardElms = ids.map(id => document.getElementById(id)).filter(Boolean);
    if (cardElms.length === 0) return;

    cardElms.forEach(el => {
        el.style.whiteSpace = 'nowrap';
        el.style.overflow = 'hidden';
        if (!el.dataset.baseFont) {
            el.dataset.baseFont = getComputedStyle(el).fontSize;
        }
        const basePx = parseFloat(el.dataset.baseFont);
        el.style.fontSize = basePx + 'px';
    });

    const results = cardElms.map(el => {
        const parent = el.parentElement;
        const cs = getComputedStyle(parent);
        const availableW = parent.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        const textW = el.scrollWidth;
        return { el, basePx: parseFloat(el.dataset.baseFont), availableW, textW };
    });

    results.forEach(({ el, basePx, availableW, textW }) => {
        if (textW > availableW && availableW > 0) {
            el.style.fontSize = Math.max((availableW / textW) * basePx, 10) + 'px';
        } else {
            el.style.fontSize = basePx + 'px';
        }
    });
}

/**
 * Draw year-by-year projections inside the layout table.
 * @param {Array} data 
 * @param {boolean} enableSwp 
 */
function updateTable(data, enableSwp) {
    const tbody = elements.tbody();
    if (!tbody) return;

    const fragment = document.createDocumentFragment();

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = "hover:bg-slate-50 border-b border-slate-100 transition-colors";

        const fmt = (v) => v !== null ? formatCurrency(v) : '-';

        let swpCols = '';
        if (enableSwp) {
            swpCols = `
                <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap">${fmt(row.swp_monthly)}</td>
                <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap">${fmt(row.annual_withdrawal)}</td>
                <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap">${fmt(row.cumulative_withdrawals)}</td>
            `;
        }

        tr.innerHTML = `
            <td class="px-6 py-4 font-medium text-slate-700 whitespace-nowrap">${row.year}</td>
            <td class="px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap">${formatCurrency(row.begin_balance)}</td>
            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${fmt(row.sip_monthly)}</td>
            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${formatCurrency(row.annual_contribution)}</td>
            <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap">${formatCurrency(row.cumulative_invested)}</td>
            ${swpCols}
            <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${formatCurrency(row.interest)}</td>
            <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap">${formatCurrency(row.combined_total)}</td>
        `;

        fragment.appendChild(tr);
    });

    tbody.innerHTML = '';
    tbody.appendChild(fragment);
}

/**
 * Update summary metric tiles.
 * @param {Array} data 
 */
function updateSummaryMetrics(data) {
    if (!data || data.length === 0) return;

    const lastRow = data[data.length - 1];
    const totalInvested = lastRow.cumulative_invested;
    const finalCorpus = lastRow.combined_total;
    const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
    const totalGains = (finalCorpus + totalWithdrawn) - totalInvested;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = formatCurrency(val);
    };

    setVal('summary-invested', totalInvested);
    setVal('summary-interest', totalGains);
    setVal('summary-withdrawn', totalWithdrawn);
    setVal('summary-corpus', finalCorpus);

    fitSummaryCards();
}

/**
 * Sync SWP toggle panel display rules.
 */
function syncSwpToggleState() {
    const isChecked = elements.enableSwp()?.checked || false;
    const fields = elements.swpFields();
    if (!fields) return;

    document.querySelectorAll('.swp-col').forEach(el => {
        el.style.display = isChecked ? '' : 'none';
    });

    if (isChecked) {
        fields.style.display = 'block';
        setTimeout(() => { fields.style.opacity = '1'; }, 10);
        fields.style.pointerEvents = 'auto';
    } else {
        fields.style.opacity = '0.5';
        fields.style.pointerEvents = 'none';
        fields.style.display = 'none';
    }

    triggerCalculation();
}

/**
 * Handle popover rate selections.
 * @param {number} val 
 */
function setSmartNudgeRate(val) {
    const rateEl = elements.rate();
    const rateRangeEl = elements.rateRange();
    const popover = elements.rateNudgePopover();

    if (rateEl) {
        rateEl.value = val;
        rateEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (rateRangeEl) {
        rateRangeEl.value = val;
        rateRangeEl.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (popover) {
        popover.classList.add('hidden');
        elements.rateNudgeBtn()?.setAttribute('aria-expanded', 'false');
    }
}

/**
 * Initialize all EventBus subscriptions and DOM bindings.
 */
export function initializeUI() {
    // ── Setup Range Sliders Sync ──
    setupRangeSync('sip', 'sip_range');
    setupRangeSync('years', 'years_range');
    setupRangeSync('rate', 'rate_range');
    setupRangeSync('stepup', 'stepup_range');
    setupRangeSync('swp_withdrawal', 'swp_withdrawal_range');
    setupRangeSync('swp_years', 'swp_years_range');
    setupRangeSync('swp_stepup', 'swp_stepup_range');

    // ── Bind SWP Toggler ──
    const swpToggle = elements.enableSwp();
    if (swpToggle) {
        swpToggle.addEventListener('change', syncSwpToggleState);
    }

    // ── Tab switcher bindings ──
    window.switchFormTab = (tab) => {
        const sipPanel = document.getElementById('panel-sip');
        const swpPanel = document.getElementById('panel-swp');
        const sipTab = document.getElementById('tab-sip');
        const swpTab = document.getElementById('tab-swp');

        if (!sipPanel || !swpPanel || !sipTab || !swpTab) return;

        if (tab === 'sip') {
            sipPanel.classList.remove('hidden');
            swpPanel.classList.add('hidden');
            sipTab.classList.add('bg-emerald-500', 'text-white');
            sipTab.classList.remove('bg-white', 'text-slate-500');
            sipTab.querySelector('span').classList.add('bg-white/20');
            sipTab.querySelector('span').classList.remove('bg-slate-100');
            swpTab.classList.add('bg-white', 'text-slate-500');
            swpTab.classList.remove('bg-rose-500', 'text-white');
            swpTab.querySelector('span').classList.add('bg-slate-100');
            swpTab.querySelector('span').classList.remove('bg-white/20');
            sipTab.setAttribute('aria-selected', 'true');
            swpTab.setAttribute('aria-selected', 'false');
        } else {
            swpPanel.classList.remove('hidden');
            sipPanel.classList.add('hidden');
            swpTab.classList.add('bg-rose-500', 'text-white');
            swpTab.classList.remove('bg-white', 'text-slate-500');
            swpTab.querySelector('span').classList.add('bg-white/20');
            swpTab.querySelector('span').classList.remove('bg-slate-100');
            sipTab.classList.add('bg-white', 'text-slate-500');
            sipTab.classList.remove('bg-emerald-500', 'text-white');
            sipTab.querySelector('span').classList.add('bg-slate-100');
            sipTab.querySelector('span').classList.remove('bg-white/20');
            swpTab.setAttribute('aria-selected', 'true');
            sipTab.setAttribute('aria-selected', 'false');
        }
    };

    // ── Smart Return Rate Nudge Bindings ──
    const nudgeBtn = elements.rateNudgeBtn();
    const nudgePopover = elements.rateNudgePopover();
    const nudgeClose = elements.rateNudgeClose();

    if (nudgeBtn && nudgePopover) {
        nudgeBtn.addEventListener('click', e => {
            e.stopPropagation();
            const isHidden = nudgePopover.classList.contains('hidden');
            nudgePopover.classList.toggle('hidden', !isHidden);
            nudgeBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
        if (nudgeClose) {
            nudgeClose.addEventListener('click', () => {
                nudgePopover.classList.add('hidden');
                nudgeBtn.setAttribute('aria-expanded', 'false');
            });
        }
        elements.useIndiaRate()?.addEventListener('click', () => setSmartNudgeRate(12));
        elements.useUsRate()?.addEventListener('click', () => setSmartNudgeRate(15));

        document.addEventListener('click', e => {
            if (!nudgePopover.contains(e.target) && e.target !== nudgeBtn) {
                nudgePopover.classList.add('hidden');
                nudgeBtn.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                nudgePopover.classList.add('hidden');
                nudgeBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── PDF Modal bindings ──
    const pdfModal = elements.pdfModal();
    const openPdfBtn = elements.openPdfModalBtn();
    const closePdfBtn = elements.closePdfModalBtn();
    const pdfForm = elements.pdfForm();

    if (openPdfBtn && pdfModal) {
        openPdfBtn.addEventListener('click', () => {
            if (!getChartInstance()) {
                alert('Please calculate the results first before generating a report.');
                return;
            }
            pdfModal.classList.remove('hidden');
        });
        if (closePdfBtn) {
            closePdfBtn.addEventListener('click', () => pdfModal.classList.add('hidden'));
        }
        pdfModal.addEventListener('click', e => {
            if (e.target === pdfModal) pdfModal.classList.add('hidden');
        });
    }

    if (pdfForm) {
        pdfForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const generatePdfBtn = elements.generatePdfBtn();
            if (generatePdfBtn) {
                generatePdfBtn.disabled = true;
                generatePdfBtn.textContent = 'Generating...';
            }

            const chartInstance = getChartInstance();
            const chartDataURL = chartInstance ? chartInstance.toBase64Image() : '';
            const resultsTable = document.getElementById('results-table');
            const tableHtml = resultsTable ? resultsTable.outerHTML : '<table><tr><td>No data available.</td></tr></table>';

            const formData = new FormData(pdfForm);
            formData.append('sip', elements.sip().value);
            formData.append('years', elements.years().value);
            formData.append('rate', elements.rate().value);
            formData.append('stepup', elements.stepup().value);
            formData.append('swp_withdrawal', elements.swpWithdrawal().value);
            formData.append('swp_stepup', elements.swpStepup().value);
            formData.append('swp_years', elements.swpYears().value);

            formData.append('currency_symbol', '₹');
            formData.append('summary_invested', document.getElementById('summary-invested')?.textContent.trim() || '0');
            formData.append('summary_interest', document.getElementById('summary-interest')?.textContent.trim() || '0');
            formData.append('summary_withdrawn', document.getElementById('summary-withdrawn')?.textContent.trim() || '0');
            formData.append('summary_corpus', document.getElementById('summary-corpus')?.textContent.trim() || '0');

            formData.append('chartData', chartDataURL);
            formData.append('tableHtml', tableHtml);

            fetch('generate-pdf.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (res.ok) return res.blob();
                throw new Error('PDF generation failed.');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `Financial_Report_for_${formData.get('clientName') || 'Client'}.pdf`;
                document.body.appendChild(a);
                a.click();

                if (generatePdfBtn) {
                    generatePdfBtn.disabled = false;
                    generatePdfBtn.textContent = 'Download PDF';
                }
                pdfModal.classList.add('hidden');
                a.remove();

                // Log PDF download analytics
                const inputs = getInputs();
                fetch('/log_insight', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        calc_type: inputs.enable_swp ? 'SWP' : 'SIP',
                        amount: inputs.enable_swp ? inputs.swp_withdrawal : inputs.sip,
                        duration: inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years,
                        step_up_pct: inputs.enable_swp ? inputs.swp_stepup : inputs.stepup,
                        currency: 'INR',
                        pdf_downloaded: true,
                        interest_rate: inputs.rate,
                        sip_amount: inputs.sip,
                        sip_duration: inputs.years,
                        sip_step_up: inputs.stepup,
                        swp_enabled: inputs.enable_swp ? 1 : 0,
                        swp_withdrawal: inputs.swp_withdrawal,
                        swp_duration: inputs.swp_years,
                        swp_step_up: inputs.swp_stepup
                    }),
                    keepalive: true
                }).catch(() => {});

                setTimeout(() => window.URL.revokeObjectURL(url), 100);
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while generating the PDF.');
                if (generatePdfBtn) {
                    generatePdfBtn.disabled = false;
                    generatePdfBtn.textContent = 'Download PDF';
                }
            });
        });
    }

    // ── Share configuration link ──
    const shareBtn = elements.shareBtn();
    if (shareBtn) {
        shareBtn.addEventListener('click', () => {
            const inputs = getInputs();
            const params = new URLSearchParams();
            params.set('sip', String(inputs.sip));
            params.set('years', String(inputs.years));
            params.set('rate', String(inputs.rate));
            params.set('stepup', String(inputs.stepup));
            params.set('cur', 'INR');
            if (inputs.enable_swp) {
                params.set('swp_on', '1');
                params.set('swp', String(inputs.swp_withdrawal));
                params.set('swp_years', String(inputs.swp_years));
                params.set('swp_stepup', String(inputs.swp_stepup));
            }
            const shareUrl = window.location.origin + '/?' + params.toString();

            navigator.clipboard.writeText(shareUrl).then(() => {
                const btnText = elements.shareBtnText();
                if (btnText) btnText.textContent = 'Copied!';
                shareBtn.classList.remove('text-emerald-600', 'border-indigo-200');
                shareBtn.classList.add('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
                setTimeout(() => {
                    if (btnText) btnText.textContent = 'Share';
                    shareBtn.classList.add('text-emerald-600', 'border-indigo-200');
                    shareBtn.classList.remove('text-emerald-700', 'border-emerald-300', 'bg-emerald-50');
                }, 2000);
            }).catch(() => {
                prompt('Copy this link:', shareUrl);
            });
        });
    }

    // ── Dynamic resize cards fit ──
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) delete el.dataset.baseFont;
            });
            fitSummaryCards();
        }, 150);
    });

    // ── Restore from share parameters ──
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
            const toggle = elements.enableSwp();
            if (toggle) {
                toggle.checked = true;
                syncSwpToggleState();
            }
        }
    }

    // ── EventBus Subscriptions ──
    eventBus.subscribe('input:changed', (inputs) => {
        const results = calculateCorpus(inputs);
        eventBus.publish('data:ready', { inputs, results });
    });

    eventBus.subscribe('data:ready', ({ inputs, results }) => {
        updateSummaryMetrics(results);
        updateTable(results, inputs.enable_swp);
        updateChart(results, inputs.enable_swp);
        if (userHasInteracted) {
            logDebouncedInsight(inputs);
        }
    });

    // ── Initial Draw ──
    const canvas = elements.canvas();
    if (canvas && canvas.dataset.years) {
        // Read pre-rendered datasets from data attributes for instant visual load
        try {
            const initialYears = JSON.parse(canvas.dataset.years).map(y => `Yr ${y}`);
            const initialCumulative = JSON.parse(canvas.dataset.cumulative);
            const initialCorpus = JSON.parse(canvas.dataset.corpus);
            const initialSwp = JSON.parse(canvas.dataset.swp);

            const mockResults = initialYears.map((y, idx) => ({
                year: idx + 1,
                cumulative_invested: initialCumulative[idx],
                combined_total: initialCorpus[idx],
                annual_withdrawal: initialSwp[idx] || 0
            }));

            const inputs = getInputs();
            updateChart(mockResults, inputs.enable_swp);
        } catch (e) {
            console.error('Failed to parse initial canvas dataset:', e);
            triggerCalculation();
        }
    } else {
        triggerCalculation();
    }
}
