# SWP Tax Calculator India: Calculate Post-Tax Withdrawal Income
Understand how your SWP withdrawals are taxed and maximize post-tax income

---

## How SWP Withdrawals Are Taxed (FIFO Method)
When you set up a Systematic Withdrawal Plan (SWP) from a mutual fund, each withdrawal is treated as a <strong>partial redemption</strong>. The Income Tax Department uses the <strong>First-In-First-Out (FIFO)</strong> method to determine which units are being sold.

Crucially, <strong>only the capital gains portion</strong> of each withdrawal is taxable — the principal (cost of acquisition) is returned tax-free. This makes SWP fundamentally different from FD interest, where the entire interest amount is taxable.

<div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 not-prose mt-4">
    <p class="font-bold text-indigo-800 mb-2">Example: <span class="dynamic-amount" data-amount-inr="50000" data-amount-usd="500" data-amount-eur="500" data-amount-gbp="500"></span> monthly SWP withdrawal</p>
    <p class="text-sm text-gray-600">If you invested <span class="dynamic-amount" data-amount-inr="1000000" data-amount-usd="100000" data-amount-eur="100000" data-amount-gbp="100000"></span> and your corpus is now <span class="dynamic-amount" data-amount-inr="1500000" data-amount-usd="150000" data-amount-eur="150000" data-amount-gbp="150000"></span>:</p>
    <ul class="text-sm text-gray-600 mt-2 space-y-1">
        <li>• Capital gains ratio = (Current - Invested) / Current = <strong>33.3%</strong></li>
        <li>• Taxable portion per withdrawal = <span class="dynamic-amount" data-amount-inr="50000" data-amount-usd="500" data-amount-eur="500" data-amount-gbp="500"></span> × 33.3% = <strong><span class="dynamic-amount" data-amount-inr="16650" data-amount-usd="166.50" data-amount-eur="166.50" data-amount-gbp="166.50"></span></strong></li>
        <li>• Tax-free portion (principal) = <span class="dynamic-amount" data-amount-inr="50000" data-amount-usd="500" data-amount-eur="500" data-amount-gbp="500"></span> × 66.7% = <strong><span class="dynamic-amount" data-amount-inr="33350" data-amount-usd="333.50" data-amount-eur="333.50" data-amount-gbp="333.50"></span></strong></li>
        <li>• If LTCG applies: Tax = <span class="dynamic-amount" data-amount-inr="16650" data-amount-usd="166.50" data-amount-eur="166.50" data-amount-gbp="166.50"></span> × 12.5% = <strong><span class="dynamic-amount" data-amount-inr="2081" data-amount-usd="24.98" data-amount-eur="24.98" data-amount-gbp="24.98"></span>/month</strong></li>
        <li>• <strong>Effective tax rate on withdrawal = only 4.2% – 5.0%!</strong></li>
    </ul>
</div>

## SWP from Equity vs Debt Funds
<div class="overflow-x-auto not-prose">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-gray-700 text-left">
                <th class="py-3 px-4 font-bold border-b">Parameter</th>
                <th class="py-3 px-4 font-bold border-b text-indigo-600">Equity Fund SWP</th>
                <th class="py-3 px-4 font-bold border-b">Debt Fund SWP</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b">
                <td class="py-3 px-4 font-medium">LTCG qualification</td>
                <td class="py-3 px-4">After 1 year holding</td>
                <td class="py-3 px-4">N/A (post Apr 2023)</td>
            </tr>
            <tr class="border-b">
                <td class="py-3 px-4 font-medium">Tax rate on gains</td>
                <td class="py-3 px-4 text-green-700">12.5% LTCG / 20% STCG</td>
                <td class="py-3 px-4 text-rose-500">Income slab rate (always)</td>
            </tr>
            <tr class="border-b">
                <td class="py-3 px-4 font-medium">Annual exemption</td>
                <td class="py-3 px-4 text-green-700"><span class="currency-text">$</span>1,500 LTCG</td>
                <td class="py-3 px-4">None</td>
            </tr>
            <tr class="border-b">
                <td class="py-3 px-4 font-medium">TDS</td>
                <td class="py-3 px-4 text-green-700">No TDS</td>
                <td class="py-3 px-4 text-green-700">No TDS</td>
            </tr>
            <tr>
                <td class="py-3 px-4 font-medium">Best for</td>
                <td class="py-3 px-4 text-indigo-600 font-medium">Long-term retirement income</td>
                <td class="py-3 px-4">Short-term regular income</td>
            </tr>
        </tbody>
    </table>
