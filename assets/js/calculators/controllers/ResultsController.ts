import { DOMAdapter } from '../../adapters/DOMAdapter';
import { CurrencyFormatter } from '../CurrencyHelper';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs, YearResult } from '../../types';

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
}
