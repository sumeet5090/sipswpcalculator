---
title: "What is CAGR? Formula, Calculation & Meaning Explained"
subtitle: "The complete guide to Compound Annual Growth Rate — mathematical formula, CAGR vs XIRR vs Absolute Returns, and how to evaluate Indian investments"
meta_desc: "What is CAGR (Compound Annual Growth Rate)? Understand the CAGR formula, compare CAGR vs XIRR vs absolute return, and calculate investment growth in India."
tag: "Guide"
tag_color: "blue"
featured: true
date: "March 2026"
---

---

<div id="summary" class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8 not-prose">
    <h2 class="text-lg font-bold text-blue-900 mb-2">📋 Executive Summary: What is CAGR?</h2>
    <p class="text-slate-700 text-sm leading-relaxed mb-3">
        <strong>CAGR (Compound Annual Growth Rate)</strong> is the mean annual growth rate of an investment over a specified period of time longer than one year, assuming the investment compounded annually. Unlike simple arithmetic average return—which gives misleading results during volatile market swings—CAGR smooths out year-to-year fluctuations to provide the true geometric rate of capital growth from inception to maturity.
    </p>
    <div class="mt-3 pt-3 border-t border-blue-200/60 flex flex-wrap items-center justify-between gap-2">
        <span class="text-xs font-semibold text-blue-800">Use for: Lumpsum investments, stocks, real estate • For SIPs: Use XIRR instead</span>
        <a href="/cagr-calculator" class="text-xs font-bold text-blue-700 hover:text-blue-900 underline">Try CAGR Calculator →</a>
    </div>
</div>

## 1. Why Simple Averages Lie: The Geometric Truth of CAGR

To understand why professional investors use CAGR rather than arithmetic averages, examine a classic financial paradox:

Suppose you invest **₹1,00,000** in an equity fund:
* **Year 1:** The market experiences a massive bull rally and the fund gains **+100%**. Your portfolio value doubles to **₹2,00,000**.
* **Year 2:** A global bear market strikes and the fund crashes by **-50%**. Your portfolio drops from ₹2,00,000 back down to **₹1,00,000**.

Now calculate your return:
* **Simple Arithmetic Average:** $\frac{(+100\%) + (-50\%)}{2} = \mathbf{+25\%\text{ per year}}$!
* **The Reality:** You started with ₹1,00,000 and ended with ₹1,00,000 after 2 years. Your net gain is exactly **₹0**!

A financial distributor boasting an "average return of 25%" is mathematically deceiving you. The true compound annual return is **0.0%**. CAGR provides the exact geometric return that accounts for volatility drag.

---

## 2. The CAGR Mathematical Formula

The mathematical formula to compute the Compound Annual Growth Rate of any one-time investment is:

$$\text{CAGR} = \left(\frac{\text{Ending Value}}{\text{Beginning Value}}\right)^{\frac{1}{t}} - 1$$

Where:
* **Ending Value ($EV$):** The final valuation of the asset at the end of the tenure.
* **Beginning Value ($BV$):** The initial purchase or investment cost at the start of the tenure.
* **t:** The total duration of the investment expressed in years (can include fractional years, e.g., 3.5 years).

To express CAGR as a percentage:
$$\text{CAGR (\%)} = \left[ \left(\frac{EV}{BV}\right)^{\frac{1}{t}} - 1 \right] \times 100$$

### Step-by-Step Worked Example:
Suppose an investor bought shares of a mutual fund scheme for **₹5,00,000** in March 2021. In March 2026 (exactly 5 years later), the portfolio is valued at **₹9,80,000**.

1. Ratio: $\frac{EV}{BV} = \frac{9,80,000}{5,00,000} = 1.96$
2. Exponent: $\frac{1}{t} = \frac{1}{5} = 0.20$
3. Compute: $1.96^{0.20} \approx 1.1440$
4. Subtract 1: $1.1440 - 1 = 0.1440$
5. Percentage: $0.1440 \times 100 = \mathbf{14.40\%\text{ CAGR}}$

The investment compounded at a rate of 14.40% annualized over the 5-year period.

---

## 3. CAGR vs. Absolute Return vs. XIRR: When to Use Which?

Indian retail investors frequently confuse three common performance metrics:

<div class="overflow-hidden border border-slate-200 rounded-2xl mb-8 shadow-sm">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50">
<tr>
<th scope="col" class="px-6 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Metric</th>
<th scope="col" class="px-6 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Formula / Nature</th>
<th scope="col" class="px-6 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Best Use Case</th>
<th scope="col" class="px-6 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Limitation</th>
</tr>
</thead>
<tbody class="bg-white divide-y divide-slate-200 text-slate-700">
<tr>
<td class="px-6 py-4 font-bold text-blue-700">Absolute Return</td>
<td class="px-6 py-4">$\frac{EV - BV}{BV} \times 100$</td>
<td class="px-6 py-4">Short-term horizons (< 1 year); single point-in-time snapshot</td>
<td class="px-6 py-4 text-rose-600">Completely ignores time duration. A 100% gain over 1 year is extraordinary; a 100% gain over 20 years is terrible (~3.5% CAGR).</td>
</tr>
<tr>
<td class="px-6 py-4 font-bold text-blue-700">CAGR</td>
<td class="px-6 py-4">$\left(\frac{EV}{BV}\right)^{\frac{1}{t}} - 1$</td>
<td class="px-6 py-4">Lump-sum mutual funds, real estate, stocks held for > 1 year</td>
<td class="px-6 py-4 text-rose-600">Cannot handle periodic recurring cash flows (e.g., monthly SIPs or SWPs).</td>
</tr>
<tr>
<td class="px-6 py-4 font-bold text-blue-700">XIRR (Extended Internal Rate of Return)</td>
<td class="px-6 py-4">Solves: $\sum \frac{C_j}{(1 + \text{XIRR})^{(d_j - d_0)/365}} = 0$</td>
<td class="px-6 py-4">Systematic Investment Plans (SIP), multi-date stock purchases, SWPs</td>
<td class="px-6 py-4 text-slate-600">Requires specialized numerical root-finding algorithms (Newton-Raphson method).</td>
</tr>
</tbody>
</table>
</div>

> **Rule of Thumb:**
> * For a **single one-time investment** (like buying a stock, buying property, or depositing an FD), use **CAGR**.
> * For **multiple irregular or periodic deposits** (like a monthly mutual fund SIP), use **XIRR**.

---

## 4. Realistic Long-Term CAGR Benchmarks in India (2026)

When assessing portfolio performance or projecting future wealth, use these historically validated rolling-return benchmarks for Indian asset classes:

* **Nifty 50 Index / Large Cap Funds:** 11.0% – 12.5% CAGR (Rolling 15-Year historical norm)
* **Flexi Cap / Multi Cap Funds:** 12.5% – 14.0% CAGR
* **Mid Cap Funds:** 13.5% – 15.5% CAGR (Higher cyclical drawdowns)
* **Small Cap Funds:** 14.5% – 17.0% CAGR (High standard deviation)
* **Physical Gold / SGB:** 8.5% – 10.5% CAGR
* **Bank Fixed Deposits (Post-Tax):** 4.5% – 5.5% net CAGR
* **Residential Real Estate (Metros):** 7.0% – 9.0% CAGR (Excluding rental yield)

---

## 5. Frequently Asked Questions on CAGR

### Can CAGR be negative?
Yes. If the ending value of your investment is lower than the beginning value, CAGR will be negative. For example, if an investment of ₹1,00,000 drops to ₹80,000 over 3 years, the CAGR is $-7.17\%$ per annum.

### Does CAGR reflect market volatility during the holding period?
No. CAGR only looks at the starting date and the ending date. It assumes a smooth, constant annual progression. An asset that grew steadily by 12% every single year will have the exact same CAGR as an asset that swung between +40% and -20% but reached the same terminal value.

### How do I calculate CAGR on an investment with intermediate dividends?
If you receive regular dividends and do not reinvest them, standard CAGR understates your true return. In mutual funds, always choose the **Growth Option** (where all capital gains and dividends are retained inside the NAV) so that published CAGR reflects total comprehensive wealth growth.

---

<div class="mt-8 p-6 bg-slate-50 rounded-2xl border border-slate-200 not-prose">
    <h3 class="text-base font-bold text-slate-800 mb-3">Explore Related Calculators</h3>
    <div class="grid sm:grid-cols-2 gap-3 text-sm">
        <a href="/cagr-calculator" class="p-3 bg-white rounded-xl border border-slate-200 hover:border-blue-500 font-semibold text-blue-700 flex items-center justify-between">
            <span>CAGR Return Calculator</span>
            <span>→</span>
        </a>
        <a href="/compound-interest-calculator" class="p-3 bg-white rounded-xl border border-slate-200 hover:border-blue-500 font-semibold text-blue-700 flex items-center justify-between">
            <span>Compound Interest Calculator</span>
            <span>→</span>
        </a>
        <a href="/lumpsum-calculator" class="p-3 bg-white rounded-xl border border-slate-200 hover:border-blue-500 font-semibold text-blue-700 flex items-center justify-between">
            <span>Lumpsum Mutual Fund Planner</span>
            <span>→</span>
        </a>
        <a href="/sip-calculator" class="p-3 bg-white rounded-xl border border-slate-200 hover:border-blue-500 font-semibold text-blue-700 flex items-center justify-between">
            <span>SIP Calculator</span>
            <span>→</span>
        </a>
    </div>
</div>
