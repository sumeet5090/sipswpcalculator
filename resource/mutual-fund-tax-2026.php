<?php
/**
 * Mutual Fund Tax Rules 2026: LTCG, STCG & Tax-Efficient Withdrawals
 */
$title = "Mutual Fund Tax Rules 2026: LTCG, STCG & Tax-Efficient Withdrawals";
$meta_desc = "Complete 2026 guide to mutual fund taxation. LTCG at 12.5%, STCG at 20% for equity. Learn SWP tax efficiency, tax harvesting, and global comparisons.";
$cta = "Don't let taxes eat your compounding. Use our calculator to model post-tax returns and simulate tax-harvesting strategies.";

ob_start();
?>

<h2>2026 Capital Gains Tax Summary (India)</h2>
<p>Following the 2024-25 amendments, mutual fund taxation has been simplified. Here's what applies for FY 2026-27:</p>

<div class="overflow-x-auto border border-slate-200 rounded-xl mb-8 not-prose shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-4 text-left font-extrabold text-slate-800 uppercase tracking-wider">Fund Type</th>
                <th class="px-6 py-4 text-left font-extrabold text-slate-800 uppercase tracking-wider">Holding</th>
                <th class="px-6 py-4 text-right font-extrabold text-indigo-700 uppercase tracking-wider">Tax Rate</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
            <tr>
                <td class="px-6 py-4 font-bold text-slate-800">Equity MFs</td>
                <td class="px-6 py-4 text-slate-500">> 1 Year</td>
                <td class="px-6 py-4 text-right font-bold text-emerald-600">12.5% (LTCG)</td>
            </tr>
            <tr class="bg-slate-50/30">
                <td class="px-6 py-4 font-bold text-slate-800">Equity MFs</td>
                <td class="px-6 py-4 text-slate-500">≤ 1 Year</td>
                <td class="px-6 py-4 text-right font-bold text-rose-600">20% (STCG)</td>
            </tr>
            <tr>
                <td class="px-6 py-4 font-bold text-slate-800">Debt Funds</td>
                <td class="px-6 py-4 text-slate-500">Any</td>
                <td class="px-6 py-4 text-right font-bold text-slate-900">Slab Rate</td>
            </tr>
        </tbody>
    </table>
</div>

<h2 id="holding-period">LTCG vs STCG: The Holding Period Bridge</h2>
<p><strong>Long-Term Capital Gains (LTCG)</strong> on equity funds are taxed at 12.5% on gains exceeding $1,500 annually. <strong>Short-Term Capital Gains (STCG)</strong> apply if held for less than 12 months, taxed at 20%.</p>

<h2 id="fifo-method">The FIFO Method for SIPs</h2>
<p>Each SIP installment is treated as a separate purchase. When you redeem, units are sold on a <strong>First-In-First-Out</strong> basis. This means your oldest (and likely most tax-efficient) units are liquidated first, which is highly beneficial for long-term SWP strategies.</p>

<h2 id="tax-efficiency">Why SWP Wins the Tax War</h2>
<p>Compared to Fixed Deposits (FDs), SWPs are significantly more efficient:</p>
<ul class="list-disc pl-6 space-y-2">
    <li><strong>FDs:</strong> Taxed on the <em>entire interest</em> at your marginal slab rate (up to 30%+).</li>
    <li><strong>SWPs:</strong> Taxed only on the <em>capital gain portion</em> of the withdrawal, often at the 12.5% LTCG rate.</li>
</ul>

<h2 id="harvesting">Strategy: The $1,250 LTCG Harvest</h2>
<p>Every year, you get a tax-free exemption on equity LTCG. By redeeming and immediately reinvesting up to $1,250 of gains annually, you effectively reset your cost basis and eliminate future tax liabilities for free.</p>

<div class="bg-slate-900 text-white p-10 rounded-3xl text-center my-14 not-prose border border-slate-800 shadow-2xl">
    <h3 class="text-2xl font-bold mb-4">Model Your Post-Tax Wealth</h3>
    <p class="mb-8 text-slate-400">Our calculator automatically accounts for these tax brackets to give you a realistic "Spendable Income" projection.</p>
    <a href="/#calculator-section" class="inline-flex items-center gap-3 px-10 py-4 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-500 transition-all">
        Open Tax-Aware Calculator
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
    </a>
</div>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../includes/layout.php';
?>