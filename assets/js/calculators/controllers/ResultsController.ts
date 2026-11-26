import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import { eventBus } from '../../utils/EventBus';
import { ModeTableSchema } from '../constants/ModeTableSchema';
import { renderMobileBreakdownView } from './MobileResultsRenderer';

const TABLE_ROW_CLASS = "even:bg-slate-100/70 hover:bg-emerald-50/70 border-b border-slate-200/60 transition-colors cursor-pointer";
const CELL_YEAR_CLASS = "px-3.5 lg:px-5 py-3.5 text-left font-extrabold text-slate-900 whitespace-nowrap sticky left-0 bg-white z-10 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.08)] border-b border-slate-100";
const CELL_MONO_CLASS = "px-3.5 lg:px-5 py-3.5 text-right font-mono tabular-nums text-slate-600 whitespace-nowrap";
const CELL_EMERALD_CLASS = "px-3.5 lg:px-5 py-3.5 text-right text-emerald-700 font-semibold font-mono tabular-nums whitespace-nowrap";
const CELL_MUTED_CLASS = "px-3.5 lg:px-5 py-3.5 text-right text-slate-600 font-mono tabular-nums whitespace-nowrap";
const CELL_ROSE_CLASS = "px-3.5 lg:px-5 py-3.5 text-right text-rose-700 font-semibold font-mono tabular-nums whitespace-nowrap";
const CELL_BOLD_CLASS = "px-3.5 lg:px-5 py-3.5 text-right font-bold text-slate-900 font-mono tabular-nums whitespace-nowrap";

