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

export class CalculatorApp {
    constructor() {
        this.formatter = new CurrencyFormatter();
        this.validator = new InputValidator();
        this.chartManager = new ChartManager(this.formatter);
        this.analytics = new AnalyticsService();
        this.userHasInteracted = false;
        this.activeGoalMode = 'grow';
        this.sliderManager = new SliderManager(
            () => {
                this.userHasInteracted = true;
                this.triggerCalculation();
            },
            this.validator
        );

        // Cache selectors
        this.elements = {
            sip: () => document.getElementById('sip'),
            sipRange: () => document.getElementById('sip_range'),
            years: () => document.getElementById('years'),
            yearsRange: () => document.getElementById('years_range'),
            rate: () => document.getElementById('rate'),
            rateRange: () => document.getElementById('rate_range'),
            stepup: () => document.getElementById('stepup'),
            stepupRange: () => document.getElementById('stepup_range'),
            lumpsum: () => document.getElementById('lumpsum'),
            lumpsumRange: () => document.getElementById('lumpsum_range'),
            inflation: () => document.getElementById('inflation'),
            inflationRange: () => document.getElementById('inflation_range'),
            goalGrow: () => document.getElementById('goal-grow'),
            goalTarget: () => document.getElementById('goal-target'),
            targetCorpus: () => document.getElementById('target_corpus'),
            targetCorpusRange: () => document.getElementById('target_corpus_range'),
            sipContainer: () => document.getElementById('sip_container'),
            targetCorpusContainer: () => document.getElementById('target_corpus_container'),
            enableSwp: () => document.getElementById('enable_swp'),
            swpFields: () => document.getElementById('swp-fields'),
            swpWithdrawal: () => document.getElementById('swp_withdrawal'),
            swpWithdrawalRange: () => document.getElementById('swp_withdrawal_range'),
            swpYears: () => document.getElementById('swp_years'),
            swpYearsRange: () => document.getElementById('swp_years_range'),
            swpStepup: () => document.getElementById('swp_stepup'),
            swpStepupRange: () => document.getElementById('swp_stepup_range'),
            swpRate: () => document.getElementById('swp_rate'),
            swpRateRange: () => document.getElementById('swp_rate_range'),
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
            canvas: () => document.getElementById('corpusChart'),
            corpus: () => document.getElementById('corpus'),
            corpusRange: () => document.getElementById('corpus_range'),
        };
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
            ? this.validator.validate('corpus', this.elements.corpus()?.value)
            : this.validator.validate('lumpsum', this.elements.lumpsum()?.value);

        return {
            sip:            this.validator.validate('sip', this.elements.sip()?.value),
            years:          this.validator.validate('years', this.elements.years()?.value),
            rate:           this.validator.validate('rate', this.elements.rate()?.value),
            stepup:         this.validator.validate('stepup', this.elements.stepup()?.value),
            inflation:      this.validator.validate('inflation', this.elements.inflation()?.value),
            lumpsum:        lumpsumVal,
            enable_swp:     this.elements.enableSwp()?.checked || isSwpMode,
            swp_withdrawal: this.validator.validate('swp_withdrawal', this.elements.swpWithdrawal()?.value),
            swp_years:      this.validator.validate('swp_years', this.elements.swpYears()?.value),
            swp_stepup: this.validator.validate('swp_stepup', this.elements.swpStepup()?.value),
            swp_rate: this.validator.validate('swp_rate', this.elements.swpRate()?.value)
        };
    }

