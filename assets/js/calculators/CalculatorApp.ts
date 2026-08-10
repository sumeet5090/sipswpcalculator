/**
 * CalculatorApp.ts
 * Main frontend application controller class.
 */
import { eventBus } from '../utils/EventBus';
import { MathEngine } from './MathEngine';
import { CurrencyFormatter } from './CurrencyHelper';
import { InputValidator } from './InputValidator';
import { ChartManager } from './ChartManager';
import { AnalyticsService } from './AnalyticsLogger';
import { SliderManager } from './SliderManager';
import { DOMAdapter } from '../adapters/DOMAdapter';
import { GrowStrategy } from './strategies/GrowStrategy';
import { TargetCorpusStrategy } from './strategies/TargetCorpusStrategy';
import { CalculatorStrategy } from './strategies/CalculatorStrategy';
import { InvestmentInputs, YearResult } from '../types';
import { PdfExportController } from './controllers/PdfExportController';
import { TabController } from './controllers/TabController';
import { ShareController } from './controllers/ShareController';
import { SmartNudgeController } from './controllers/SmartNudgeController';
import { UrlStateController } from './controllers/UrlStateController';

export class CalculatorApp {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private validator: InputValidator;
    private chartManager: ChartManager;
    private analytics: AnalyticsService;
    private userHasInteracted: boolean;
    private interactionCount: number;
    private latestResults: YearResult[];
    private activeGoalMode: string;
    private strategies: Record<string, CalculatorStrategy>;
    private sliderManager: SliderManager;

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
     */
    getInputs(): InvestmentInputs {
        const mode = document.querySelector<HTMLElement>('[data-js="calculator-app"]')?.dataset?.mode ?? 'sip';
        const isSwpMode = (mode === 'swp');

        const lumpsumVal = isSwpMode
            ? this.validator.validate('corpus', this.dom.getValue('corpus') || 0)
            : this.validator.validate('lumpsum', this.dom.getValue('lumpsum') || 0);

        return {
            sip:            this.validator.validate('sip', this.dom.getValue('sip') || 0),
            years:          this.validator.validate('years', this.dom.getValue('years') || 0),
            rate:           this.validator.validate('rate', this.dom.getValue('rate') || 0),
            stepup:         this.validator.validate('stepup', this.dom.getValue('stepup') || 0),
            inflation:      this.validator.validate('inflation', this.dom.getValue('inflation') || 0),
            lumpsum:        lumpsumVal,
            enable_swp:     (this.dom.getElement<HTMLInputElement>('enable_swp')?.checked) || isSwpMode,
            swp_withdrawal: this.validator.validate('swp_withdrawal', this.dom.getValue('swp_withdrawal') || 0),
            swp_years:      this.validator.validate('swp_years', this.dom.getValue('swp_years') || 0),
            swp_stepup:     this.validator.validate('swp_stepup', this.dom.getValue('swp_stepup') || 0),
            swp_rate:       this.validator.validate('swp_rate', this.dom.getValue('swp_rate') || 0)
        };
    }

    /**
     * Publish inputs to calculation event queue.
     */
    triggerCalculation(): void {
        let inputs = this.getInputs();
        
        // Execute Strategy based on goal mode
        const strategy = this.strategies[this.activeGoalMode];
        if (strategy) {
            inputs = strategy.execute(inputs);
        }

        if (this.activeGoalMode === 'target_corpus' || this.activeGoalMode === 'target') {
            this.dom.setValue('sip', inputs.sip);
            this.dom.setValue('sip_range', inputs.sip);
        }

        eventBus.publish('input:changed', inputs);
    }