</div>

## Worked Example: Monthly <span class="dynamic-amount" data-amount-inr="50000" data-amount-usd="500" data-amount-eur="500" data-amount-gbp="500"></span> SWP Tax Calculation
Assume: <span class="dynamic-amount" data-amount-inr="1000000" data-amount-usd="100000" data-amount-eur="100000" data-amount-gbp="100000"></span> corpus in equity hybrid fund, held >1 year, 12% annual returns, <span class="dynamic-amount" data-amount-inr="50000" data-amount-usd="500" data-amount-eur="500" data-amount-gbp="500"></span>/month SWP:

<div class="overflow-x-auto not-prose">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-gray-700 text-left">
                <th class="py-3 px-4 font-bold border-b">Year</th>
                <th class="py-3 px-4 font-bold border-b">Withdrawn</th>
                <th class="py-3 px-4 font-bold border-b">Capital Gains Portion</th>
                <th class="py-3 px-4 font-bold border-b">Tax (12.5% / 15%)*</th>
                <th class="py-3 px-4 font-bold border-b text-emerald-600">Post-Tax Income</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b">
                <td class="py-3 px-4">Year 1</td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="600000" data-amount-usd="6000" data-amount-eur="6000" data-amount-gbp="6000"></span></td>
                <td class="py-3 px-4">~<span class="dynamic-amount" data-amount-inr="180000" data-amount-usd="1800" data-amount-eur="1800" data-amount-gbp="1800"></span></td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="6875" data-amount-usd="270" data-amount-eur="270" data-amount-gbp="270"></span></td>
                <td class="py-3 px-4 text-emerald-600 font-bold"><span class="dynamic-amount" data-amount-inr="593125" data-amount-usd="5730" data-amount-eur="5730" data-amount-gbp="5730"></span></td>
            </tr>
            <tr class="border-b">
                <td class="py-3 px-4">Year 5</td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="600000" data-amount-usd="6000" data-amount-eur="6000" data-amount-gbp="6000"></span></td>
                <td class="py-3 px-4">~<span class="dynamic-amount" data-amount-inr="240000" data-amount-usd="2400" data-amount-eur="2400" data-amount-gbp="2400"></span></td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="14375" data-amount-usd="143" data-amount-eur="143" data-amount-gbp="143"></span></td>
                <td class="py-3 px-4 text-emerald-600 font-bold"><span class="dynamic-amount" data-amount-inr="585625" data-amount-usd="5856" data-amount-eur="5856" data-amount-gbp="5856"></span></td>
            </tr>
            <tr>
                <td class="py-3 px-4">Year 10</td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="600000" data-amount-usd="6000" data-amount-eur="6000" data-amount-gbp="6000"></span></td>
                <td class="py-3 px-4">~<span class="dynamic-amount" data-amount-inr="320000" data-amount-usd="3200" data-amount-eur="3200" data-amount-gbp="3200"></span></td>
                <td class="py-3 px-4"><span class="dynamic-amount" data-amount-inr="24375" data-amount-usd="243" data-amount-eur="243" data-amount-gbp="243"></span></td>
                <td class="py-3 px-4 text-emerald-600 font-bold"><span class="dynamic-amount" data-amount-inr="575625" data-amount-usd="5756" data-amount-eur="5756" data-amount-gbp="5756"></span></td>
            </tr>
        </tbody>
    </table>
</div>
<p class="text-xs text-gray-500">* After <span class="currency-text">$</span>1.25L annual LTCG exemption. Effective tax rate ranges from ~1.1% to ~4.1% on total withdrawal — far lower than FD taxation.</p>

