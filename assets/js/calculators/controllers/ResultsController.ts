import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import type { ChartManager } from '../ChartManager';

const TABLE_ROW_CLASS = "hover:bg-emerald-50/50 border-b border-slate-100 transition-colors cursor-pointer";
const CELL_YEAR_CLASS = "px-4 sm:px-6 py-3.5 text-left font-extrabold text-slate-900 whitespace-nowrap sticky left-0 bg-white/98 backdrop-blur-md z-10 shadow-[2px_0_6px_-2px_rgba(0,0,0,0.08)] border-b border-slate-100";
const CELL_MONO_CLASS = "px-4 sm:px-6 py-3.5 text-right font-mono tabular-nums text-slate-600 whitespace-nowrap";
const CELL_EMERALD_CLASS = "px-4 sm:px-6 py-3.5 text-right text-emerald-700 font-semibold font-mono tabular-nums whitespace-nowrap";
const CELL_MUTED_CLASS = "px-4 sm:px-6 py-3.5 text-right text-slate-600 font-mono tabular-nums whitespace-nowrap";
const CELL_ROSE_CLASS = "px-4 sm:px-6 py-3.5 text-right text-rose-700 font-semibold font-mono tabular-nums whitespace-nowrap";
const CELL_BOLD_CLASS = "px-4 sm:px-6 py-3.5 text-right font-bold text-slate-900 font-mono tabular-nums whitespace-nowrap";

