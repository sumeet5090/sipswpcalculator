import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';

const TABLE_ROW_CLASS = "hover:bg-slate-50 border-b border-slate-100 transition-colors";
const CELL_YEAR_CLASS = "px-6 py-4 font-medium text-slate-700 whitespace-nowrap";
const CELL_MONO_CLASS = "px-6 py-4 text-right font-mono text-slate-600 whitespace-nowrap";
const CELL_EMERALD_CLASS = "px-6 py-4 text-right text-emerald-600 font-medium font-mono whitespace-nowrap";
const CELL_MUTED_CLASS = "px-6 py-4 text-right text-slate-500 font-mono whitespace-nowrap";
const CELL_ROSE_CLASS = "px-6 py-4 text-right text-rose-500 font-medium font-mono whitespace-nowrap";
const CELL_BOLD_CLASS = "px-6 py-4 text-right font-bold text-slate-800 font-mono whitespace-nowrap";

export class ResultsController {
    private dom: DOMAdapter;
    private formatter: CurrencyFormatter;
    private getInputs: () => InvestmentInputs;

    constructor(
        dom: DOMAdapter,
        formatter: CurrencyFormatter,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.formatter = formatter;
        this.getInputs = getInputs;
    }

    /**
     * Draw years breakdown logs securely using DOM node construction.
     */
    updateTable(data: YearResult[], enableSwp: boolean): void {
        const tbody = this.dom.getElement('breakdown-body');
        if (!tbody) return;

        const fragment = document.createDocumentFragment();
        const postTaxToggle = this.dom.getElement<HTMLInputElement>('show_post_tax');
        const showPostTax = postTaxToggle?.checked || false;
        const inputs = this.getInputs();

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = TABLE_ROW_CLASS;

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
            tr.appendChild(createCell(this.formatter.format(finalCorpus), CELL_BOLD_CLASS + " end-corpus-col"));

            fragment.appendChild(tr);
        });

        tbody.innerHTML = '';
        tbody.appendChild(fragment);
    }
}
