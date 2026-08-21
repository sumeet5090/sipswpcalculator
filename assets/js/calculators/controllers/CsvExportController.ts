import { DOMAdapter } from '../../adapters/DOMAdapter';
import { AnalyticsService } from '../AnalyticsLogger';
import { InvestmentInputs } from '../../types';

/**
 * CsvExportController.ts
 * Dedicated Single Responsibility controller managing CSV export downloads.
 * Fetches the CSV file from /download-csv and initiates client-side blob download with UI feedback.
 */
export class CsvExportController {
    private dom: DOMAdapter;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;

    constructor(
        dom: DOMAdapter,
        analytics: AnalyticsService,
        getInputs: () => InvestmentInputs
    ) {
        this.dom = dom;
        this.analytics = analytics;
        this.getInputs = getInputs;
    }

    init(): void {
        const downloadBtn = this.dom.getElement<HTMLButtonElement>('downloadCsvBtn')
            || document.querySelector<HTMLButtonElement>('button[value="download_csv"]');

        if (!downloadBtn) return;

        downloadBtn.addEventListener('click', async (e: Event) => {
            e.preventDefault();

            const origContent = downloadBtn.innerHTML;
            downloadBtn.disabled = true;
            downloadBtn.classList.add('opacity-75', 'cursor-wait');

            try {
                const inputs = this.getInputs();
                const formData = new FormData();

                Object.entries(inputs).forEach(([key, val]) => {
                    if (val !== undefined && val !== null) {
                        formData.append(key, String(val));
                    }
                });

                const response = await fetch('/download-csv', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`CSV export server returned HTTP ${response.status}`);
                }

                const blob = await response.blob();
                const disposition = response.headers.get('Content-Disposition');
                let filename = 'SIP_SWP_Yearly_Report.csv';
                if (disposition && disposition.includes('filename=')) {
                    const match = disposition.match(/filename="?([^";]+)"?/);
                    if (match && match[1]) {
                        filename = match[1].trim();
                    }
                }

                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                this.analytics.sendImmediateInsight(inputs, [], 'grow', {
                    exit_action: 'csv_download'
                });
            } catch (err) {
                console.error('CSV download failed:', err);
                // Graceful fallback: trigger native form post
                const form = this.dom.getElement<HTMLFormElement>('calculator-form');
                if (form) {
                    const originalAction = form.action;
                    form.action = '/download-csv';
                    form.submit();
                    form.action = originalAction;
                }
            } finally {
                downloadBtn.disabled = false;
                downloadBtn.classList.remove('opacity-75', 'cursor-wait');
                downloadBtn.innerHTML = origContent;
            }
        });
    }
}