    /**
     * Publish inputs to calculation event queue.
     */
    triggerCalculation() {
        const inputs = this.getInputs();
        
        // Handle Goal Seek Mode
        if (this.activeGoalMode === 'target') {
            const targetCorpus = this.validator.validate('target_corpus', this.elements.targetCorpus()?.value || 10000000);
            const requiredSip = MathEngine.calculateRequiredSip(inputs, targetCorpus);
            inputs.sip = requiredSip;
            
            const sipEl = this.elements.sip();
            const sipRangeEl = this.elements.sipRange();
            if (sipEl && sipEl.value != requiredSip) {
                sipEl.value = requiredSip;
                sipEl.setAttribute('aria-valuenow', requiredSip);
            }
            if (sipRangeEl && sipRangeEl.value != requiredSip) {
                sipRangeEl.value = requiredSip;
                sipRangeEl.setAttribute('aria-valuenow', requiredSip);
            }
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
        const tbody = this.elements.tbody();
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
        const isChecked = this.elements.enableSwp()?.checked || false;
        const fields = this.elements.swpFields();
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

        const growBtn = this.elements.goalGrow();
        const targetBtn = this.elements.goalTarget();
        const sipContainer = this.elements.sipContainer();
        const targetCorpusContainer = this.elements.targetCorpusContainer();

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
        const rateEl = this.elements.rate();
        const rateRangeEl = this.elements.rateRange();
        const popover = this.elements.rateNudgePopover();

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
            this.elements.rateNudgeBtn()?.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Initialize app lifecycle.
     */
    init() {
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
        const growBtn = this.elements.goalGrow();
        const targetBtn = this.elements.goalTarget();
        if (growBtn) {
            growBtn.addEventListener('click', () => this.setGoalMode('grow'));
        }
        if (targetBtn) {
            targetBtn.addEventListener('click', () => this.setGoalMode('target'));
        }

        // ── Auto-Switch to Grow Mode when user directly interacts with SIP inputs ──
        const sipInput = this.elements.sip();
        const sipRange = this.elements.sipRange();
        const autoSwitchToGrow = () => {
            if (this.activeGoalMode === 'target') {
                this.setGoalMode('grow');
            }
        };
        if (sipInput) sipInput.addEventListener('input', autoSwitchToGrow);
        if (sipRange) sipRange.addEventListener('input', autoSwitchToGrow);

        // ── Auto-Calculate SWP Target Corpus and feed it back to SIP target ──
        const swpWithdrawal = this.elements.swpWithdrawal();
        const swpWithdrawalRange = this.elements.swpWithdrawalRange();
        const swpYears = this.elements.swpYears();
        const swpYearsRange = this.elements.swpYearsRange();
        const handleSwpInput = () => {
            const inputs = this.getInputs();
            if (inputs.enable_swp && inputs.swp_withdrawal > 0 && inputs.swp_years > 0) {
                const reqCorpus = MathEngine.calculateRequiredStartingCorpusForSwp(inputs);
                if (reqCorpus > 0) {
                    const targetEl = this.elements.targetCorpus();
                    const targetRangeEl = this.elements.targetCorpusRange();
                    
                    if (targetEl) {
                        targetEl.value = reqCorpus;
                        targetEl.setAttribute('aria-valuenow', reqCorpus);
                    }
                    if (targetRangeEl) {
                        const defaultMax = parseFloat(targetRangeEl.getAttribute('max')) || 50000000;
                        if (reqCorpus > defaultMax) {
                            targetRangeEl.max = reqCorpus;
                        } else {
                            targetRangeEl.max = defaultMax;
                        }
                        targetRangeEl.value = reqCorpus;
                        targetRangeEl.setAttribute('aria-valuenow', reqCorpus);
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
        const swpToggle = this.elements.enableSwp();
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
                sipTab.setAttribute('aria-selected', 'false');
            }
        };

        // ──smart rate nudge popovers ──
        const nudgeBtn = this.elements.rateNudgeBtn();
        const nudgePopover = this.elements.rateNudgePopover();
        const nudgeClose = this.elements.rateNudgeClose();

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
            this.elements.useIndiaRate()?.addEventListener('click', () => this.setSmartNudgeRate(12));
            this.elements.useUsRate()?.addEventListener('click', () => this.setSmartNudgeRate(15));

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
        const pdfModal = this.elements.pdfModal();
        const openPdfBtn = this.elements.openPdfModalBtn();
        const closePdfBtn = this.elements.closePdfModalBtn();
        const pdfForm = this.elements.pdfForm();

        if (openPdfBtn && pdfModal) {
            openPdfBtn.addEventListener('click', () => {
                if (!this.chartManager.getChartInstance()) {
                    const btn = this.elements.openPdfModalBtn();
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
                pdfModal.showModal();
            });
            if (closePdfBtn) {
                closePdfBtn.addEventListener('click', () => pdfModal.close());
            }
            pdfModal.addEventListener('click', e => {
                if (e.target === pdfModal) pdfModal.close();
            });
        }

        if (pdfForm) {
            pdfForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const generatePdfBtn = this.elements.generatePdfBtn();
                if (generatePdfBtn) {
                    generatePdfBtn.disabled = true;
                    generatePdfBtn.textContent = 'Generating...';
                }

                const chartInst = this.chartManager.getChartInstance();
                const chartDataURL = chartInst ? chartInst.toBase64Image() : '';
                const resultsTable = document.getElementById('results-table');
                const tableHtml = resultsTable ? resultsTable.outerHTML : '<table><tr><td>No data available.</td></tr></table>';

                const formData = new FormData(pdfForm);
                formData.append('sip', this.elements.sip().value);
                formData.append('years', this.elements.years().value);
                formData.append('rate', this.elements.rate().value);
                formData.append('stepup', this.elements.stepup().value);
                formData.append('lumpsum', this.elements.lumpsum().value);
                formData.append('swp_withdrawal', this.elements.swpWithdrawal().value);
                formData.append('swp_stepup', this.elements.swpStepup().value);
                formData.append('swp_years', this.elements.swpYears().value);
                formData.append('swp_rate', this.elements.swpRate().value);

                formData.append('currency_symbol', '₹');
                formData.append('summary_invested', document.getElementById('summary-invested')?.textContent.trim() || '0');
                formData.append('summary_interest', document.getElementById('summary-interest')?.textContent.trim() || '0');
                formData.append('summary_withdrawn', document.getElementById('summary-withdrawn')?.textContent.trim() || '0');
                formData.append('summary_corpus', document.getElementById('summary-corpus')?.textContent.trim() || '0');

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
                    pdfModal.close();
                    a.remove();

                    // Log PDF telemetry
                    const inputs = this.getInputs();
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
                            swp_step_up: inputs.swp_stepup,
                            lumpsum: inputs.lumpsum,
                            swp_rate: inputs.swp_rate
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
        const shareBtn = this.elements.shareBtn();
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
                const shareUrl = window.location.origin + '/?' + params.toString();

                navigator.clipboard.writeText(shareUrl).then(() => {
                    const btnText = this.elements.shareBtnText();
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
                'sip': 'sip', 'years': 'years', 'rate': 'rate', 'stepup': 'stepup',
                'lumpsum': 'lumpsum', 'swp_rate': 'swp_rate',
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
                const toggle = this.elements.enableSwp();
                if (toggle) {
                    toggle.checked = true;
                    this.syncSwpToggleState();
                }
            }
        }

        // ── EventBus Subscriptions ──
        eventBus.subscribe('input:changed', (inputs) => {
            const results = MathEngine.calculateCorpus(inputs);
            eventBus.publish('data:ready', { inputs, results });
        });

        eventBus.subscribe('data:ready', ({ inputs, results }) => {
            this.updateSummaryMetrics(results);
            this.updateTable(results, inputs.enable_swp);
            this.chartManager.updateChart(results, inputs.enable_swp);
            if (this.userHasInteracted) {
                this.analytics.logInsight(inputs);
            }
        });

        // ── Initial render check ──
        const canvas = this.elements.canvas();
        if (canvas && canvas.dataset.years) {
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

                const inputs = this.getInputs();
                this.chartManager.updateChart(mockResults, inputs.enable_swp);
            } catch (e) {
                console.error('Failed to parse canvas initial data attribute:', e);
                this.triggerCalculation();
            }
        } else {
            this.triggerCalculation();
        }

        // Hydrate all static amounts
        this.hydrateDynamicAmounts();
    }

    hydrateDynamicAmounts() {
        document.querySelectorAll('.dynamic-amount').forEach(el => {
            const amount = parseFloat(el.getAttribute('data-amount-inr') || el.getAttribute('data-amount'));
            if (!isNaN(amount)) {
                el.textContent = this.formatter.formatDynamic(amount);
            }
        });
    }
}
