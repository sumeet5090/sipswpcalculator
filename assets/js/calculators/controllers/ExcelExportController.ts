import { DOMAdapter } from '../../adapters/DOMAdapter.ts';
import { AnalyticsService } from '../AnalyticsLogger.ts';
import { MathEngine } from '../MathEngine.ts';
import type { InvestmentInputs, YearResult } from '../../types';
import { CalculatorRegistry } from '../constants/CalculatorRegistry.ts';
import { ModeTableSchema } from '../constants/ModeTableSchema.ts';

/**
 * ExcelExportController.ts
 * Single Responsibility controller managing client-side native .xlsx workbook generation.
 * Dynamically imports SheetJS ('xlsx') on user click for zero initial bundle overhead.
 */
export class ExcelExportController {
    private dom: DOMAdapter;
    private analytics: AnalyticsService;
    private getInputs: () => InvestmentInputs;
    private getLatestResults: () => YearResult[];
    private getActiveGoalMode: () => string;
    private getExportPayload?: () => Record<string, string | number | boolean>;

    constructor(
        dom: DOMAdapter,
        analytics: AnalyticsService,
        getInputs: () => InvestmentInputs,
        getLatestResults: () => YearResult[],
        getActiveGoalMode: () => string,
        getExportPayload?: () => Record<string, string | number | boolean>
    ) {
        this.dom = dom;
        this.analytics = analytics;
        this.getInputs = getInputs;
        this.getLatestResults = getLatestResults;
        this.getActiveGoalMode = getActiveGoalMode;
        this.getExportPayload = getExportPayload;
    }

