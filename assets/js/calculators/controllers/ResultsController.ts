import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';
import type { ChartManager } from '../ChartManager';

const TABLE_ROW_CLASS = "hover:bg-emerald-50/40 border-b border-slate-100 transition-colors cursor-default";
const CELL_YEAR_CLASS = "px-6 py-4 font-bold text-slate-700 whitespace-nowrap";
const CELL_MONO_CLASS = "px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap";
const CELL_EMERALD_CLASS = "px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap";
const CELL_MUTED_CLASS = "px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap";
const CELL_ROSE_CLASS = "px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap";
const CELL_BOLD_CLASS = "px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap";

export class ResultsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;
    private chartManager: ChartManager | null;
    private density: 'all' | '5y' = 'all';
    private searchYear: number | null = null;
    private lastData: YearResult[] = [];
    private lastEnableSwp: boolean = true;

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
        const allBtn = document.getElementById('table-density-all');
        const fiveYBtn = document.getElementById('table-density-5y');
        if (allBtn) {
            allBtn.addEventListener('click', () => this.setDensity('all'));
        }
        if (fiveYBtn) {
            fiveYBtn.addEventListener('click', () => this.setDensity('5y'));
        }

        const searchInput = document.getElementById('table-year-search') as HTMLInputElement | null;
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

    setDensity(density: 'all' | '5y'): void {
        if (this.density === density) return;
        this.density = density;

        const allBtn = document.getElementById('table-density-all');
        const fiveYBtn = document.getElementById('table-density-5y');
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
        if (!tbody) return;

        const fragment = document.createDocumentFragment();
        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;
        const inputs = this.getInputs();

        let filteredData = data;
        if (this.searchYear !== null) {
            filteredData = data.filter(r => r.year === this.searchYear);
        } else if (this.density === '5y') {
            filteredData = data.filter(r => r.year % 5 === 0 || r.year === data.length);
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
            const swpDisplay = enableSwp ? '' : 'none';
            const taxDisplay = showPostTax ? '' : 'none';

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
            tr.appendChild(createCell(this.formatter.format(row.begin_balance), CELL_MONO_CLASS));
            tr.appendChild(createCell(fmt(row.sip_monthly), CELL_EMERALD_CLASS));
            tr.appendChild(createCell(this.formatter.format(row.annual_contribution), CELL_EMERALD_CLASS));
            tr.appendChild(createCell(this.formatter.format(row.cumulative_invested), CELL_MUTED_CLASS));

            // SWP Columns
            tr.appendChild(createCell(fmt(row.swp_monthly), CELL_ROSE_CLASS + " swp-col", swpDisplay));
            tr.appendChild(createCell(fmt(row.annual_withdrawal), CELL_ROSE_CLASS + " swp-col", swpDisplay));
            tr.appendChild(createCell(fmt(row.cumulative_withdrawals), CELL_MUTED_CLASS + " swp-col", swpDisplay));

            tr.appendChild(createCell(this.formatter.format(row.interest), CELL_EMERALD_CLASS));

            // Tax Column
            tr.appendChild(createCell(this.formatter.format(Math.round(ltcgTax)), CELL_ROSE_CLASS + " tax-col", taxDisplay));

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

        tbody.innerHTML = '';
        tbody.appendChild(fragment);
    }
}
