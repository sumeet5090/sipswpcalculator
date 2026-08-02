/**
 * CalculatorApp.js
 * Main frontend application controller class.
 */
import { eventBus } from '../utils/EventBus.js';
import { MathEngine } from './MathEngine.js';
import { CurrencyFormatter } from './CurrencyHelper.js';
import { InputValidator } from './InputValidator.js';
import { ChartManager } from './ChartManager.js';
import { AnalyticsService } from './AnalyticsLogger.js';
import { SliderManager } from './SliderManager.js';
import { DOMAdapter } from '../adapters/DOMAdapter.js';
import { GrowStrategy } from './strategies/GrowStrategy.js';
import { TargetCorpusStrategy } from './strategies/TargetCorpusStrategy.js';

export class CalculatorApp {
    constructor() {
        this.dom = new DOMAdapter();
        this.formatter = new CurrencyFormatter();
        this.validator = new InputValidator();
        this.chartManager = new ChartManager(this.formatter);
        this.analytics = new AnalyticsService();
        this.userHasInteracted = false;
        this.interactionCount = 0;
        this.latestResults = [];
        this.activeGoalMode = 'grow';
        
        // Strategy instances
        this.strategies = {
            'grow': new GrowStrategy(this.dom, this.validator),
            'target': new TargetCorpusStrategy(this.dom, this.validator)
        };
        
        this.sliderManager = new SliderManager(
            () => {
                this.userHasInteracted = true;
                this.interactionCount++;
                this.triggerCalculation();
            },
            this.validator
        );
    }

    /**
     * Gather form input parameters and run validation constraints.
     * @returns {object} validated input parameters
     */
    getInputs() {
        const mode = document.querySelector('[data-js="calculator-app"]')?.dataset?.mode ?? 'sip';
        const isSwpMode = (mode === 'swp');

        // In SWP-only mode, `corpus` is the user-facing field; it maps to `lumpsum`
        // internally so MathEngine receives a consistent starting-balance parameter.
        const lumpsumVal = isSwpMode
            ? this.validator.validate('corpus', this.dom.getValue('corpus'))
            : this.validator.validate('lumpsum', this.dom.getValue('lumpsum'));

        return {
            sip:            this.validator.validate('sip', this.dom.getValue('sip')),
            years:          this.validator.validate('years', this.dom.getValue('years')),
            rate:           this.validator.validate('rate', this.dom.getValue('rate')),
            stepup:         this.validator.validate('stepup', this.dom.getValue('stepup')),
            inflation:      this.validator.validate('inflation', this.dom.getValue('inflation')),
            lumpsum:        lumpsumVal,
            enable_swp:     this.dom.getElement('enable_swp')?.checked || isSwpMode,
            swp_withdrawal: this.validator.validate('swp_withdrawal', this.dom.getValue('swp_withdrawal')),
            swp_years:      this.validator.validate('swp_years', this.dom.getValue('swp_years')),
            swp_stepup:     this.validator.validate('swp_stepup', this.dom.getValue('swp_stepup')),
            swp_rate:       this.validator.validate('swp_rate', this.dom.getValue('swp_rate'))
        };
    }

    /**
     * Publish inputs to calculation event queue.
     */
    triggerCalculation() {
        let inputs = this.getInputs();
        
        // Execute Strategy based on goal mode
        const strategy = this.strategies[this.activeGoalMode];
        if (strategy) {
            inputs = strategy.execute(inputs);
        }

        eventBus.publish('input:changed', inputs);
    }

    /**
     * Adapt text font size inside metrics tiles on screen resize.
     */
    fitSummaryCards() {
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
     * Draw years breakdown logs.
     */
    updateTable(data, enableSwp) {
        const tbody = this.dom.getElement('breakdown-body');
        if (!tbody) return;

        const fragment = document.createDocumentFragment();

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-slate-50 border-b border-slate-100 transition-colors";

            const fmt = (v) => v !== null ? this.formatter.format(v) : '-';

            let swpCols = '';
            if (enableSwp) {
                swpCols = `
                    <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap swp-col">${fmt(row.swp_monthly)}</td>
                    <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap swp-col">${fmt(row.annual_withdrawal)}</td>
                    <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap swp-col">${fmt(row.cumulative_withdrawals)}</td>
                `;
            }
            
            const showPostTax = document.getElementById('show_post_tax')?.checked || false;
            let taxCols = '';
            let finalCorpus = row.combined_total;
            if (showPostTax) {
                taxCols = `<td class="px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap tax-col">${fmt(row.ltcg_tax)}</td>`;
                finalCorpus = row.post_tax_total;
            }

            const inputs = this.getInputs();
            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years, inputs.inflation);
            }