export class ResultsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private chartManager: ChartManager | null;
    private density: 'all' | '5y' = '5y';
    private colDensity: 'essential' | 'audit' = 'essential';
    private searchYear: number | null = null;
    private lastData: YearResult[] = [];
    private lastEnableSwp: boolean = true;
    private heatmapEnabled: boolean = false;
    private mobileExpanded: boolean = false;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs,
        chartManager: ChartManager | null = null
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
        this.chartManager = chartManager;
        this.initControls();
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
        const showTotalWithdrawn = enableSwp;
        const showInterest = true;
        const showTax = showPostTax && isAudit;
        const showEndCorpus = true;

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
            filteredData = data.filter(r => r.year === 1 || r.year % 5 === 0 || r.year === data.length);
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

            // Detect and inject Compounding Ignition Inflection Ribbon Row
            if (!ignitionRendered && (row.interest ?? 0) >= (row.annual_contribution ?? 0) && row.year > 1 && row.cumulative_invested > 0) {
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

            // Bi-directional Hover & Click sync with Chart
            if (this.chartManager) {
                const yearIndex = row.year - 1;
                tr.addEventListener('mouseenter', () => this.chartManager?.highlightYear(yearIndex));
                tr.addEventListener('mouseleave', () => this.chartManager?.clearHighlight());
                tr.addEventListener('click', () => {
                    this.chartManager?.highlightYear(yearIndex);
                    this.chartManager?.updateInspectionRibbon(row);
                });
            }

            const fmt = (v: number | null | undefined) => (v !== null && v !== undefined) ? this.formatter.format(v) : '-';

            const createCell = (text: string, className: string): HTMLTableCellElement => {
                const td = document.createElement('td');
                td.className = className;
                td.textContent = text;
                return td;
            };

            // 1. Year Column (always visible)
            tr.appendChild(createCell(String(row.year), CELL_YEAR_CLASS));

            // 2. Secondary SIP Breakdown Columns (Audit mode only)
            if (showBeginBalance) tr.appendChild(createCell(this.formatter.format(row.begin_balance), CELL_MONO_CLASS));
            if (showSipMonthly) tr.appendChild(createCell(fmt(row.sip_monthly), CELL_EMERALD_CLASS));
            if (showAnnualSip) tr.appendChild(createCell(this.formatter.format(row.annual_contribution), CELL_EMERALD_CLASS));

            // 3. Total Invested Column (always visible)
            if (showTotalInvested) tr.appendChild(createCell(this.formatter.format(row.cumulative_invested), CELL_MUTED_CLASS));

            // 4. SWP Columns (SWP enabled only)
            if (showSwpMonthly) tr.appendChild(createCell(fmt(row.swp_monthly), CELL_ROSE_CLASS));
            if (showAnnualSwp) tr.appendChild(createCell(fmt(row.annual_withdrawal), CELL_ROSE_CLASS));
            if (showTotalWithdrawn) tr.appendChild(createCell(fmt(row.cumulative_withdrawals), CELL_MUTED_CLASS));

            // 5. Annual Gain (always visible)
            if (showInterest) {
                const interestCell = createCell(`+${this.formatter.format(row.interest)}`, CELL_EMERALD_CLASS);
                if (this.heatmapEnabled && row.interest > 0) {
                    const maxInterest = Math.max(1, ...data.map(r => r.interest));
                    const intensity = Math.min(1, row.interest / maxInterest);
                    interestCell.style.backgroundColor = `rgba(16, 185, 129, ${(0.06 + intensity * 0.22).toFixed(2)})`;
                }
                tr.appendChild(interestCell);
            }

            // 6. LTCG Tax Column (Post-tax + Audit mode only)
            if (showTax) {
                tr.appendChild(createCell(this.formatter.format(Math.round(ltcgTax)), CELL_ROSE_CLASS));
            }

            // 7. End Corpus Column (always visible, with wealth multiplier badge)
            if (showEndCorpus) {
                const corpusCell = document.createElement('td');
                corpusCell.className = CELL_BOLD_CLASS;

                const corpusWrap = document.createElement('div');
                corpusWrap.className = 'flex items-center justify-end gap-1.5 font-mono';

                const corpusValDiv = document.createElement('span');
                corpusValDiv.className = 'font-extrabold text-slate-900';
                corpusValDiv.textContent = this.formatter.format(finalCorpus);
                corpusWrap.appendChild(corpusValDiv);

                if (row.cumulative_invested > 0 && finalCorpus > 0) {
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

        // Render Mobile Milestone Cards
        if (mobileContainer) {
            const mobileFragment = document.createDocumentFragment();
            const maxInterest = Math.max(1, ...data.map(r => r.interest));

            let mobileData = data;
            if (this.searchYear !== null) {
                mobileData = data.filter(r => r.year === this.searchYear);
            } else if (this.density === '5y' && !this.mobileExpanded) {
                mobileData = data.filter(r => r.year === 1 || r.year % 5 === 0 || r.year === data.length);
            }

            mobileData.forEach((row) => {
                const card = document.createElement('div');
                const isMilestone = row.year === 1 || row.year % 5 === 0 || row.year === data.length;
                const isFinal = row.year === data.length;

                card.className = "p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-2.5 transition-all";

                if (this.heatmapEnabled && row.interest > 0) {
                    const intensity = Math.min(1, row.interest / maxInterest);
                    card.style.backgroundColor = `rgba(16, 185, 129, ${(0.04 + intensity * 0.10).toFixed(2)})`;
                    card.style.borderColor = `rgba(16, 185, 129, ${(0.2 + intensity * 0.25).toFixed(2)})`;
                }

                let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
                if (inputs.inflation > 0) {
                    finalCorpus = MathEngine.calculateInflationDiscount(
                        finalCorpus,
                        row.year,
                        inputs.inflation
                    );
                }

                const header = document.createElement('div');
                header.className = "flex items-center justify-between";

                const yearBadge = document.createElement('span');
                yearBadge.className = isFinal
                    ? "px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-600 text-white shadow-2xs"
                    : (isMilestone ? "px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200/80" : "text-xs font-bold text-slate-700");
                yearBadge.textContent = `Year ${row.year}${isFinal ? ' (Maturity)' : ''}`;
                header.appendChild(yearBadge);

                const corpusWrap = document.createElement('div');
                corpusWrap.className = 'flex items-center gap-1.5';

                const corpusVal = document.createElement('span');
                corpusVal.className = "text-sm font-black font-financial-mono tabular-nums text-slate-900 whitespace-nowrap";
                corpusVal.textContent = this.formatter.format(finalCorpus);
                corpusWrap.appendChild(corpusVal);

                if (row.cumulative_invested > 0 && finalCorpus > 0) {
                    const multiplier = (finalCorpus / row.cumulative_invested).toFixed(1);
                    const multiplierBadge = document.createElement('span');
                    multiplierBadge.className = 'px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-extrabold shrink-0';
                    multiplierBadge.textContent = `${multiplier}×`;
                    corpusWrap.appendChild(multiplierBadge);
                }

                header.appendChild(corpusWrap);
                card.appendChild(header);

                const grid = document.createElement('div');
                grid.className = "grid grid-cols-2 gap-2 text-xs pt-1.5 border-t border-slate-100";

                const investedCol = document.createElement('div');
                const investedLabel = document.createElement('span');
                investedLabel.className = "text-slate-500 block text-[10px] font-bold";
                investedLabel.textContent = "Total Invested";
                const investedVal = document.createElement('span');
                investedVal.className = "font-bold font-financial-mono tabular-nums text-slate-800 whitespace-nowrap";
                investedVal.textContent = this.formatter.format(row.cumulative_invested);
                investedCol.appendChild(investedLabel);
                investedCol.appendChild(investedVal);
                grid.appendChild(investedCol);

                const interestCol = document.createElement('div');
                interestCol.className = "text-right";
                const interestLabel = document.createElement('span');
                interestLabel.className = "text-emerald-700 block text-[10px] font-bold";
                interestLabel.textContent = "Annual Gain";
                const interestVal = document.createElement('span');
                interestVal.className = "font-bold font-financial-mono tabular-nums text-emerald-700 whitespace-nowrap";
                interestVal.textContent = `+${this.formatter.format(row.interest)}`;
                interestCol.appendChild(interestLabel);
                interestCol.appendChild(interestVal);
                grid.appendChild(interestCol);

                if (enableSwp && (row.cumulative_withdrawals ?? 0) > 0) {
                    const withCol = document.createElement('div');
                    withCol.className = "col-span-2 pt-1 border-t border-slate-100 flex items-center justify-between text-xs";
                    const withLabel = document.createElement('span');
                    withLabel.className = "text-rose-700 text-[10px] font-bold";
                    withLabel.textContent = "Total Withdrawn";
                    const withVal = document.createElement('span');
                    withVal.className = "font-bold font-financial-mono tabular-nums text-rose-700 whitespace-nowrap";
                    withVal.textContent = this.formatter.format(row.cumulative_withdrawals ?? 0);
                    withCol.appendChild(withLabel);
                    withCol.appendChild(withVal);
                    grid.appendChild(withCol);
                }

                card.appendChild(grid);
                mobileFragment.appendChild(card);
            });

            mobileContainer.innerHTML = '';
            mobileContainer.appendChild(mobileFragment);
        }
    }
}