    init(): void {
        const excelBtn = this.dom.getElement<HTMLButtonElement>('downloadExcelBtn');
        if (!excelBtn) return;

        excelBtn.addEventListener('click', async (e: Event) => {
            e.preventDefault();

            const origContent = excelBtn.innerHTML;
            excelBtn.disabled = true;
            excelBtn.classList.add('opacity-75', 'cursor-wait');

            try {
                const XLSX = await import('xlsx');
                const inputs = this.getInputs();
                const payload = this.getExportPayload ? this.getExportPayload() : { ...inputs };
                const rawResults = this.getLatestResults();
                const results = rawResults.length > 0 ? rawResults : MathEngine.calculate(inputs);

                const sym = typeof payload.currency_symbol === 'string' && payload.currency_symbol.length > 0
                    ? payload.currency_symbol
                    : '₹';
                const currFmt = `${sym}#,##,##0`;
                const calcType = String(payload.calculator_type ?? 'combo');
                const schema = ModeTableSchema.get(calcType);
                const sheetName = schema.excel.sheetName.slice(0, 31);

                const scheduleRows = this.buildScheduleRows(results);
                const lastDataRow = scheduleRows.length + 1;

                // Sheet 2: Mode-Specific Yearly Schedule
                const scheduleHeaders = [...schema.excel.headers];

                const scheduleAoA: (string | number | { f: string; t: string; z: string })[][] = [
                    scheduleHeaders,
                    ...scheduleRows
                ];

                if (scheduleRows.length > 0) {
                    const hasWithdrawnData = schema.excel.summary.includeWithdrawnRow
                        || results.some((r) => (r.annual_withdrawal ?? 0) > 0 || (r.cumulative_withdrawals ?? 0) > 0);
                    scheduleAoA.push([
                        'TOTAL',
                        '',
                        '',
                        schema.excel.summary.useLastRowInsteadOfSum ? '' : { f: `SUM(D2:D${lastDataRow})`, t: 'n', z: currFmt },
                        '',
                        '',
                        hasWithdrawnData ? { f: `SUM(G2:G${lastDataRow})`, t: 'n', z: currFmt } : '',
                        schema.excel.summary.useLastRowInsteadOfSum ? { f: `H${lastDataRow}`, t: 'n', z: currFmt } : { f: `SUM(H2:H${lastDataRow})`, t: 'n', z: currFmt },
                        { f: `I${lastDataRow}`, t: 'n', z: currFmt },
                        { f: `J${lastDataRow}`, t: 'n', z: currFmt }
                    ]);
                }

                const wsSchedule = XLSX.utils.aoa_to_sheet(scheduleAoA);
                this.applyCurrencyFormat(wsSchedule, 1, lastDataRow, [1, 2, 3, 4, 5, 6, 7, 8, 9], currFmt);
                wsSchedule['!cols'] = [
                    { wch: 8 },
                    { wch: 18 },
                    { wch: 16 },
                    { wch: 20 },
                    { wch: 20 },
                    { wch: 16 },
                    { wch: 18 },
                    { wch: 22 },
                    { wch: 20 },
                    { wch: 24 }
                ];

                // Sheet 1: Summary & Inputs
                const summaryAoA = this.buildSummarySheetAoA(
                    calcType,
                    inputs,
                    payload,
                    results,
                    lastDataRow,
                    currFmt,
                    sheetName
                );
                const wsSummary = XLSX.utils.aoa_to_sheet(summaryAoA);
                wsSummary['!cols'] = [
                    { wch: 36 },
                    { wch: 24 },
                    { wch: 38 }
                ];

                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, wsSummary, 'Summary & Inputs');
                XLSX.utils.book_append_sheet(wb, wsSchedule, sheetName);

                const safeSlug = calcType.replace(/[^a-z0-9_-]/gi, '_').toUpperCase();
                const filename = `SIPSWP_${safeSlug}_Financial_Model.xlsx`;
                XLSX.writeFile(wb, filename);

                this.analytics.sendImmediateInsight(inputs, results, this.getActiveGoalMode(), {
                    exit_action: 'excel_download'
                });
            } catch (err) {
                console.error('Excel (.xlsx) export failed:', err);
            } finally {
                excelBtn.disabled = false;
                excelBtn.classList.remove('opacity-75', 'cursor-wait');
                excelBtn.innerHTML = origContent;
            }
        });
    }

    private buildScheduleRows(results: YearResult[]): number[][] {
        return results.map((row) => {
            const legacyRow = row as YearResult & { swp_annual_payout?: number };
            const annualWithdrawal = row.annual_withdrawal ?? legacyRow.swp_annual_payout ?? 0;
            const postTax = row.post_tax_total ?? row.combined_total;

            return [
                row.year,
                Math.round(row.begin_balance ?? 0),
                Math.round(row.sip_monthly ?? 0),
                Math.round(row.annual_contribution ?? 0),
                Math.round(row.cumulative_invested ?? 0),
                Math.round(row.swp_monthly ?? 0),
                Math.round(annualWithdrawal),
                Math.round(row.interest ?? 0),
                Math.round(row.combined_total ?? 0),
                Math.round(postTax ?? 0)
            ];
        });
    }

    private applyCurrencyFormat(
        ws: Record<string, unknown>,
        startRowZeroBased: number,
        endRowZeroBased: number,
        colIndices: number[],
        currFmt: string
    ): void {
        const colLetters = 'ABCDEFGHIJ';
        for (let r = startRowZeroBased; r <= endRowZeroBased; r++) {
            for (const c of colIndices) {
                const addr = `${colLetters[c]}${r + 1}`;
                const cell = ws[addr] as { t?: string; z?: string } | undefined;
                if (cell && cell.t === 'n') {
                    cell.z = currFmt;
                }
            }
        }
    }

    private buildSummarySheetAoA(
        calcType: string,
        inputs: InvestmentInputs,
        payload: Record<string, string | number | boolean>,
        results: YearResult[],
        lastDataRow: number,
        currFmt: string,
        sheetName: string
    ): (string | number | { f: string; t: string; z: string })[][] {
        const isSpecialized = CalculatorRegistry.isSpecializedMode(calcType);
        const schema = ModeTableSchema.get(calcType);
        const summarySchema = schema.excel.summary;
        const generatedDate = new Date().toISOString().slice(0, 10);

        const rows: (string | number | { f: string; t: string; z: string })[][] = [
            [`${CalculatorRegistry.getShareTitle(calcType)} — Financial Projection Model`],
            ['Generated On', generatedDate, 'https://sipswpcalculator.com'],
            ['Calculator Mode', calcType.toUpperCase(), ''],
            [],
            ['ACTIVE CALCULATOR PARAMETERS', 'Value', 'Notes']
        ];

        let inflationRowNum = 0;

        if (isSpecialized) {
            const skipKeys = new Set(['calculator_type', 'currency', 'currency_symbol', 'enable_swp', 'sip', 'stepup', 'lumpsum']);
            Object.entries(payload).forEach(([key, val]) => {
                if (skipKeys.has(key) || val === '' || val === undefined || val === null) return;
                const label = key
                    .replace(/^(post_ret_|nps_|gratuity_|fd_|ppf_|emi_|cagr_|ci_|inflation_|epf_|tax_|it_)/, '')
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, (ch) => ch.toUpperCase());
                const numVal = Number(val);
                rows.push([label, Number.isFinite(numVal) ? numVal : String(val), '']);
                if (key.includes('inflation')) {
                    inflationRowNum = rows.length;
                }
            });
        } else {
            rows.push(['Monthly SIP', inputs.sip, 'Monthly investment contribution']);
            rows.push(['Investment Tenure (Years)', inputs.years, 'Accumulation horizon']);
            rows.push(['Expected Annual Return (%)', inputs.rate, 'CAGR assumption']);
            rows.push(['Annual Step-Up (%)', inputs.stepup, 'Yearly SIP increase']);
            rows.push(['Initial Lumpsum', inputs.lumpsum, 'Day-1 seed capital']);
            rows.push(['Expected Inflation (%)', inputs.inflation, 'Annual purchasing power erosion']);
            inflationRowNum = rows.length;

            if (inputs.enable_swp) {
                rows.push(['SWP Monthly Withdrawal', inputs.swp_withdrawal, 'Initial monthly payout']);
                rows.push(['SWP Tenure (Years)', inputs.swp_years, 'Withdrawal horizon']);
                rows.push(['SWP Annual Return (%)', inputs.swp_rate, 'Retirement portfolio CAGR']);
                rows.push(['SWP Annual Step-Up (%)', inputs.swp_stepup, 'Yearly withdrawal escalation']);
                if (inputs.corpus !== undefined && inputs.corpus > 0) {
                    rows.push(['Starting SWP Corpus', inputs.corpus, 'Standalone SWP principal']);
                }
            }
        }

        rows.push([]);
        rows.push(['HEADLINE KPI SUMMARY', 'Amount / Formula', 'Description']);

        if (results.length > 0) {
            const ref = `'${sheetName}'`;
            const hasWithdrawnData = summarySchema.includeWithdrawnRow
                || results.some((r) => (r.annual_withdrawal ?? 0) > 0 || (r.cumulative_withdrawals ?? 0) > 0);

            rows.push([
                summarySchema.investedLabel,
                summarySchema.useLastRowInsteadOfSum
                    ? { f: `${ref}!E${lastDataRow}`, t: 'n', z: currFmt }
                    : { f: `MAX(${ref}!E2:E${lastDataRow})`, t: 'n', z: currFmt },
                summarySchema.investedNote
            ]);
            rows.push([
                summarySchema.gainsLabel,
                summarySchema.useLastRowInsteadOfSum
                    ? { f: `${ref}!H${lastDataRow}`, t: 'n', z: currFmt }
                    : { f: `SUM(${ref}!H2:H${lastDataRow})`, t: 'n', z: currFmt },
                summarySchema.gainsNote
            ]);

            if (hasWithdrawnData && summarySchema.withdrawnLabel) {
                rows.push([
                    summarySchema.withdrawnLabel,
                    { f: `SUM(${ref}!G2:G${lastDataRow})`, t: 'n', z: currFmt },
                    summarySchema.withdrawnNote
                ]);
            }

            rows.push([
                summarySchema.finalLabel,
                { f: `${ref}!I${lastDataRow}`, t: 'n', z: currFmt },
                summarySchema.finalNote
            ]);
            const corpusRowNum = rows.length;
            const totalYears = results.length;

            if (inflationRowNum > 0 && summarySchema.allowInflationDiscount) {
                rows.push([
                    'Inflation-Adjusted Real Value',
                    { f: `B${corpusRowNum}/POWER(1+B${inflationRowNum}/100,${totalYears})`, t: 'n', z: currFmt },
                    `Today's purchasing power (${totalYears} yrs)`
                ]);
            }
        }

        return rows;
    }
}