            tr.innerHTML = `
                <td class="px-6 py-4 font-medium text-slate-700 whitespace-nowrap">${row.year}</td>
                <td class="px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap">${this.formatter.format(row.begin_balance)}</td>
                <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${fmt(row.sip_monthly)}</td>
                <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${this.formatter.format(row.annual_contribution)}</td>
                <td class="px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap">${this.formatter.format(row.cumulative_invested)}</td>
                ${swpCols}
                <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap">${this.formatter.format(row.interest)}</td>
                ${taxCols}
                <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap end-corpus-col">${this.formatter.format(finalCorpus)}</td>
            `;

            fragment.appendChild(tr);
        });

        tbody.innerHTML = '';
        tbody.appendChild(fragment);
    }

    /**
     * Update summary stats block.
     */
    updateSummaryMetrics(data) {
        if (!data || data.length === 0) return;

        const lastRow = data[data.length - 1];
        const totalInvested = lastRow.cumulative_invested;
        const preTaxCorpus = lastRow.combined_total;
        const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
        const preTaxGains = (preTaxCorpus + totalWithdrawn) - totalInvested;

        const showPostTax = document.getElementById('show_post_tax')?.checked || false;
        
        let finalCorpus = preTaxCorpus;
        let finalGains = preTaxGains;
        
        const inputs = this.getInputs();
        
        // Calculate delay cost
        const delayCost = MathEngine.calculateDelayCost(inputs);
        const delayCostEl = document.getElementById('delay-cost-amount');
        const delayCostBanner = document.getElementById('delay-cost-banner');
        
        if (delayCost > 0) {
            if (delayCostBanner) delayCostBanner.style.display = 'flex';
            if (delayCostEl) delayCostEl.textContent = this.formatter.format(delayCost);
        } else {
            if (delayCostBanner) delayCostBanner.style.display = 'none';
        }

        if (showPostTax) {
            const taxableGains = Math.max(0, preTaxGains - 125000);
            const ltcgTax = taxableGains * 0.125;
            finalCorpus = Math.max(0, preTaxCorpus - ltcgTax);
            finalGains = Math.max(0, preTaxGains - ltcgTax);

            const interestTitle = document.getElementById('title-interest');
            const corpusTitle = document.getElementById('title-corpus');
            if (interestTitle) interestTitle.textContent = 'Total Gains (Post-Tax)';
            if (corpusTitle) corpusTitle.textContent = 'Final Corpus (Post-Tax)';
        } else {
            const interestTitle = document.getElementById('title-interest');
            const corpusTitle = document.getElementById('title-corpus');
            if (interestTitle) interestTitle.textContent = 'Total Gains';
            if (corpusTitle) corpusTitle.textContent = 'Final Corpus';
        }
        
        // Apply inflation discounting
        if (inputs.inflation > 0) {
            finalCorpus = MathEngine.calculateInflationDiscount(finalCorpus, inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years, inputs.inflation);
            const corpusTitle = document.getElementById('title-corpus');
            if (corpusTitle) corpusTitle.textContent += ' (Inflation Adjusted)';
        }

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = this.formatter.format(val);
        };

        setVal('summary-invested', totalInvested);
        setVal('summary-interest', finalGains);
        setVal('summary-withdrawn', totalWithdrawn);
        setVal('summary-corpus', finalCorpus);

        this.fitSummaryCards();
    }

    /**
     * Show/Hide SWP withdrawal configurations.
     */
    syncSwpToggleState() {
        const isChecked = this.dom.getElement('enable_swp')?.checked || false;
        const fields = this.dom.getElement('swp-fields');
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

        this.triggerCalculation();
    }

    /**
     * Update Segmented Control UI styles and configure layout constraints based on goal mode.
     * @param {string} mode - 'grow' or 'target'
     */
    setGoalMode(mode) {
        if (mode === this.activeGoalMode) return;
        this.activeGoalMode = mode;

        const growBtn = this.dom.getElement('goal-grow');
        const targetBtn = this.dom.getElement('goal-target');
        const sipContainer = this.dom.getElement('sip_container');
        const targetCorpusContainer = this.dom.getElement('target_corpus_container');

        const activeClass = ['bg-white', 'text-emerald-600', 'shadow-sm', 'border', 'border-slate-200/20'];
        const inactiveClass = ['text-slate-500', 'hover:text-slate-700'];

        if (mode === 'grow') {
            if (growBtn) {
                growBtn.classList.add(...activeClass);
                growBtn.classList.remove(...inactiveClass);
                growBtn.setAttribute('aria-checked', 'true');
            }
            if (targetBtn) {
                targetBtn.classList.remove(...activeClass);
                targetBtn.classList.add(...inactiveClass);
                targetBtn.setAttribute('aria-checked', 'false');
            }
            if (sipContainer) {
                sipContainer.style.opacity = '1';
                sipContainer.style.pointerEvents = 'auto';
            }
            if (targetCorpusContainer) {
                targetCorpusContainer.style.display = 'none';
            }
        } else {
            if (targetBtn) {
                targetBtn.classList.add(...activeClass);
                targetBtn.classList.remove(...inactiveClass);
                targetBtn.setAttribute('aria-checked', 'true');
            }
            if (growBtn) {
                growBtn.classList.remove(...activeClass);
                growBtn.classList.add(...inactiveClass);
                growBtn.setAttribute('aria-checked', 'false');
            }
            if (sipContainer) {
                sipContainer.style.opacity = '0.65';
                sipContainer.style.pointerEvents = 'none';
            }
            if (targetCorpusContainer) {
                targetCorpusContainer.style.display = 'block';
            }
        }
        this.triggerCalculation();
    }

    setSmartNudgeRate(val) {
        this.dom.setValue('rate', val);
        this.dom.getElement('rate')?.dispatchEvent(new Event('input', { bubbles: true }));
        this.dom.setValue('rate_range', val);
        this.dom.getElement('rate_range')?.dispatchEvent(new Event('input', { bubbles: true }));
        
        const popover = this.dom.getElement('rate-nudge-popover');
        if (popover) {
            popover.classList.add('hidden');
            this.dom.getElement('rate-nudge-btn')?.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Initialize app lifecycle.
     */
    init() {
        const appContainer = document.getElementById('calculator-app');
        if (appContainer && appContainer.dataset.mode === 'target_corpus') {
            this.activeGoalMode = 'target';
        }

        // ── Synchronize Slider pairs via SliderManager ──
        this.sliderManager.syncAll({
            'sip':            'sip_range',
            'years':          'years_range',
            'rate':           'rate_range',
            'stepup':         'stepup_range',
            'inflation':      'inflation_range',
            'lumpsum':        'lumpsum_range',
            'corpus':         'corpus_range',
            'target_corpus':  'target_corpus_range',
            'swp_withdrawal': 'swp_withdrawal_range',
            'swp_years':      'swp_years_range',
            'swp_stepup':     'swp_stepup_range',
            'swp_rate':       'swp_rate_range',
        });

        // ── Goal Mode Segmented Controls ──
        const growBtn = this.dom.getElement('goal-grow');
        const targetBtn = this.dom.getElement('goal-target');
        if (growBtn) {
            growBtn.addEventListener('click', () => this.setGoalMode('grow'));
        }
        if (targetBtn) {
            targetBtn.addEventListener('click', () => this.setGoalMode('target'));
        }

        // ── Auto-Switch to Grow Mode when user directly interacts with SIP inputs ──
        const sipInput = this.dom.getElement('sip');
        const sipRange = this.dom.getElement('sip_range');
        const autoSwitchToGrow = () => {
            if (this.activeGoalMode === 'target') {
                this.setGoalMode('grow');
            }
        };
        if (sipInput) sipInput.addEventListener('input', autoSwitchToGrow);
        if (sipRange) sipRange.addEventListener('input', autoSwitchToGrow);

        // ── Auto-Calculate SWP Target Corpus and feed it back to SIP target ──
        const swpWithdrawal = this.dom.getElement('swp_withdrawal');
        const swpWithdrawalRange = this.dom.getElement('swp_withdrawal_range');
        const swpYears = this.dom.getElement('swp_years');
        const swpYearsRange = this.dom.getElement('swp_years_range');
        const handleSwpInput = () => {
            const inputs = this.getInputs();
            if (inputs.enable_swp && inputs.swp_withdrawal > 0 && inputs.swp_years > 0) {
                const reqCorpus = MathEngine.calculateRequiredStartingCorpusForSwp(inputs);
                if (reqCorpus > 0) {
                    this.dom.setValue('target_corpus', reqCorpus);
                    
                    const targetRangeEl = this.dom.getElement('target_corpus_range');
                    if (targetRangeEl) {
                        const defaultMax = parseFloat(targetRangeEl.getAttribute('max')) || 50000000;
                        if (reqCorpus > defaultMax) {
                            targetRangeEl.max = reqCorpus;
                        } else {
                            targetRangeEl.max = defaultMax;
                        }
                        this.dom.setValue('target_corpus_range', reqCorpus);
                    }
                    
                    if (this.activeGoalMode !== 'target') {
                        this.setGoalMode('target');
                    }
                }
            }
        };

        if (swpWithdrawal) swpWithdrawal.addEventListener('input', handleSwpInput);
        if (swpWithdrawalRange) swpWithdrawalRange.addEventListener('input', handleSwpInput);
        if (swpYears) swpYears.addEventListener('input', handleSwpInput);
        if (swpYearsRange) swpYearsRange.addEventListener('input', handleSwpInput);

        // ── SWP Toggle ──
        const swpToggle = this.dom.getElement('enable_swp');
        if (swpToggle) {
            swpToggle.addEventListener('change', () => this.syncSwpToggleState());
        }

        // ── Post-Tax Toggle ──
        const postTaxToggle = document.getElementById('show_post_tax');
        if (postTaxToggle) {
            postTaxToggle.addEventListener('change', () => {
                const taxCols = document.querySelectorAll('.tax-col');
                taxCols.forEach(el => {
                    el.style.display = postTaxToggle.checked ? '' : 'none';
                });
                this.triggerCalculation();
            });
        }

        // ── Wealth Map Toggle ──
        const wealthMapToggle = document.getElementById('show_wealth_map');
        if (wealthMapToggle) {
            wealthMapToggle.addEventListener('change', () => this.triggerCalculation());
        }

        // ── Tabs controller ──
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
                swpTab.setAttribute('aria-selected', 'false');
            }
        };

        // ──smart rate nudge popovers ──
        const nudgeBtn = this.dom.getElement('rate-nudge-btn');
        const nudgePopover = this.dom.getElement('rate-nudge-popover');
        const nudgeClose = this.dom.getElement('rate-nudge-close');

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
            this.dom.getElement('use-india-rate')?.addEventListener('click', () => this.setSmartNudgeRate(12));
            this.dom.getElement('use-us-rate')?.addEventListener('click', () => this.setSmartNudgeRate(15));

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

        // ── PDF export actions modal ──
        const pdfModal = this.dom.getElement('pdfModal');
        const openPdfBtn = this.dom.getElement('openPdfModalBtn');
        const closePdfBtn = this.dom.getElement('closePdfModalBtn');
        const pdfForm = this.dom.getElement('pdfForm');

        const openModalFn = (el) => {
            if (!el) return;
            if (typeof el.showModal === 'function') {
                el.showModal();
            } else {
                el.classList.remove('hidden');
            }
        };

        const closeModalFn = (el) => {
            if (!el) return;
            if (typeof el.close === 'function') {
                el.close();
            } else {
                el.classList.add('hidden');
            }
        };

        if (openPdfBtn && pdfModal) {
            openPdfBtn.addEventListener('click', () => {
                if (!this.chartManager.getChartInstance()) {
                    const btn = this.dom.getElement('openPdfModalBtn');
                    if (btn) {
                        const origText = btn.querySelector('svg + span, span')?.textContent || 'PDF';
                        btn.classList.add('border-rose-300', 'text-rose-600');
                        const span = btn.querySelector('svg + span, span') || btn;
                        span.textContent = 'Calculate first!';
                        setTimeout(() => {
                            btn.classList.remove('border-rose-300', 'text-rose-600');
                            span.textContent = origText;
                        }, 2500);
                    }
                    return;
                }
                openModalFn(pdfModal);
            });
            if (closePdfBtn) {
                closePdfBtn.addEventListener('click', () => closeModalFn(pdfModal));
            }
            pdfModal.addEventListener('click', e => {
                if (e.target === pdfModal) closeModalFn(pdfModal);
            });
        }

        if (pdfForm) {
            pdfForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const generatePdfBtn = this.dom.getElement('generatePdfBtn');
                if (generatePdfBtn) {
                    generatePdfBtn.disabled = true;
                    generatePdfBtn.textContent = 'Generating...';
                }

                const chartInst = this.chartManager.getChartInstance();
                let chartDataURL = '';
                if (chartInst && chartInst.canvas) {
                    try {
                        chartDataURL = chartInst.canvas.toDataURL('image/png');
                    } catch (e) {
                        chartDataURL = '';
                    }
                }
                const resultsTable = document.getElementById('results-table');
                const tableHtml = resultsTable ? resultsTable.outerHTML : '<table><tr><td>No data available.</td></tr></table>';

                const formData = new FormData(pdfForm);
                formData.append('sip', this.dom.getValue('sip') || 0);
                formData.append('years', this.dom.getValue('years') || 0);
                formData.append('rate', this.dom.getValue('rate') || 0);
                formData.append('stepup', this.dom.getValue('stepup') || 0);
                formData.append('lumpsum', this.dom.getValue('lumpsum') || 0);
                formData.append('swp_withdrawal', this.dom.getValue('swp_withdrawal') || 0);
                formData.append('swp_stepup', this.dom.getValue('swp_stepup') || 0);
                formData.append('swp_years', this.dom.getValue('swp_years') || 0);
                formData.append('swp_rate', this.dom.getValue('swp_rate') || 0);

                formData.append('currency_symbol', '₹');
                formData.append('summary_invested', document.getElementById('summary-invested')?.textContent.trim() || '0');
                formData.append('summary_interest', document.getElementById('summary-interest')?.textContent.trim() || '0');
                formData.append('summary_withdrawn', document.getElementById('summary-withdrawn')?.textContent.trim() || '0');
                formData.append('summary_corpus', document.getElementById('summary-corpus')?.textContent.trim() || '0');

                const lastRow = (Array.isArray(this.latestResults) && this.latestResults.length > 0)
                    ? this.latestResults[this.latestResults.length - 1]
                    : null;
                formData.append('raw_invested', lastRow ? (lastRow.cumulative_invested || 0) : 0);
                formData.append('raw_corpus', lastRow ? (lastRow.combined_total || 0) : 0);

                formData.append('chartData', chartDataURL);
                formData.append('tableHtml', tableHtml);

                fetch('/generate-pdf', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (res.ok) return res.blob();
                    throw new Error('PDF generation failed.');
                })
                .then(blob => {
                    const clientNameClean = (formData.get('clientName') || 'Client').toString().trim().replace(/[^a-zA-Z0-9_\-]/g, '_').replace(/_+/g, '_') || 'Client';
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `Financial_Report_for_${clientNameClean}.pdf`;
                    document.body.appendChild(a);
                    a.click();

                    if (generatePdfBtn) {
                        generatePdfBtn.disabled = false;
                        generatePdfBtn.textContent = 'Download PDF';
                    }
                    closeModalFn(pdfModal);
                    a.remove();

                    // Log PDF telemetry
                    const inputs = this.getInputs();
                    const advisorNameStr = (formData.get('advisorName') || '').toString().trim();
                    const pdfHasCustomName = advisorNameStr.length > 0 ? 1 : 0;

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
                            pdf_has_custom_name: pdfHasCustomName,
                            exit_action: 'pdf_download',
                            interest_rate: inputs.rate,
                            sip_amount: inputs.sip,
                            sip_duration: inputs.years,
                            sip_step_up: inputs.stepup,
                            swp_enabled: inputs.enable_swp ? 1 : 0,
                            swp_withdrawal: inputs.swp_withdrawal,
                            swp_duration: inputs.swp_years,
                            swp_step_up: inputs.swp_stepup,
                            lumpsum: inputs.lumpsum,
                            swp_rate: inputs.swp_rate,
                            interaction_count: this.interactionCount
                        }),
                        keepalive: true
                    }).catch(() => {});

                    setTimeout(() => window.URL.revokeObjectURL(url), 100);
                })
                .catch(err => {
                    console.error('PDF generation failed:', err.message);
                    if (generatePdfBtn) {
                        generatePdfBtn.disabled = false;
                        generatePdfBtn.textContent = 'Download PDF';
                    }
                });
            });
        }

        // ── Clipboard Sharing links ──
        const shareBtn = this.dom.getElement('shareCalcBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                const inputs = this.getInputs();
                const params = new URLSearchParams();
                params.set('sip', String(inputs.sip));
                params.set('years', String(inputs.years));
                params.set('rate', String(inputs.rate));
                params.set('stepup', String(inputs.stepup));
                params.set('lumpsum', String(inputs.lumpsum));
                params.set('cur', 'INR');
                if (inputs.enable_swp) {
                    params.set('swp_on', '1');
                    params.set('swp', String(inputs.swp_withdrawal));
                    params.set('swp_years', String(inputs.swp_years));
                    params.set('swp_stepup', String(inputs.swp_stepup));
                    params.set('swp_rate', String(inputs.swp_rate));
                }
                const shareUrl = window.location.origin + window.location.pathname + '?' + params.toString();

                navigator.clipboard.writeText(shareUrl).then(() => {
                    const btnText = this.dom.getElement('shareBtnText');
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

        // ── Resize handlers ──
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
                ids.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) delete el.dataset.baseFont;
                });
                this.fitSummaryCards();
            }, 150);
        });

        // ── Restore from URL query params ──
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sip') || urlParams.has('lumpsum')) {
            const paramMap = {
                'sip': 'sip',
                'years': 'years',
                'rate': 'rate',
                'stepup': 'stepup',
                'lumpsum': 'lumpsum',
                'swp': 'swp_withdrawal',
                'swp_years': 'swp_years',
                'swp_stepup': 'swp_stepup',
                'swp_rate': 'swp_rate'
            };
            for (const [key, id] of Object.entries(paramMap)) {
                if (urlParams.has(key)) {
                    this.dom.setValue(id, urlParams.get(key));
                    this.dom.setValue(id + '_range', urlParams.get(key));
                }
            }
            if (urlParams.has('swp_on') && urlParams.get('swp_on') === '1') {
                const swpToggle = this.dom.getElement('enable_swp');
                if (swpToggle) {
                    swpToggle.checked = true;
                    this.syncSwpToggleState();
                }
            }
        }

        // Setup event bus listeners
        eventBus.subscribe('input:changed', (inputs) => {
            if (!this.userHasInteracted) return;

            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;
            this.updateTable(combined, inputs.enable_swp);
            this.updateSummaryMetrics(combined);

            if (document.getElementById('show_wealth_map')?.checked) {
                this.chartManager.updateWaterfallChart(combined, inputs);
            } else {
                this.chartManager.updateChart(combined, inputs.enable_swp);
            }
        });

        // Initial render logic
        const existingData = window.__INITIAL_DATA__ || [];
        if (existingData.length > 0) {
            this.latestResults = existingData;
            
            // Check if SWP is inherently enabled in the template defaults
            let swpEnabledOnLoad = false;
            const urlSwpOn = (new URLSearchParams(window.location.search)).get('swp_on') === '1';
            const swpToggle = this.dom.getElement('enable_swp');
            if (swpToggle && (swpToggle.checked || urlSwpOn)) {
                swpEnabledOnLoad = true;
                if (urlSwpOn) swpToggle.checked = true;
                this.syncSwpToggleState();
            } else if (document.querySelector('[data-js="calculator-app"]')?.dataset?.mode === 'swp') {
                swpEnabledOnLoad = true;
            }

            this.updateTable(existingData, swpEnabledOnLoad);
            this.updateSummaryMetrics(existingData);

            if (document.getElementById('show_wealth_map')?.checked) {
                this.chartManager.updateWaterfallChart(existingData, this.getInputs());
            } else {
                this.chartManager.updateChart(existingData, swpEnabledOnLoad);
            }
        }
    }
}