## Strategies to Minimize SWP Tax
1. **Wait for LTCG:** Ensure all SIP units have crossed the 1-year mark before starting SWP. LTCG (12.5%) is much lower than STCG (20%).
2. **Harvest the <span class="currency-text">$</span>1.25L exemption:** If your annual SWP gains are under <span class="currency-text">$</span>1,500, you pay <strong>zero tax</strong> on equity fund withdrawals.
3. **Split between spouses:** If your spouse also has investments, split SWP across two accounts to double the <span class="currency-text">$</span>1.25L exemption.
4. **Use hybrid funds:** Equity-oriented hybrid funds (>65% equity) get the same tax treatment as pure equity funds but with lower volatility.
5. **Strategic rebalancing:** Periodically book gains within the exempt limit to "reset" your cost basis higher, reducing future tax liability.

## Frequently Asked Questions
<details class="group">
    <summary class="cursor-pointer font-bold text-slate-800 py-2">Is SWP from mutual funds taxable?</summary>
    <div class="pb-4 text-gray-600">Yes, but only the <strong>capital gains portion</strong> is taxable, not the full withdrawal. The principal component is returned tax-free. This makes the effective tax rate on SWP much lower than on FD interest.</div>
</details>

<details class="group">
    <summary class="cursor-pointer font-bold text-slate-800 py-2">How is the capital gains portion calculated?</summary>
    <div class="pb-4 text-gray-600">Using the FIFO method: the earliest units are redeemed first. For each unit, capital gain = Redemption NAV - Purchase NAV. Your AMC provides this in the Capital Gains Statement (available on their website or via CAMS/KFintech).</div>
</details>

<details class="group">
    <summary class="cursor-pointer font-bold text-slate-800 py-2">Can I claim tax-loss harvesting with SWP?</summary>
    <div class="pb-4 text-gray-600">Yes. If some of your mutual fund units are at a loss, you can redeem them to book short-term capital losses, which can be set off against capital gains from SWP withdrawals. This can significantly reduce your overall tax liability.</div>
</details>

<details class="group">
    <summary class="cursor-pointer font-bold text-slate-800 py-2">Is there GST on mutual fund transactions?</summary>
    <div class="pb-4 text-gray-600">There's no GST on the investment or redemption amount. However, the fund's expense ratio includes GST on management fees (typically 0.5-2% annually). This is deducted from the fund's NAV, not billed separately.</div>
</details>

<div class="mt-12 bg-indigo-50/50 p-6 rounded-xl border border-indigo-100 not-prose">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Related Guides</h3>
    <ul class="space-y-2 text-sm">
        <li><a href="/#calculator-section" class="text-indigo-600 hover:underline font-medium">Advanced SIP & SWP Calculator</a> — Model SWP withdrawals with tax projections</li>
        <li><a href="/swp-retirement-planning" class="text-indigo-600 hover:underline font-medium">SWP Retirement Planning</a> — Complete guide to retirement income via SWP</li>
        <li><a href="/mutual-fund-tax-2026" class="text-indigo-600 hover:underline font-medium">Mutual Fund Tax 2026</a> — LTCG, STCG & tax-efficient strategies</li>
        <li><a href="/swp-vs-fixed-deposit" class="text-indigo-600 hover:underline font-medium">SWP vs Fixed Deposit</a> — Tax efficiency comparison for retirement</li>
        <li><a href="/sip-vs-fd-vs-ppf" class="text-indigo-600 hover:underline font-medium">SIP vs FD vs PPF</a> — Compare tax treatment across investments</li>
    </ul>
</div>

<div class="mt-12 p-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl text-white shadow-xl text-center not-prose">
    <h2 class="text-2xl font-bold mb-4 text-white">Calculate Your SWP Tax Liability</h2>
    <p class="mb-8 text-indigo-100">Use our free calculator to simulate SWP withdrawals and estimate your capital gains tax at different holding periods.</p>
    <a href="/#calculator-section" class="inline-flex items-center px-8 py-3 bg-white text-indigo-600 font-bold rounded-lg shadow-lg hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200">
        Launch SWP Calculator
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
        </svg>
    </a>
</div>