export class ResultsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private density: 'all' | '5y' = '5y';
    private colDensity: 'essential' | 'audit' = 'essential';
    private denominationMode: 'exact' | 'lakh' = 'exact';
    private searchYear: number | null = null;
    private lastData: YearResult[] = [];
    private lastEnableSwp: boolean = true;
    private heatmapEnabled: boolean = false;
    private mobileExpanded: boolean = false;
    private mobileViewMode: 'carousel' | 'list' = 'carousel';

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.initControls();
        this.initEventSubscriptions();
    }

    public setDenominationMode(mode: 'exact' | 'lakh'): void {
        if (this.denominationMode === mode) return;
        this.denominationMode = mode;
        if (this.lastData.length > 0) {
            this.updateTable(this.lastData, this.lastEnableSwp);
        }
    }

    public getMobileViewMode(): 'carousel' | 'list' {
        return this.mobileViewMode;
    }

    private initControls(): void {
        const essentialBtn = this.dom.getElement('table-col-essential');
        const auditBtn = this.dom.getElement('table-col-audit');
        if (essentialBtn) {
            essentialBtn.addEventListener('click', () => this.setColumnDensity('essential'));
        }
        if (auditBtn) {
            auditBtn.addEventListener('click', () => this.setColumnDensity('audit'));
        }

        const allBtn = this.dom.getElement('table-density-all');
        const fiveYBtn = this.dom.getElement('table-density-5y');
        if (allBtn) {
            allBtn.addEventListener('click', () => this.setDensity('all'));
        }
        if (fiveYBtn) {
            fiveYBtn.addEventListener('click', () => this.setDensity('5y'));
        }

        const desktopToggleBtn = this.dom.getElement('desktop-toggle-all-years-btn');
        if (desktopToggleBtn) {
            desktopToggleBtn.addEventListener('click', () => {
                this.setDensity(this.density === '5y' ? 'all' : '5y');
            });
        }

        const heatmapBtn = this.dom.getElement('table-heatmap-toggle');
        if (heatmapBtn) {
            heatmapBtn.addEventListener('click', () => {
                this.heatmapEnabled = !this.heatmapEnabled;
                heatmapBtn.classList.toggle('bg-emerald-100', this.heatmapEnabled);
                heatmapBtn.classList.toggle('text-emerald-800', this.heatmapEnabled);
                heatmapBtn.classList.toggle('border-emerald-300', this.heatmapEnabled);
                const legend = this.dom.getElement('heatmap-legend');
                if (legend) {
                    legend.classList.toggle('hidden', !this.heatmapEnabled);
                    legend.classList.toggle('flex', this.heatmapEnabled);
                }
                if (this.lastData.length > 0) {
                    this.updateTable(this.lastData, this.lastEnableSwp);
                }
            });
        }

        const searchInput = this.dom.getElement<HTMLInputElement>('table-year-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const val = parseInt(searchInput.value, 10);
                this.searchYear = !isNaN(val) && val > 0 ? val : null;
                if (this.lastData.length > 0) {
                    this.updateTable(this.lastData, this.lastEnableSwp);
                }
            });
        }

        const mobileExpandBtn = this.dom.getElement('mobile-expand-all-years-btn');
        if (mobileExpandBtn) {
            mobileExpandBtn.addEventListener('click', () => {
                this.mobileExpanded = !this.mobileExpanded;
                const textEl = this.dom.getElement('expand-btn-text');
                if (textEl) {
                    textEl.textContent = this.mobileExpanded ? 'Collapse to 5Y Milestones' : 'Show All Years Breakdown';
                }
                if (this.lastData.length > 0) {
                    this.updateTable(this.lastData, this.lastEnableSwp);
                }
            });
        }

        const carouselBtn = this.dom.getElement('mobile-view-carousel-btn');
        const listBtn = this.dom.getElement('mobile-view-list-btn');
        const carouselWrapper = this.dom.getElement('mobile-breakdown-carousel-wrapper');
        const listWrapper = this.dom.getElement('mobile-breakdown-list-wrapper');

        if (carouselBtn && listBtn) {
            carouselBtn.addEventListener('click', () => {
                this.mobileViewMode = 'carousel';
                carouselBtn.classList.add('bg-white', 'text-growth-emphasis', 'shadow-flat', 'border', 'border-slate-200/60');
                carouselBtn.classList.remove('text-slate-600');
                carouselBtn.setAttribute('aria-selected', 'true');

                listBtn.classList.remove('bg-white', 'text-growth-emphasis', 'shadow-flat', 'border', 'border-slate-200/60');
                listBtn.classList.add('text-slate-600');
                listBtn.setAttribute('aria-selected', 'false');

                carouselWrapper?.classList.remove('hidden');
                carouselWrapper?.removeAttribute('hidden');
                if (carouselWrapper) {
                    carouselWrapper.style.display = '';
                    carouselWrapper.setAttribute('aria-hidden', 'false');
                }

                listWrapper?.classList.add('hidden');
                listWrapper?.setAttribute('hidden', '');
                if (listWrapper) {
                    listWrapper.style.display = 'none';
                    listWrapper.setAttribute('aria-hidden', 'true');
                }

                if (this.lastData.length > 0) {
                    this.updateTable(this.lastData, this.lastEnableSwp);
                }
            });

            listBtn.addEventListener('click', () => {
                this.mobileViewMode = 'list';
                listBtn.classList.add('bg-white', 'text-growth-emphasis', 'shadow-flat', 'border', 'border-slate-200/60');
                listBtn.classList.remove('text-slate-600');
                listBtn.setAttribute('aria-selected', 'true');

                carouselBtn.classList.remove('bg-white', 'text-growth-emphasis', 'shadow-flat', 'border', 'border-slate-200/60');
                carouselBtn.classList.add('text-slate-600');
                carouselBtn.setAttribute('aria-selected', 'false');

                listWrapper?.classList.remove('hidden');
                listWrapper?.removeAttribute('hidden');
                if (listWrapper) {
                    listWrapper.style.display = '';
                    listWrapper.setAttribute('aria-hidden', 'false');
                }

                carouselWrapper?.classList.add('hidden');
                carouselWrapper?.setAttribute('hidden', '');
                if (carouselWrapper) {
                    carouselWrapper.style.display = 'none';
                    carouselWrapper.setAttribute('aria-hidden', 'true');
                }

                if (this.lastData.length > 0) {
                    this.updateTable(this.lastData, this.lastEnableSwp);
                }
            });
        }
    }

    public setColumnDensity(mode: 'essential' | 'audit'): void {
        if (this.colDensity === mode) return;
        this.colDensity = mode;

        const essentialBtn = this.dom.getElement('table-col-essential');
        const auditBtn = this.dom.getElement('table-col-audit');
        if (essentialBtn && auditBtn) {
            if (mode === 'essential') {
                essentialBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                essentialBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                essentialBtn.setAttribute('aria-selected', 'true');
                auditBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                auditBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                auditBtn.setAttribute('aria-selected', 'false');
            } else {
                auditBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                auditBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                auditBtn.setAttribute('aria-selected', 'true');
                essentialBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                essentialBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                essentialBtn.setAttribute('aria-selected', 'false');
            }
        }

        if (this.lastData.length > 0) {
            this.updateTable(this.lastData, this.lastEnableSwp);
        }
    }

    public setDensity(density: 'all' | '5y'): void {
        if (this.density === density) return;
        this.density = density;

        const allBtn = this.dom.getElement('table-density-all');
        const fiveYBtn = this.dom.getElement('table-density-5y');
        if (allBtn && fiveYBtn) {
            if (density === 'all') {
                allBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                allBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                allBtn.setAttribute('aria-selected', 'true');
                fiveYBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                fiveYBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                fiveYBtn.setAttribute('aria-selected', 'false');
            } else {
                fiveYBtn.classList.add('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                fiveYBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                fiveYBtn.setAttribute('aria-selected', 'true');
                allBtn.classList.remove('bg-white', 'text-emerald-800', 'shadow-2xs', 'border', 'border-slate-200/60');
                allBtn.classList.add('text-slate-600', 'hover:text-slate-900');
                allBtn.setAttribute('aria-selected', 'false');
            }
        }

        if (this.lastData.length > 0) {
            this.updateTable(this.lastData, this.lastEnableSwp);
        }
    }

    private initEventSubscriptions(): void {
        eventBus.subscribe<{ index: number; scrollIntoView?: boolean }>('table:highlight', (data: { index: number; scrollIntoView?: boolean }) => {
            this.highlightTableRow(data.index, data.scrollIntoView ?? false);
        });
    }

    /**
     * Highlights matching table row during bi-directional Chart scrubbing.
     */
    public highlightTableRow(yearIndex: number, scrollIntoView: boolean = false): void {
        const rows = this.dom.getElements<HTMLTableRowElement>('#breakdown-body tr.stagger-row');
        rows.forEach((r) => {
            const rowYr = parseInt(r.dataset.year || '0', 10);
            if (rowYr === yearIndex + 1) {
                r.classList.add('bg-emerald-50/90', 'ring-1', 'ring-emerald-400/40');
                if (scrollIntoView) {
                    r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            } else {
                r.classList.remove('bg-emerald-50/90', 'ring-1', 'ring-emerald-400/40');
            }
        });
    }

    /**
     * Draw years breakdown logs securely using DOM node construction.
     */
    public updateTable(data: YearResult[], enableSwp: boolean): void {
        this.lastData = data;
        this.lastEnableSwp = enableSwp;

        const tbody = this.dom.getElement('breakdown-body');
        const mobileContainer = this.dom.getElement('mobile-breakdown-cards');
        if (!tbody && !mobileContainer) return;

        const fragment = document.createDocumentFragment();
        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;
        const inputs = this.getInputs();

        const isAudit = this.colDensity === 'audit';
        const showBeginBalance = isAudit;
        const showSipMonthly = isAudit;
        const showAnnualSip = isAudit;
        const showTotalInvested = true;
        const showSwpMonthly = enableSwp && isAudit;
        const showAnnualSwp = enableSwp && isAudit;
        const showTotalWithdrawn = enableSwp || data.some(r => (r.cumulative_withdrawals ?? 0) > 0);
        const showInterest = true;
        const showTax = showPostTax && isAudit;
        const showEndCorpus = true;

        const appMode = this.dom.getElement('calculator-app')?.dataset?.mode || '';
        const schema = ModeTableSchema.get(appMode);

        const setHeaderText = (id: string, text: string) => {
            const el = this.dom.getElement<HTMLElement>(id);
            if (el) el.textContent = text;
        };

        setHeaderText('th-year', schema.desktop.year);
        setHeaderText('th-begin-balance', schema.desktop.beginBalance);
        setHeaderText('th-sip-monthly', schema.desktop.sipMonthly);
        setHeaderText('th-annual-sip', schema.desktop.annualSip);
        setHeaderText('th-total-invested', schema.desktop.totalInvested);
        setHeaderText('th-swp-monthly', schema.desktop.swpMonthly);
        setHeaderText('th-annual-swp', schema.desktop.annualSwp);
        setHeaderText('th-total-withdrawn', schema.desktop.totalWithdrawn);
        setHeaderText('th-interest', schema.desktop.interest);
        setHeaderText('th-tax', schema.desktop.tax);
        setHeaderText('th-end-corpus', schema.desktop.endCorpus);

        setHeaderText('th-mobile-year', schema.mobileCompact.year);
        setHeaderText('th-mobile-invested', schema.mobileCompact.invested);
        setHeaderText('th-mobile-interest', schema.mobileCompact.interest);
        setHeaderText('th-mobile-corpus', schema.mobileCompact.corpus);

        // Synchronize <thead> headers strictly with active column visibility
        const setHeaderVisibility = (id: string, isVisible: boolean) => {
            const th = this.dom.getElement<HTMLElement>(id);
            if (th) {
                th.style.display = isVisible ? '' : 'none';
            }
        };

        setHeaderVisibility('th-year', true);
        setHeaderVisibility('th-begin-balance', showBeginBalance);
        setHeaderVisibility('th-sip-monthly', showSipMonthly);
        setHeaderVisibility('th-annual-sip', showAnnualSip);
        setHeaderVisibility('th-total-invested', showTotalInvested);
        setHeaderVisibility('th-swp-monthly', showSwpMonthly);
        setHeaderVisibility('th-annual-swp', showAnnualSwp);
        setHeaderVisibility('th-total-withdrawn', showTotalWithdrawn);
        setHeaderVisibility('th-interest', showInterest);
        setHeaderVisibility('th-tax', showTax);
        setHeaderVisibility('th-end-corpus', showEndCorpus);

        const visibleColsCount = 1 
            + (showBeginBalance ? 1 : 0)
            + (showSipMonthly ? 1 : 0)
            + (showAnnualSip ? 1 : 0)
            + (showTotalInvested ? 1 : 0)
            + (showSwpMonthly ? 1 : 0)
            + (showAnnualSwp ? 1 : 0)
            + (showTotalWithdrawn ? 1 : 0)
            + (showInterest ? 1 : 0)
            + (showTax ? 1 : 0)
            + (showEndCorpus ? 1 : 0);

        let filteredData = data;
        if (this.searchYear !== null) {
            filteredData = data.filter(r => r.year === this.searchYear);
        } else if (this.density === '5y') {
            if (data.length <= 15) {
                filteredData = data.filter(r => r.year === 1 || r.year % 2 === 0 || r.year === data.length);
            } else {
                filteredData = data.filter(r => r.year === 1 || r.year % 5 === 0 || r.year === data.length);
            }
        }

        const milestoneCounter = this.dom.getElement('desktop-table-milestone-counter');
        if (milestoneCounter) {
            milestoneCounter.textContent = this.density === 'all'
                ? `Showing All ${data.length} Annual Cashflows`
                : `Showing ${filteredData.length} Milestone Years`;
        }

        const desktopToggleText = this.dom.getElement('desktop-toggle-all-years-text');
        const desktopToggleIcon = this.dom.getElement('desktop-toggle-all-years-icon');
        if (desktopToggleText) {
            desktopToggleText.textContent = this.density === 'all'
                ? 'Collapse to 5Y Milestones'
                : `View All ${data.length} Years`;
        }
        if (desktopToggleIcon) {
            desktopToggleIcon.classList.toggle('rotate-180', this.density === 'all');
        }

        let ignitionRendered = false;

        filteredData.forEach((row, index) => {
            let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
            const ltcgTax = row.ltcg_tax ?? 0;

            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(
                    finalCorpus,
                    row.year,
                    inputs.inflation
                );
            }

            // Detect and inject Compounding Ignition Inflection Ribbon Row (SIP accumulation only)
            const isAccumulation = (appMode === 'sip' || appMode === 'sip-step-up' || appMode === 'combo' || appMode === '') && !inputs.enable_swp && (inputs.sip ?? 0) > 0 && (row.annual_contribution ?? 0) > 0;
            if (isAccumulation && !ignitionRendered && (row.interest ?? 0) >= (row.annual_contribution ?? 0) && row.year > 1 && row.cumulative_invested > 0) {
                ignitionRendered = true;
                const inflectionTr = document.createElement('tr');
                inflectionTr.className = 'bg-emerald-50/80 border-y border-emerald-300/80 font-sans';
                
                const td = document.createElement('td');
                td.colSpan = visibleColsCount;
                td.className = 'px-4 sm:px-6 py-2 text-center text-xs font-bold text-emerald-950 shadow-2xs';

                const badge = document.createElement('span');
                badge.className = 'inline-flex items-center gap-1.5 flex-wrap justify-center';
                badge.innerHTML = `<span class="text-sm">⚡</span> <strong>Year ${row.year} Compounding Ignition:</strong> Annual interest (+${this.formatter.format(row.interest)}) has surpassed annual SIP contributions (${this.formatter.format(row.annual_contribution)})!`;
                td.appendChild(badge);
                inflectionTr.appendChild(td);
                fragment.appendChild(inflectionTr);
            }

            const tr = document.createElement('tr');
            tr.className = `${TABLE_ROW_CLASS} stagger-row`;
            tr.dataset.year = String(row.year);
            tr.style.setProperty('--row-index', String(index));

            // Bi-directional Hover & Click sync with Chart via EventBus
            const yearIndex = row.year - 1;
            tr.addEventListener('mouseenter', () => eventBus.publish('chart:highlight', { index: yearIndex }));
            tr.addEventListener('mouseleave', () => eventBus.publish('chart:clearHighlight'));
            tr.addEventListener('click', () => {
                eventBus.publish('chart:scrub', { index: yearIndex, row });
            });

            const fmt = (v: number | null | undefined): string => {
                if (v === null || v === undefined) return '-';
                return this.denominationMode === 'lakh' ? this.formatter.formatDynamic(v) : this.formatter.format(v);
            };

            const createCell = (text: string, className: string): HTMLTableCellElement => {
                const td = document.createElement('td');
                td.className = className;
                td.textContent = text;
                return td;
            };

            // 1. Year Column (always visible)
            tr.appendChild(createCell(String(row.year), CELL_YEAR_CLASS));

            // 2. Secondary SIP Breakdown Columns (Audit mode only)
            if (showBeginBalance) tr.appendChild(createCell(fmt(row.begin_balance), CELL_MONO_CLASS));
            if (showSipMonthly) tr.appendChild(createCell(fmt(row.sip_monthly), CELL_EMERALD_CLASS));
            if (showAnnualSip) tr.appendChild(createCell(fmt(row.annual_contribution), CELL_EMERALD_CLASS));

            // 3. Total Invested Column (always visible)
            if (showTotalInvested) tr.appendChild(createCell(fmt(row.cumulative_invested), CELL_MUTED_CLASS));

            // 4. SWP Columns (SWP enabled only)
            if (showSwpMonthly) tr.appendChild(createCell(fmt(row.swp_monthly), CELL_ROSE_CLASS));
            if (showAnnualSwp) tr.appendChild(createCell(fmt(row.annual_withdrawal), CELL_ROSE_CLASS));
            if (showTotalWithdrawn) tr.appendChild(createCell(fmt(row.cumulative_withdrawals), CELL_MUTED_CLASS));

            // 5. Annual Gain / Mode-Specific Interest Column (always visible)
            if (showInterest) {
                const prefix = schema.mobileCard.interestPositivePrefix && row.interest >= 0 ? '+' : '';
                const interestClass = schema.mobileCard.isDebtOrCostMode ? CELL_MUTED_CLASS : CELL_EMERALD_CLASS;
                const interestCell = createCell(`${prefix}${fmt(row.interest)}`, interestClass);
                if (this.heatmapEnabled && !schema.mobileCard.isDebtOrCostMode && row.interest > 0) {
                    const maxInterest = Math.max(1, ...data.map(r => r.interest));
                    const intensity = Math.min(1, row.interest / maxInterest);
                    interestCell.style.backgroundColor = `rgba(16, 185, 129, ${(0.06 + intensity * 0.22).toFixed(2)})`;
                }
                tr.appendChild(interestCell);
            }

            // 6. LTCG Tax Column (Post-tax + Audit mode only)
            if (showTax) {
                tr.appendChild(createCell(fmt(Math.round(ltcgTax)), CELL_ROSE_CLASS));
            }

            // 7. End Corpus Column (always visible, with wealth multiplier badge)
            if (showEndCorpus) {
                const corpusCell = document.createElement('td');
                corpusCell.className = CELL_BOLD_CLASS;

                const corpusWrap = document.createElement('div');
                corpusWrap.className = 'flex items-center justify-end gap-1.5 font-mono';

                const corpusValDiv = document.createElement('span');
                corpusValDiv.className = 'font-extrabold text-slate-900';
                corpusValDiv.textContent = fmt(finalCorpus);
                corpusWrap.appendChild(corpusValDiv);

                if (schema.showMultiplier && row.cumulative_invested > 0 && finalCorpus > 0) {
                    const multiplier = (finalCorpus / row.cumulative_invested).toFixed(1);
                    const multiplierBadge = document.createElement('span');
                    multiplierBadge.className = 'px-1.5 py-0.2 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200/90 text-[10px] font-extrabold shadow-2xs';
                    multiplierBadge.textContent = `${multiplier}×`;
                    corpusWrap.appendChild(multiplierBadge);
                }
                corpusCell.appendChild(corpusWrap);
                tr.appendChild(corpusCell);
            }

            fragment.appendChild(tr);
        });

        if (tbody) {
            tbody.innerHTML = '';
            tbody.appendChild(fragment);
        }

        renderMobileBreakdownView({
            dom: this.dom,
            formatter: this.formatter,
            data,
            inputs,
            appMode,
            schema,
            enableSwp,
            showPostTax,
            denominationMode: this.denominationMode,
            heatmapEnabled: this.heatmapEnabled,
            density: this.density,
            mobileExpanded: this.mobileExpanded,
            mobileViewMode: this.mobileViewMode,
            searchYear: this.searchYear,
        });
    }
}
