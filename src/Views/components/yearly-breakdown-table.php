<?php
/**
 * yearly-breakdown-table.php
 * Component for the tabular breakdown showing SIP/SWP milestones.
 */
declare(strict_types=1);
?>
<div class="mt-8 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h2 id="yearly-breakdown" class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Yearly Breakdown
        </h2>
        <div class="flex gap-2">
            <button type="submit" name="action" value="download_csv" form="calculator-form"
                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                CSV
            </button>
            <button type="button" id="openPdfModalBtn" aria-haspopup="dialog" aria-expanded="false"
                aria-controls="pdfModal"
                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50 hover:border-emerald-300 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                PDF
            </button>
            <button type="button" id="shareCalcBtn" aria-expanded="false"
                class="text-sm px-4 py-3 sm:py-2 flex items-center gap-2 rounded-lg font-semibold bg-white text-emerald-600 border border-indigo-200 hover:bg-emerald-50 hover:border-indigo-300 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span id="shareBtnText">Share</span>
            </button>
        </div>
    </div>

    <div class="glass-card overflow-hidden border border-slate-200 shadow-sm">
        <div id="table-scroll-wrapper" class="max-h-[600px] overflow-y-auto overflow-x-auto custom-scrollbar">
            <table id="results-table" class="w-full text-sm text-left relative">
                <thead id="breakdown-head"
                    class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-4 bg-slate-50/95 border-b border-slate-200">Year</th>
                        <th scope="col" class="px-6 py-4 bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">Start Corpus</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-700 whitespace-nowrap">Monthly SIP</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-700 whitespace-nowrap">Annual SIP</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 whitespace-nowrap">Total Invested</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-rose-500 whitespace-nowrap swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>>Monthly SWP</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-rose-500 whitespace-nowrap swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>>Annual SWP</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-slate-500 whitespace-nowrap swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>>Total Withdrawn</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 text-emerald-700 whitespace-nowrap">Interest</th>
                        <th scope="col" class="px-6 py-4 text-right bg-slate-50/95 border-b border-slate-200 font-bold text-slate-800 whitespace-nowrap">End Corpus</th>
                    </tr>
                </thead>
                <tbody id="breakdown-body" class="divide-y divide-slate-100 text-slate-600">
                    <?php foreach ($combined as $row): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-700"><?= $row['year']?></td>
                        <td class="px-6 py-4 text-right font-mono"><?= formatInr($row['begin_balance'])?></td>
                        <td class="px-6 py-4 text-right text-emerald-700 font-medium font-mono"><?= $row['sip_monthly'] !== null ? formatInr($row['sip_monthly']) : '-'?></td>
                        <td class="px-6 py-4 text-right text-emerald-700 font-medium font-mono"><?= formatInr($row['annual_contribution'])?></td>
                        <td class="px-6 py-4 text-right text-slate-500 font-mono"><?= formatInr($row['cumulative_invested'])?></td>
                        <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>><?= $row['swp_monthly'] !== null ? formatInr($row['swp_monthly']) : '-'?></td>
                        <td class="px-6 py-4 text-right text-rose-500 font-medium font-mono swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>><?= $row['annual_withdrawal'] !== null ? formatInr($row['annual_withdrawal']) : '-'?></td>
                        <td class="px-6 py-4 text-right text-slate-500 font-mono swp-col" <?=!$enable_swp ? 'style="display:none"' : ''?>><?= $row['cumulative_withdrawals'] ? formatInr($row['cumulative_withdrawals']) : '-'?></td>
                        <td class="px-6 py-4 text-right text-emerald-600 font-medium font-mono"><?= formatInr($row['interest'])?></td>
                        <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono"><?= formatInr($row['combined_total'])?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
