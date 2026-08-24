import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import type { ChartManager } from '../ChartManager';

const TABLE_ROW_CLASS = "hover:bg-emerald-50/40 border-b border-slate-100 transition-colors cursor-default";
const CELL_YEAR_CLASS = "px-6 py-4 font-bold text-slate-800 whitespace-nowrap sticky left-0 bg-white/95 backdrop-blur-md z-10 shadow-[1px_0_4px_rgba(0,0,0,0.04)]";
const CELL_MONO_CLASS = "px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap";
const CELL_EMERALD_CLASS = "px-6 py-4 text-right text-emerald-700 font-medium font-mono whitespace-nowrap";
const CELL_MUTED_CLASS = "px-6 py-4 text-right text-slate-600 font-mono whitespace-nowrap";
const CELL_ROSE_CLASS = "px-6 py-4 text-right text-rose-700 font-medium font-mono whitespace-nowrap";
const CELL_BOLD_CLASS = "px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap";

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
    }

    setColumnDensity(mode: 'essential' | 'audit'): void {
        if (this.colDensity === mode) return;
        this.colDensity = mode;

        const essentialBtn = this.dom.getElement('table-col-essential');
        const auditBtn = this.dom.getElement('table-col-audit');
        if (essentialBtn && auditBtn) {
            if (mode === 'essential') {
                essentialBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                essentialBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                essentialBtn.setAttribute('aria-selected', 'true');
                auditBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                auditBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                auditBtn.setAttribute('aria-selected', 'false');
            } else {
                auditBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                auditBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                auditBtn.setAttribute('aria-selected', 'true');
                essentialBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                essentialBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                essentialBtn.setAttribute('aria-selected', 'false');
            }
        }

        // Toggle secondary column visibility in header
        const secondaryHeaders = this.dom.getElements<HTMLElement>('th.col-secondary');
        secondaryHeaders.forEach(th => {
            th.style.display = (mode === 'essential') ? 'none' : '';
        });

        if (this.lastData.length > 0) {
            this.updateTable(this.lastData, this.lastEnableSwp);
        }
    }

    setDensity(density: 'all' | '5y'): void {
        if (this.density === density) return;
        this.density = density;

        const allBtn = this.dom.getElement('table-density-all');
        const fiveYBtn = this.dom.getElement('table-density-5y');
        if (allBtn && fiveYBtn) {
            if (density === 'all') {
                allBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                allBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                allBtn.setAttribute('aria-selected', 'true');
                fiveYBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                fiveYBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                fiveYBtn.setAttribute('aria-selected', 'false');
            } else {
                fiveYBtn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                fiveYBtn.classList.remove('text-slate-500', 'hover:text-slate-700');
                fiveYBtn.setAttribute('aria-selected', 'true');
                allBtn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm', 'border', 'border-slate-200/40');
                allBtn.classList.add('text-slate-500', 'hover:text-slate-700');
                allBtn.setAttribute('aria-selected', 'false');
            }
        }

        if (this.lastData.length > 0) {
            this.updateTable(this.lastData, this.lastEnableSwp);
        }
    }

    /**
     * Draw years breakdown logs securely using DOM node construction.
     */
    updateTable(data: YearResult[], enableSwp: boolean): void {
        this.lastData = data;
        this.lastEnableSwp = enableSwp;

        const tbody = this.dom.getElement('breakdown-body');
        const mobileContainer = this.dom.getElement('mobile-breakdown-cards');
        if (!tbody && !mobileContainer) return;

        const fragment = document.createDocumentFragment();
        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;
        const inputs = this.getInputs();

        // Update secondary headers display based on column density mode
        const secondaryHeaders = this.dom.getElements<HTMLElement>('th.col-secondary');
        secondaryHeaders.forEach(th => {
            th.style.display = (this.colDensity === 'essential') ? 'none' : '';
        });

        let filteredData = data;
        if (this.searchYear !== null) {
            filteredData = data.filter(r => r.year === this.searchYear);
        } else if (this.density === '5y') {
            filteredData = data.filter(r => r.year === 1 || r.year % 5 === 0 || r.year === data.length);
        }

        filteredData.forEach((row, index) => {
            const tr = document.createElement('tr');
            tr.className = `${TABLE_ROW_CLASS} stagger-row`;
            tr.style.setProperty('--row-index', String(index));

            // Hover sync with Chart
            if (this.chartManager) {
                const yearIndex = row.year - 1;
                tr.addEventListener('mouseenter', () => this.chartManager?.highlightYear(yearIndex));
                tr.addEventListener('mouseleave', () => this.chartManager?.clearHighlight());
            }

            const fmt = (v: number | null | undefined) => (v !== null && v !== undefined) ? this.formatter.format(v) : '-';
            const swpDisplay = enableSwp ? (this.colDensity === 'essential' ? 'none' : '') : 'none';
            const taxDisplay = showPostTax ? (this.colDensity === 'essential' ? 'none' : '') : 'none';
            const secondaryDisplay = (this.colDensity === 'essential') ? 'none' : '';

            let finalCorpus = showPostTax ? (row.post_tax_total ?? row.combined_total) : row.combined_total;
            const ltcgTax = row.ltcg_tax ?? 0;

            if (inputs.inflation > 0) {
                finalCorpus = MathEngine.calculateInflationDiscount(
                    finalCorpus,
                    row.year,
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

            tr.appendChild(createCell(String(row.year), CELL_YEAR_CLASS));
            tr.appendChild(createCell(this.formatter.format(row.begin_balance), CELL_MONO_CLASS + " col-secondary", secondaryDisplay));
            tr.appendChild(createCell(fmt(row.sip_monthly), CELL_EMERALD_CLASS + " col-secondary", secondaryDisplay));
            tr.appendChild(createCell(this.formatter.format(row.annual_contribution), CELL_EMERALD_CLASS + " col-secondary", secondaryDisplay));
            tr.appendChild(createCell(this.formatter.format(row.cumulative_invested), CELL_MUTED_CLASS));

            // SWP Columns
            tr.appendChild(createCell(fmt(row.swp_monthly), CELL_ROSE_CLASS + " swp-col col-secondary", swpDisplay));
            tr.appendChild(createCell(fmt(row.annual_withdrawal), CELL_ROSE_CLASS + " swp-col col-secondary", swpDisplay));
            tr.appendChild(createCell(fmt(row.cumulative_withdrawals), CELL_MUTED_CLASS + " swp-col", enableSwp ? '' : 'none'));

            const interestCell = createCell(this.formatter.format(row.interest), CELL_EMERALD_CLASS);
            if (this.heatmapEnabled && row.interest > 0) {
                const maxInterest = Math.max(1, ...data.map(r => r.interest));
                const intensity = Math.min(1, row.interest / maxInterest);
                interestCell.style.backgroundColor = `rgba(16, 185, 129, ${(0.06 + intensity * 0.24).toFixed(2)})`;
            }
            tr.appendChild(interestCell);

            // Tax Column
            tr.appendChild(createCell(this.formatter.format(Math.round(ltcgTax)), CELL_ROSE_CLASS + " tax-col col-secondary", taxDisplay));

            // Final Corpus Column
            const corpusCell = document.createElement('td');
            corpusCell.className = CELL_BOLD_CLASS + " end-corpus-col";

            const corpusValDiv = document.createElement('div');
            corpusValDiv.textContent = this.formatter.format(finalCorpus);
            corpusCell.appendChild(corpusValDiv);

            if (row.cumulative_invested > 0 && finalCorpus > 0) {
                const investedRatio = Math.min((row.cumulative_invested / finalCorpus) * 100, 100);
                const gainsRatio = Math.max(100 - investedRatio, 0);

                const miniBar = document.createElement('div');
                miniBar.className = "w-full bg-slate-200/80 rounded-full h-1 mt-1 flex overflow-hidden";
                miniBar.title = `Invested: ${investedRatio.toFixed(0)}% • Gains: ${gainsRatio.toFixed(0)}%`;

                const investedSeg = document.createElement('div');
                investedSeg.className = "bg-slate-400 h-full";
                investedSeg.style.width = `${investedRatio}%`;

                const gainsSeg = document.createElement('div');
                gainsSeg.className = "bg-emerald-500 h-full";
                gainsSeg.style.width = `${gainsRatio}%`;

                miniBar.appendChild(investedSeg);
                miniBar.appendChild(gainsSeg);
                corpusCell.appendChild(miniBar);
            }

            tr.appendChild(corpusCell);
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

            filteredData.forEach((row) => {
                const card = document.createElement('div');
                const isMilestone = row.year === 1 || row.year % 5 === 0 || row.year === data.length;
                const isFinal = row.year === data.length;

                card.className = "p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-2.5 transition-all";

                if (this.heatmapEnabled && row.interest > 0) {
                    const intensity = Math.min(1, row.interest / maxInterest);
                    card.style.backgroundColor = `rgba(16, 185, 129, ${(0.04 + intensity * 0.12).toFixed(2)})`;
                    card.style.borderColor = `rgba(16, 185, 129, ${(0.2 + intensity * 0.3).toFixed(2)})`;
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
                    ? "px-2.5 py-0.5 rounded-lg text-xs font-black bg-emerald-600 text-white"
                    : (isMilestone ? "px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800" : "text-xs font-bold text-slate-700");
                yearBadge.textContent = `Year ${row.year}${isFinal ? ' (Maturity)' : ''}`;
                header.appendChild(yearBadge);

                const corpusVal = document.createElement('span');
                corpusVal.className = "text-sm font-black font-mono text-slate-900";
                corpusVal.textContent = this.formatter.format(finalCorpus);
                header.appendChild(corpusVal);

                card.appendChild(header);

                const grid = document.createElement('div');
                grid.className = "grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-100";

                const investedCol = document.createElement('div');
                const investedLabel = document.createElement('span');
                investedLabel.className = "text-slate-600 block text-[10px]";
                investedLabel.textContent = "Total Invested";
                const investedVal = document.createElement('span');
                investedVal.className = "font-bold font-mono text-slate-800";
                investedVal.textContent = this.formatter.format(row.cumulative_invested);
                investedCol.appendChild(investedLabel);
                investedCol.appendChild(investedVal);
                grid.appendChild(investedCol);

                const interestCol = document.createElement('div');
                interestCol.className = "text-right";
                const interestLabel = document.createElement('span');
                interestLabel.className = "text-emerald-700 block text-[10px]";
                interestLabel.textContent = "Interest / Yr";
                const interestVal = document.createElement('span');
                interestVal.className = "font-bold font-mono text-emerald-700";
                interestVal.textContent = `+${this.formatter.format(row.interest)}`;
                interestCol.appendChild(interestLabel);
                interestCol.appendChild(interestVal);
                grid.appendChild(interestCol);

                if (enableSwp && (row.cumulative_withdrawals ?? 0) > 0) {
                    const withCol = document.createElement('div');
                    withCol.className = "col-span-2 pt-1 border-t border-slate-100 flex items-center justify-between text-xs";
                    const withLabel = document.createElement('span');
                    withLabel.className = "text-rose-700 text-[10px]";
                    withLabel.textContent = "Total Withdrawn";
                    const withVal = document.createElement('span');
                    withVal.className = "font-bold font-mono text-rose-700";
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