    /**
     * Adapt text font size inside metrics tiles on screen resize.
     */
    fitSummaryCards(): void {
        const ids = ['summary-invested', 'summary-interest', 'summary-withdrawn', 'summary-corpus'];
        const cardElms = ids.map(id => document.getElementById(id)).filter((el): el is HTMLElement => el !== null);
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
            if (!parent) return null;
            const cs = getComputedStyle(parent);
            const availableW = parent.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
            const textW = el.scrollWidth;
            return { el, basePx: parseFloat(el.dataset.baseFont || '16'), availableW, textW };
        }).filter((item): item is NonNullable<typeof item> => item !== null);

        results.forEach(({ el, basePx, availableW, textW }) => {
            if (textW > availableW && availableW > 0) {
                el.style.fontSize = Math.max((availableW / textW) * basePx, 10) + 'px';
            } else {
                el.style.fontSize = basePx + 'px';
            }
        });
    }

    /**
     * Draw years breakdown logs securely using DOM node construction.
     */
    updateTable(data: YearResult[], enableSwp: boolean): void {
        const tbody = this.dom.getElement('breakdown-body');
        if (!tbody) return;

        const fragment = document.createDocumentFragment();
        const showPostTax = (document.getElementById('show_post_tax') as HTMLInputElement | null)?.checked || false;
        const inputs = this.getInputs();

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-slate-50 border-b border-slate-100 transition-colors";

            const fmt = (v: number | null | undefined) => (v !== null && v !== undefined) ? this.formatter.format(v) : '-';
            const swpDisplay = enableSwp ? '' : 'none';
            const taxDisplay = showPostTax ? '' : 'none';

            let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
            const ltcgTax = row.ltcg_tax ?? 0;

            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(
                    finalCorpus,
                    inputs.enable_swp ? (inputs.years + inputs.swp_years) : inputs.years,
                    inputs.inflation
                );
            }

            const createCell = (text: string, className: string, displayStyle: string = ''): HTMLTableCellElement => {
                const td = document.createElement('td');
                td.className = className;
                if (displayStyle !== '') {
                    td.style.display = displayStyle;
                }
                td.textContent = text;
                return td;
            };

            tr.appendChild(createCell(String(row.year), "px-6 py-4 font-medium text-slate-700 whitespace-nowrap"));
            tr.appendChild(createCell(this.formatter.format(row.begin_balance), "px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap"));
            tr.appendChild(createCell(fmt(row.sip_monthly), "px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap"));
            tr.appendChild(createCell(this.formatter.format(row.annual_contribution), "px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap"));
            tr.appendChild(createCell(this.formatter.format(row.cumulative_invested), "px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap"));

            // SWP Columns
            tr.appendChild(createCell(fmt(row.swp_monthly), "px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap swp-col", swpDisplay));
            tr.appendChild(createCell(fmt(row.annual_withdrawal), "px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap swp-col", swpDisplay));
            tr.appendChild(createCell(fmt(row.cumulative_withdrawals), "px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap swp-col", swpDisplay));

            tr.appendChild(createCell(this.formatter.format(row.interest), "px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap"));

            // Tax Column
            tr.appendChild(createCell(this.formatter.format(Math.round(ltcgTax)), "px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap tax-col", taxDisplay));

            // Final Corpus Column
            tr.appendChild(createCell(this.formatter.format(finalCorpus), "px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap end-corpus-col"));

            fragment.appendChild(tr);
        });

        tbody.innerHTML = '';
        tbody.appendChild(fragment);
    }

    /**
     * Update summary stats block.
     */
    updateSummaryMetrics(data: YearResult[]): void {
        if (!data || data.length === 0) return;

        const lastRow = data[data.length - 1];
        const totalInvested = lastRow.cumulative_invested;
        const preTaxCorpus = lastRow.combined_total;
        const totalWithdrawn = lastRow.cumulative_withdrawals || 0;
        const preTaxGains = (preTaxCorpus + totalWithdrawn) - totalInvested;

        const showPostTax = (document.getElementById('show_post_tax') as HTMLInputElement | null)?.checked || false;
        
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
            const ltcgTax = lastRow.ltcg_tax ?? 0;
            finalCorpus = lastRow.post_tax_total ?? Math.max(0, preTaxCorpus - ltcgTax);
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

        const setVal = (id: string, val: number) => {
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
    syncSwpToggleState(): void {
        const isChecked = this.dom.getElement<HTMLInputElement>('enable_swp')?.checked || false;
        const fields = this.dom.getElement('swp-fields');
        if (!fields) return;

        document.querySelectorAll<HTMLElement>('.swp-col').forEach(el => {
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
     */
    setGoalMode(mode: string): void {
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

    setSmartNudgeRate(val: number): void {
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
    init(): void {
        const appContainer = document.getElementById('calculator-app');
        if (appContainer && appContainer.dataset.mode === 'target_corpus') {
            this.activeGoalMode = 'target';
        }

        this.initSliderSync();
        this.initGoalModeControls();
        this.initSwpHandlers();
        this.initToggles();

        new TabController().init();
        new SmartNudgeController(this.dom, (rate) => this.setSmartNudgeRate(rate)).init();
        new PdfExportController(
            this.dom,
            this.chartManager,
            this.analytics,
            () => this.getInputs(),
            () => this.latestResults,
            () => this.activeGoalMode,
            () => this.interactionCount
        ).init();
        new ShareController(this.dom, () => this.getInputs()).init();
        this.initResizeListeners();
        new UrlStateController(this.dom, () => this.syncSwpToggleState()).init();
        this.initEventBusSubscribers();
        this.initInitialCalculation();
    }

    private initSliderSync(): void {
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
    }

    private initGoalModeControls(): void {
        const growBtn = this.dom.getElement('goal-grow');
        const targetBtn = this.dom.getElement('goal-target');
        if (growBtn) {
            growBtn.addEventListener('click', () => this.setGoalMode('grow'));
        }
        if (targetBtn) {
            targetBtn.addEventListener('click', () => this.setGoalMode('target'));
        }

        const sipInput = this.dom.getElement('sip');
        const sipRange = this.dom.getElement('sip_range');
        const autoSwitchToGrow = () => {
            if (this.activeGoalMode === 'target') {
                this.setGoalMode('grow');
            }
        };
        if (sipInput) sipInput.addEventListener('input', autoSwitchToGrow);
        if (sipRange) sipRange.addEventListener('input', autoSwitchToGrow);
    }

    private initSwpHandlers(): void {
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
                    
                    const targetRangeEl = this.dom.getElement<HTMLInputElement>('target_corpus_range');
                    if (targetRangeEl) {
                        const defaultMax = parseFloat(targetRangeEl.getAttribute('max') || '50000000');
                        if (reqCorpus > defaultMax) {
                            targetRangeEl.max = String(reqCorpus);
                        } else {
                            targetRangeEl.max = String(defaultMax);
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
    }

    private initToggles(): void {
        const swpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (swpToggle) {
            swpToggle.addEventListener('change', () => this.syncSwpToggleState());
        }

        const postTaxToggle = document.getElementById('show_post_tax') as HTMLInputElement | null;
        if (postTaxToggle) {
            postTaxToggle.addEventListener('change', () => {
                const taxCols = document.querySelectorAll<HTMLElement>('.tax-col');
                taxCols.forEach(el => {
                    el.style.display = postTaxToggle.checked ? '' : 'none';
                });
                this.triggerCalculation();
            });
        }

        const wealthMapToggle = document.getElementById('show_wealth_map');
        if (wealthMapToggle) {
            wealthMapToggle.addEventListener('change', () => this.triggerCalculation());
        }
    }

    private initResizeListeners(): void {
        let resizeTimer: ReturnType<typeof setTimeout> | undefined;
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
    }

    private initEventBusSubscribers(): void {
        eventBus.subscribe('input:changed', (inputs: InvestmentInputs) => {
            if (!this.userHasInteracted) return;

            const combined = MathEngine.calculate(inputs);
            this.latestResults = combined;
            this.updateTable(combined, inputs.enable_swp);
            this.updateSummaryMetrics(combined);

            this.chartManager.updateChart(combined, inputs.enable_swp);
            this.analytics.logInsight(inputs, combined, this.activeGoalMode);
        });
    }

    private initInitialCalculation(): void {
        let swpEnabledOnLoad = false;
        const urlSwpOn = (new URLSearchParams(window.location.search)).get('swp_on') === '1';
        const initialSwpToggle = this.dom.getElement<HTMLInputElement>('enable_swp');
        if (initialSwpToggle && (initialSwpToggle.checked || urlSwpOn)) {
            swpEnabledOnLoad = true;
            if (urlSwpOn) initialSwpToggle.checked = true;
            this.syncSwpToggleState();
        } else if (document.querySelector<HTMLElement>('[data-js="calculator-app"]')?.dataset?.mode === 'swp') {
            swpEnabledOnLoad = true;
            if (initialSwpToggle) {
                initialSwpToggle.checked = true;
                this.syncSwpToggleState();
            }
        }

        const initialInputs = this.getInputs();
        let existingData: YearResult[] = [];
        try {
            existingData = MathEngine.calculate(initialInputs);
        } catch (e) {
            console.error("Initial JS Calculation Failed:", e);
        }

        if (existingData.length > 0) {
            this.latestResults = existingData;
            
            this.updateTable(existingData, swpEnabledOnLoad);
            this.updateSummaryMetrics(existingData);

            this.chartManager.updateChart(existingData, swpEnabledOnLoad);
        }
    }
}
