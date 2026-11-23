import { DOMAdapter } from '../../adapters/DOMAdapter';
import { ChartManager } from '../ChartManager';
import { AnalyticsService } from '../AnalyticsLogger';
import { CurrencyFormatter } from '../CurrencyHelper';
import { InvestmentInputs, YearResult } from '../../types';

export interface AdvisorProfile {
    advisorName?: string;
    advisorFirm?: string;
    advisorRegNo?: string;
    advisorContact?: string;
    customDisclaimer?: string;
    logoBase64?: string;
    logoFilename?: string;
}

export class PdfExportController {
    private static readonly STORAGE_KEY = 'advisor_profile_v1';

    private dom: DOMAdapter;
    private chartManager: ChartManager;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];
    private getActiveGoalMode: () => string;
    private getInteractionCount: () => number;
    private formatter: CurrencyFormatter;
    private getExportPayload?: () => Record<string, string | number | boolean>;

    private cachedLogoBase64: string | null = null;

    constructor(
        dom: DOMAdapter,
        chartManager: ChartManager,
        analytics: AnalyticsService,
        getInputs: () => InvestmentInputs,
        getLatestResults: () => YearResult[],
        getActiveGoalMode: () => string,
        getInteractionCount: () => number,
        formatter: CurrencyFormatter = new CurrencyFormatter(),
        getExportPayload?: () => Record<string, string | number | boolean>
    ) {
        this.dom = dom;
        this.chartManager = chartManager;
        this.analytics = analytics;
        this.getInputs = getInputs;
        this.getLatestResults = getLatestResults;
        this.getActiveGoalMode = getActiveGoalMode;
        this.getInteractionCount = getInteractionCount;
        this.formatter = formatter;
        this.getExportPayload = getExportPayload;
    }

    init(): void {
        const pdfModal = this.dom.getElement<HTMLDialogElement>('pdfModal');
        const openPdfBtn = this.dom.getElement('openPdfModalBtn');
        const customizePdfBtn = this.dom.getElement('customizePdfModalBtn');
        const closePdfBtn = this.dom.getElement('closePdfModalBtn');
        const closePdfBtnX = this.dom.getElement('closePdfModalBtnX');
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

        // 1-Click Fast Direct Download as Default
        if (openPdfBtn) {
            openPdfBtn.addEventListener('click', () => {
                if (!this.chartManager.getChartInstance()) {
                    const origText = openPdfBtn.querySelector('svg + span, span')?.textContent || 'Download PDF';
                    openPdfBtn.classList.add('border-rose-300', 'text-rose-600');
                    const span = openPdfBtn.querySelector('svg + span, span') || openPdfBtn;
                    span.textContent = 'Calculate first!';
                    setTimeout(() => {
                        openPdfBtn.classList.remove('border-rose-300', 'text-rose-600');
                        span.textContent = origText;
                    }, 2500);
                    return;
                }
                this.executeDirectDownload(openPdfBtn);
            });
        }

        // Optional Customized / Branded PDF Trigger
        if (customizePdfBtn && pdfModal) {
            customizePdfBtn.addEventListener('click', () => {
                openModalFn(pdfModal);
            });
        }

        if (pdfModal) {
            if (closePdfBtn) {
                closePdfBtn.addEventListener('click', () => closeModalFn(pdfModal));
            }
            if (closePdfBtnX) {
                closePdfBtnX.addEventListener('click', () => closeModalFn(pdfModal));
            }
            pdfModal.addEventListener('click', (e: Event) => {
                if (e.target === pdfModal) closeModalFn(pdfModal);
            });
        }

        // Form Inputs
        const clientInput = this.dom.getElement<HTMLInputElement>('clientName');
        const proposalTitleInput = this.dom.getElement<HTMLInputElement>('proposalTitle');
        const advisorInput = this.dom.getElement<HTMLInputElement>('advisorName');
        const firmInput = this.dom.getElement<HTMLInputElement>('advisorFirm');
        const regInput = this.dom.getElement<HTMLInputElement>('advisorRegNo');
        const contactInput = this.dom.getElement<HTMLInputElement>('advisorContact');
        const logoInput = this.dom.getElement<HTMLInputElement>('advisorLogo');
        const disclaimerInput = this.dom.getElement<HTMLTextAreaElement>('customDisclaimer');
        const rememberCheckbox = this.dom.getElement<HTMLInputElement>('rememberProfile');
        const clearProfileBtn = this.dom.getElement('clearAdvisorProfileBtn');
        const removeLogoBtn = this.dom.getElement('removeLogoBtn');

        // Preview Elements
        const previewClient = this.dom.getElement('preview-client-name');
        const previewTitle = this.dom.getElement('preview-proposal-title');
        const previewAdvisor = this.dom.getElement('preview-advisor-name');
        const previewFirm = this.dom.getElement('preview-firm-name');
        const previewRegNo = this.dom.getElement('preview-reg-no');
        const previewLogoImg = this.dom.getElement<HTMLImageElement>('preview-logo-img');
        const logoPreviewContainer = this.dom.getElement('logo-preview-container');
        const logoPreviewImg = this.dom.getElement<HTMLImageElement>('logo-preview-img');
        const logoPreviewFilename = this.dom.getElement('logo-preview-filename');

        const updateLivePreview = () => {
            if (previewClient && clientInput) {
                previewClient.textContent = clientInput.value.trim() || 'Valued Investor';
            }
            if (previewTitle && proposalTitleInput) {
                previewTitle.textContent = proposalTitleInput.value.trim() || 'Mutual Fund Investment Plan';
            }
            if (previewAdvisor && advisorInput) {
                previewAdvisor.textContent = advisorInput.value.trim() || 'Financial Advisor';
            }
            if (previewFirm && firmInput) {
                const firmVal = firmInput.value.trim();
                if (firmVal) {
                    previewFirm.textContent = firmVal;
                    previewFirm.classList.remove('hidden');
                } else {
                    previewFirm.classList.add('hidden');
                }
            }
            if (previewRegNo && regInput) {
                const regVal = regInput.value.trim();
                if (regVal) {
                    previewRegNo.textContent = regVal;
                    previewRegNo.classList.remove('hidden');
                } else {
                    previewRegNo.classList.add('hidden');
                }
            }
            if (previewLogoImg) {
                if (this.cachedLogoBase64) {
                    previewLogoImg.src = this.cachedLogoBase64;
                    previewLogoImg.classList.remove('hidden');
                } else {
                    previewLogoImg.classList.add('hidden');
                    previewLogoImg.src = '';
                }
            }
        };

        // Hydrate from LocalStorage
        try {
            const rawStored = localStorage.getItem(PdfExportController.STORAGE_KEY);
            if (rawStored) {
                const profile: AdvisorProfile = JSON.parse(rawStored);
                if (advisorInput && profile.advisorName) advisorInput.value = profile.advisorName;
                if (firmInput && profile.advisorFirm) firmInput.value = profile.advisorFirm;
                if (regInput && profile.advisorRegNo) regInput.value = profile.advisorRegNo;
                if (contactInput && profile.advisorContact) contactInput.value = profile.advisorContact;
                if (disclaimerInput && profile.customDisclaimer) disclaimerInput.value = profile.customDisclaimer;
                if (rememberCheckbox) rememberCheckbox.checked = true;

                if (profile.logoBase64 && previewLogoImg && logoPreviewContainer && logoPreviewImg) {
                    this.cachedLogoBase64 = profile.logoBase64;
                    logoPreviewImg.src = profile.logoBase64;
                    if (logoPreviewFilename) logoPreviewFilename.textContent = profile.logoFilename || 'practice-logo.png';
                    logoPreviewContainer.classList.remove('hidden');
                    logoPreviewContainer.classList.add('flex');
                }
            }
        } catch (e) {
            console.warn('[PdfExportController] LocalStorage read failed:', e);
        }

        updateLivePreview();

        // Reactive Listeners
        [clientInput, proposalTitleInput, advisorInput, firmInput, regInput, contactInput].forEach(inp => {
            if (inp) inp.addEventListener('input', updateLivePreview);
        });

        // Logo Upload Handling with Strict Security (PNG, JPEG, WebP, <= 1MB)
        if (logoInput) {
            logoInput.addEventListener('change', () => {
                const file = logoInput.files?.[0];
                if (!file) return;

                const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid logo format. Only PNG, JPEG, and WebP images are allowed.');
                    logoInput.value = '';
                    return;
                }

                if (file.size > 1024 * 1024) {
                    alert('Logo file too large. Maximum size is 1 MB.');
                    logoInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const result = e.target?.result as string;
                    if (result) {
                        this.cachedLogoBase64 = result;
                        if (logoPreviewImg) logoPreviewImg.src = result;
                        if (logoPreviewFilename) logoPreviewFilename.textContent = file.name;
                        if (logoPreviewContainer) {
                            logoPreviewContainer.classList.remove('hidden');
                            logoPreviewContainer.classList.add('flex');
                        }
                        updateLivePreview();
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        // Remove Logo Action
        if (removeLogoBtn) {
            removeLogoBtn.addEventListener('click', () => {
                this.cachedLogoBase64 = null;
                if (logoInput) logoInput.value = '';
                if (logoPreviewContainer) {
                    logoPreviewContainer.classList.add('hidden');
                    logoPreviewContainer.classList.remove('flex');
                }
                if (logoPreviewImg) logoPreviewImg.src = '';
                updateLivePreview();
            });
        }

        // Clear Saved Profile Action
        if (clearProfileBtn) {
            clearProfileBtn.addEventListener('click', () => {
                if (advisorInput) advisorInput.value = '';
                if (firmInput) firmInput.value = '';
                if (regInput) regInput.value = '';
                if (contactInput) contactInput.value = '';
                if (disclaimerInput) disclaimerInput.value = '';
                if (rememberCheckbox) rememberCheckbox.checked = false;
                if (logoInput) logoInput.value = '';
                this.cachedLogoBase64 = null;
                if (logoPreviewContainer) {
                    logoPreviewContainer.classList.add('hidden');
                    logoPreviewContainer.classList.remove('flex');
                }
                if (logoPreviewImg) logoPreviewImg.src = '';

                try {
                    localStorage.removeItem(PdfExportController.STORAGE_KEY);
                } catch (e) {
                    console.warn('[PdfExportController] LocalStorage removal failed:', e);
                }

                updateLivePreview();
            });
        }

        // Fast Instant Button in Modal
        const fastInstantPdfBtn = this.dom.getElement('fastInstantPdfBtn');
        if (fastInstantPdfBtn && pdfForm) {
            fastInstantPdfBtn.addEventListener('click', () => {
                if (clientInput && !clientInput.value) {
                    clientInput.value = 'Valued Investor';
                }
                pdfForm.requestSubmit();
            });
        }

        // Form Submit
        if (pdfForm) {
            pdfForm.addEventListener('submit', (e) => {
                e.preventDefault();

                // Persist or clear practice profile in localStorage
                if (rememberCheckbox && rememberCheckbox.checked) {
                    try {
                        const profile: AdvisorProfile = {
                            advisorName: advisorInput?.value.trim() || '',
                            advisorFirm: firmInput?.value.trim() || '',
                            advisorRegNo: regInput?.value.trim() || '',
                            advisorContact: contactInput?.value.trim() || '',
                            customDisclaimer: disclaimerInput?.value.trim() || '',
                            logoBase64: this.cachedLogoBase64 || undefined,
                            logoFilename: logoPreviewFilename?.textContent || 'practice-logo.png'
                        };
                        localStorage.setItem(PdfExportController.STORAGE_KEY, JSON.stringify(profile));
                    } catch (err) {
                        console.warn('[PdfExportController] LocalStorage save failed:', err);
                    }
                } else {
                    try {
                        localStorage.removeItem(PdfExportController.STORAGE_KEY);
                    } catch (err) {
                        console.warn('[PdfExportController] LocalStorage remove failed:', err);
                    }
                }

                const generatePdfBtn = this.dom.getElement<HTMLButtonElement>('generatePdfBtn');
                this.executeDirectDownload(generatePdfBtn, new FormData(pdfForm), () => {
                    closeModalFn(pdfModal);
                });
            });
        }
    }

    /**
     * Executes immediate PDF generation and download without modal dialog friction.
     */
    async executeDirectDownload(
        triggerBtn: HTMLElement | null = null,
        customFormData: FormData | null = null,
        onComplete?: () => void
    ): Promise<void> {
        let origBtnHtml = '';
        if (triggerBtn) {
            origBtnHtml = triggerBtn.innerHTML;
            if (triggerBtn instanceof HTMLButtonElement) triggerBtn.disabled = true;
            triggerBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...`;
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

        const formData = customFormData || new FormData();
        const currentInputs = this.getInputs();
        const exportPayload = this.getExportPayload ? this.getExportPayload() : {};

        if (!formData.has('clientName')) formData.append('clientName', 'Valued Investor');
        if (!formData.has('advisorName')) formData.append('advisorName', '');

        // If cached logo exists from LocalStorage and no new file was attached, send base64
        if (this.cachedLogoBase64 && (!formData.has('advisorLogo') || !(formData.get('advisorLogo') as File)?.size)) {
            formData.append('advisorLogoBase64', this.cachedLogoBase64);
        }

        Object.entries(exportPayload).forEach(([key, val]) => {
            if (!formData.has(key) && val !== undefined && val !== null) {
                formData.append(key, String(val));
            }
        });

        if (!formData.has('sip')) formData.append('sip', String(currentInputs.sip));
        if (!formData.has('years')) formData.append('years', String(currentInputs.years));
        if (!formData.has('rate')) formData.append('rate', String(currentInputs.rate));
        if (!formData.has('stepup')) formData.append('stepup', String(currentInputs.stepup));
        if (!formData.has('lumpsum')) formData.append('lumpsum', String(currentInputs.lumpsum));
        if (!formData.has('inflation')) formData.append('inflation', String(currentInputs.inflation));
        if (!formData.has('enable_swp')) formData.append('enable_swp', currentInputs.enable_swp ? '1' : '0');
        if (!formData.has('swp_withdrawal')) formData.append('swp_withdrawal', String(currentInputs.swp_withdrawal));
        if (!formData.has('swp_stepup')) formData.append('swp_stepup', String(currentInputs.swp_stepup));
        if (!formData.has('swp_years')) formData.append('swp_years', String(currentInputs.swp_years));
        if (!formData.has('swp_rate')) formData.append('swp_rate', String(currentInputs.swp_rate));

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

        try {
            const res = await fetch('/generate-pdf', {
                method: 'POST',
                body: formData
            });
            if (!res.ok) throw new Error('PDF generation failed.');

            const blob = await res.blob();
            const clientNameClean = (formData.get('clientName') || 'Plan').toString().trim().replace(/[^a-zA-Z0-9_\-]/g, '_').replace(/_+/g, '_') || 'Plan';
            let filename = `Financial_Report_for_${clientNameClean}.pdf`;
            const disposition = res.headers.get('Content-Disposition');
            if (disposition && disposition.includes('filename=')) {
                const match = disposition.match(/filename="?([^";]+)"?/);
                if (match && match[1]) {
                    filename = match[1].trim();
                }
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();

            // Telemetry logging
            const advisorNameStr = (formData.get('advisorName') || '').toString().trim();
            this.analytics.sendImmediateInsight(currentInputs, this.getLatestResults(), this.getActiveGoalMode(), {
                pdf_downloaded: true,
                pdf_has_custom_name: advisorNameStr.length > 0,
                exit_action: 'pdf_download',
                interaction_count: this.getInteractionCount()
            });

            setTimeout(() => window.URL.revokeObjectURL(url), 60000);
            if (onComplete) onComplete();
        } catch (err) {
            console.error('PDF generation failed:', err);
        } finally {
            if (triggerBtn) {
                if (triggerBtn instanceof HTMLButtonElement) triggerBtn.disabled = false;
                triggerBtn.innerHTML = origBtnHtml;
            }
        }
    }
}
