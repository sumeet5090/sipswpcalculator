import { DOMAdapter } from '../../adapters/DOMAdapter';
import { ChartManager } from '../ChartManager';
import { AnalyticsService } from '../AnalyticsLogger';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs, YearResult } from '../../types';

export class PdfExportController {
    private dom: DOMAdapter;
    private chartManager: ChartManager;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];
    private getActiveGoalMode: () => string;
    private getInteractionCount: () => number;
    private formatter: CurrencyFormatter;

    constructor(
        dom: DOMAdapter,
        chartManager: ChartManager,
        analytics: AnalyticsService,
        getInputs: () => InvestmentInputs,
        getLatestResults: () => YearResult[],
        getActiveGoalMode: () => string,
        getInteractionCount: () => number,
        formatter: CurrencyFormatter = new CurrencyFormatter()
    ) {
        this.dom = dom;
        this.chartManager = chartManager;
        this.analytics = analytics;
        this.getInputs = getInputs;
        this.getLatestResults = getLatestResults;
        this.getActiveGoalMode = getActiveGoalMode;
        this.getInteractionCount = getInteractionCount;
        this.formatter = formatter;
    }

    init(): void {
        const pdfModal = this.dom.getElement<HTMLDialogElement>('pdfModal');
        const openPdfBtn = this.dom.getElement('openPdfModalBtn');
        const closePdfBtn = this.dom.getElement('closePdfModalBtn');
        const pdfForm = this.dom.getElement<HTMLFormElement>('pdfForm');

        const openModalFn = (el: HTMLDialogElement | HTMLElement | null) => {
            if (!el) return;
            if ('showModal' in el && typeof (el as HTMLDialogElement).showModal === 'function') {
                (el as HTMLDialogElement).showModal();
            } else {
                el.classList.remove('hidden');
            }
        };

        const closeModalFn = (el: HTMLDialogElement | HTMLElement | null) => {
            if (!el) return;
            if ('close' in el && typeof (el as HTMLDialogElement).close === 'function') {
                (el as HTMLDialogElement).close();
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
            pdfModal.addEventListener('click', (e: Event) => {
                if (e.target === pdfModal) closeModalFn(pdfModal);
            });
        }

        const fastInstantPdfBtn = this.dom.getElement('fastInstantPdfBtn');
        if (fastInstantPdfBtn && pdfForm) {
            fastInstantPdfBtn.addEventListener('click', () => {
                const clientInput = this.dom.getElement<HTMLInputElement>('clientName');
                if (clientInput && !clientInput.value) {
                    clientInput.value = 'Valued Investor';
                }
                pdfForm.requestSubmit();
            });
        }

        if (pdfForm) {
            pdfForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const generatePdfBtn = this.dom.getElement<HTMLButtonElement>('generatePdfBtn');
                if (generatePdfBtn) {
                    generatePdfBtn.disabled = true;
                    generatePdfBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating PDF...`;
                }

                const chartInst = this.chartManager.getChartInstance();
                let chartDataURL = '';
                if (chartInst && chartInst.canvas) {
                    try {
                        chartInst.stop();
                        chartDataURL = chartInst.toBase64Image('image/png', 1.0);
                    } catch (e) {
                        console.warn("Could not capture chart as Base64 image:", e);
                    }
                }
                const resultsTable = this.dom.getElement('results-table');
                const tableHtml = resultsTable ? resultsTable.outerHTML : '<table><tr><td>No data available.</td></tr></table>';

                const formData = new FormData(pdfForm);
                const currentInputs = this.getInputs();

                formData.append('sip', String(currentInputs.sip));
                formData.append('years', String(currentInputs.years));
                formData.append('rate', String(currentInputs.rate));
                formData.append('stepup', String(currentInputs.stepup));
                formData.append('lumpsum', String(currentInputs.lumpsum));
                formData.append('inflation', String(currentInputs.inflation));
                formData.append('enable_swp', currentInputs.enable_swp ? '1' : '0');
                formData.append('swp_withdrawal', String(currentInputs.swp_withdrawal));
                formData.append('swp_stepup', String(currentInputs.swp_stepup));
                formData.append('swp_years', String(currentInputs.swp_years));
                formData.append('swp_rate', String(currentInputs.swp_rate));

                formData.append('currency_symbol', this.formatter.getSymbol());
                formData.append('currency', this.formatter.getCurrency());
                formData.append('summary_invested', this.dom.getElement('summary-invested')?.textContent?.trim() || '0');
                formData.append('summary_interest', this.dom.getElement('summary-interest')?.textContent?.trim() || '0');
                formData.append('summary_withdrawn', this.dom.getElement('summary-withdrawn')?.textContent?.trim() || '0');
                formData.append('summary_corpus', this.dom.getElement('summary-corpus')?.textContent?.trim() || '0');

                const latestResults = this.getLatestResults();
                const lastRow = (Array.isArray(latestResults) && latestResults.length > 0)
                    ? latestResults[latestResults.length - 1]
                    : null;
                formData.append('raw_invested', String(lastRow ? (lastRow.cumulative_invested || 0) : 0));
                formData.append('raw_corpus', String(lastRow ? (lastRow.combined_total || 0) : 0));

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
                            generatePdfBtn.innerHTML = 'Download PDF';
                        }
                        closeModalFn(pdfModal);
                        a.remove();

                        // Log PDF telemetry immediately using AnalyticsLogger (CQS Fix)
                        const inputs = this.getInputs();
                        const advisorNameStr = (formData.get('advisorName') || '').toString().trim();
                        const pdfHasCustomName = advisorNameStr.length > 0;

                        this.analytics.sendImmediateInsight(inputs, this.getLatestResults(), this.getActiveGoalMode(), {
                            pdf_downloaded: true,
                            pdf_has_custom_name: pdfHasCustomName,
                            exit_action: 'pdf_download',
                            interaction_count: this.getInteractionCount()
                        });

                        setTimeout(() => window.URL.revokeObjectURL(url), 100);
                    })
                    .catch(err => {
                        console.error('PDF generation failed:', err.message);
                        if (generatePdfBtn) {
                            generatePdfBtn.disabled = false;
                            generatePdfBtn.innerHTML = 'Download PDF';
                        }
                    });
            });
        }
    }
}
