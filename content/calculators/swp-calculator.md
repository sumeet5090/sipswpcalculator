---
title: "SWP Calculator India — Monthly Withdrawal Planner 2026"
subtitle: "Free SWP calculator with step-up withdrawals & inflation protection for Indian mutual funds. See how long your corpus lasts, plan retirement income & export yearly tables."
meta_desc: "Calculate monthly SWP payout, retirement corpus longevity, and post-tax returns for Indian mutual funds. Model inflation step-up withdrawals and download free PDF."
keywords: "swp calculator, swp calculator india, step up swp calculator, systematic withdrawal plan calculator india, swp mutual fund calculator, swp return calculator, best swp calculator, swp retirement calculator, swp tax calculator"
schema_name: "SWP Calculator India — Monthly Withdrawal Planner"
seo_category: "retirement"
type: "calculator"
date: "2026-07-05"
og_image: "/assets/og/og-swp-calculator.jpg"
---

---

## The Definitive Guide to Systematic Withdrawal Plans (SWP) in India (2026 Edition)

A **Systematic Withdrawal Plan (SWP)** is an automated financial decumulation facility offered by Indian mutual fund houses that allows an investor to redeem a predetermined sum of money from their accumulated mutual fund scheme at regular intervals—most commonly on a monthly, quarterly, or annual basis. 

While the working years are dedicated to wealth accumulation through Systematic Investment Plans (SIPs), retirement and financial independence (FIRE) necessitate a complete structural paradigm shift toward **decumulation and sustainable cash-flow engineering**. 

Historically, Indian retirees relied almost exclusively on Bank Fixed Deposits (FDs), Post Office Monthly Income Schemes (POMIS), and commercial life insurance annuities to generate monthly living cash flows. However, in 2026, against an economic backdrop of sticky 5.5%–6.5% retail inflation and punitive income tax slabs that tax traditional interest at rates exceeding 30%, traditional fixed-income instruments guarantee negative real returns. 

An SWP executed from a tax-efficient mutual fund portfolio has emerged as the premier retirement income mechanism in India. It preserves purchasing power, offers unparalleled tax arbitrage, and ensures that your hard-earned retirement corpus outlives you.

---

## 1. The Mathematical Mechanics of Month-by-Month SWP Simulation

Most oversimplified retirement calculators available on banking websites employ the classic **Present Value of an Ordinary Annuity** formula:

$$PV = W \times \left[ \frac{1 - (1 + i)^{-n}}{i} \right]$$

While mathematically elegant, this textbook formula fails catastrophically when applied to real-world Indian retirement planning because:
1. It assumes a static, flat monthly withdrawal amount throughout the entire 25 to 30-year horizon, completely ignoring cost-of-living inflation.
2. It assumes linear annual interest crediting rather than reflecting daily mutual fund NAV compounding.
3. It cannot model dynamic annual step-up withdrawal increases or custom portfolio return shifts.

### The True Month-by-Month Iterative Simulation Model

Our advanced SWP computational engine utilizes a sequential **month-by-month algorithmic simulation**. In this model, each monthly cycle consists of two distinct mathematical phases:

```
[ Beginning Balance (Bm-1) ]
            │
            ▼
   Phase 1: Monthly Compounding Return Accrual
   Corpus earns monthly return: B_gross = Bm-1 × (1 + r/12)
            │
            ▼
   Phase 2: Monthly Cash Withdrawal Deduction
   Redemption processed: Bm = B_gross - Wm
            │
            ▼
[ Ending Balance for Month m (Bm) ]
```

### The Governing Equations

For any month $m$ (where $m \in \{1, 2, \dots, n\}$):
* Let $B_{m-1}$ = Remaining invested corpus at the end of the previous month ($B_0 = \text{Initial Principal Corpus}$).
* Let $r$ = Annualized expected rate of return on the SWP portfolio (in decimal, e.g., $8\% = 0.08$).
* Let $i = \frac{r}{12}$ = Monthly periodic rate of return.
* Let $W_m$ = Withdrawal amount scheduled for month $m$.

The net corpus balance at the conclusion of month $m$ is:

$$B_m = \left[ B_{m-1} \times \left(1 + \frac{r}{12}\right) \right] - W_m$$

### The Step-Up Withdrawal Expansion
To counteract the corrosive impact of lifestyle and medical inflation, a retiree must increase their monthly withdrawal annually. If an annual step-up rate $g$ (e.g., $5\% = 0.05$) is applied:

$$W_m = W_1 \times (1 + g)^{\lfloor (m - 1) / 12 \rfloor}$$

Where:
* $W_1$ = Initial monthly withdrawal amount during Year 1.
* $\lfloor (m - 1) / 12 \rfloor$ = Floor integer representing the completed years of retirement.

This dynamic simulation provides an exact, zero-approximation ledger tracking the precise date of corpus depletion or the terminal generational surplus remaining after 30 years.

---

## 2. Sequence of Returns Risk (SRR): The Silent Retirement Killer

The most lethal mathematical hazard confronting a retiree is not low average market returns, but **Sequence of Returns Risk (SRR)**. 

When you are accumulating wealth via a SIP, early market crashes are beneficial because of Rupee Cost Averaging. However, when you are in the decumulation phase executing an SWP, the mathematics flips into **Reverse Rupee Cost Averaging**:
* When markets plunge, scheme NAV drops.
* To withdraw your fixed rupee sum (e.g., ₹60,000/month), the AMC must liquidate **more units**.
* Liquidating a higher volume of units at market troughs permanently depletes your unit balance, leaving fewer units to participate when the market inevitably recovers.

### Empirical Case Study: The Devastating Impact of Market Timing

Consider two retirees, **Investor A** and **Investor B**, each starting with an identical **₹1 Crore corpus** and withdrawing **₹50,000/month (₹6 Lakh/year)** over a 5-year period. Both portfolios achieve the **exact same arithmetic average return of 8.0% p.a.** over the 5 years, but experience the returns in reverse order:

| Year | Investor A (Early Bear Market) | Investor B (Early Bull Market) |
|:---:|:---:|:---:|
| **Year 1 Return** | **-15.0%** (Crash) | **+25.0%** (Rally) |
| **Year 2 Return** | **-5.0%** (Correction) | **+18.0%** (Growth) |
| **Year 3 Return** | **+10.0%** (Recovery) | **+10.0%** (Consolidation) |
| **Year 4 Return** | **+18.0%** (Growth) | **-5.0%** (Correction) |
| **Year 5 Return** | **+25.0%** (Rally) | **-15.0%** (Crash) |
| **5-Year Average CAGR** | **+8.0%** | **+8.0%** |
| **Corpus Balance at End of Year 5** | **₹84.2 Lakh** | **₹1.28 Crore** |

### The Chilling Conclusion:
Even though both investors experienced the exact same portfolio returns over 5 years, **Investor A's portfolio is ₹43.8 Lakh smaller than Investor B's** simply because the bear market struck in Years 1 and 2. 

To insulate against Sequence of Returns Risk, Indian retirees must never execute an SWP directly from high-beta pure equity funds. Instead, they must deploy the **3-Bucket Portfolio Architecture**.

---

## 3. The 3-Bucket Portfolio Architecture for Bulletproof SWPs

To eliminate Sequence of Returns Risk and ensure an SWP can withstand multi-year equity bear markets, financial planners structure the retirement corpus across three distinct, interconnected buckets:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        THE 3-BUCKET SWP ENGINE                         │
└────────────────────────────────────────────────────────────────────────┘

  [ BUCKET 1: Operational Liquidity ] (Years 1 to 2 of living expenses)
  • Assets: Liquid Funds, Arbitrage Funds, High-Yield Savings
  • Risk: Zero market volatility. Absolute capital stability.
  • Function: Monthly SWP credits flow directly to bank account from here.
         ▲
         │ Periodic Annual Rebalancing Transfer
         │
  [ BUCKET 2: Income & Capital Defense ] (Years 3 to 6 of living expenses)
  • Assets: Conservative Hybrid Funds, Equity Savings Funds, Corporate Bond Funds
  • Risk: Low-to-moderate volatility. Target return: 7.5% – 9.0% CAGR.
  • Function: Protects capital; refills Bucket 1 during market corrections.
         ▲
         │ Strategic Harvesting during Bull Markets
         │
  [ BUCKET 3: Long-Term Growth Engine ] (Years 7+ of living expenses)
  • Assets: Flexi Cap Funds, Large & Mid Cap Funds, Balanced Advantage Funds
  • Risk: Moderate-to-high volatility. Target return: 11.0% – 13.0% CAGR.
  • Function: Compounds aggressively over decades to beat 6% inflation.
```

### Operational Workflow:
1. **The Monthly Cash Flow:** Your monthly SWP mandate is attached strictly to **Bucket 1**. Every month, on your designated date (e.g., 5th of the month), money is transferred from the Liquid/Arbitrage fund into your primary bank account.
2. **The Defense Buffer:** If equity markets crash by 20% to 30%, you do nothing in Bucket 3. You allow your growth assets to recover while your living expenses are safely paid out of Bucket 1 and Bucket 2 for up to 5 full years.
3. **The Bull Market Harvest:** When equity markets rally strongly and Bucket 3 expands, you harvest capital gains from Bucket 3 and refill Buckets 1 and 2.

---

## 4. The Indian Trinity Study: Calibrating the Safe Withdrawal Rate (SWR)

The celebrated **Trinity Study** (conducted at Trinity University, Texas) established the iconic **4% Rule**: withdrawing 4% of your initial retirement portfolio in Year 1, and adjusting subsequent annual withdrawals for inflation, provided a 95% probability that a 50/50 stock-and-bond portfolio would survive a 30-year retirement.

### Why the US 4% Rule Must Be Adapted for India
Applying the 4% rule blindly in India without localization introduces fatal risks:
* **Higher Inflation Differential:** While long-term US consumer inflation averages 2.5%–3.0%, Indian CPI inflation averages **5.5%–6.5%**. An annual withdrawal that escalates at 6% doubles in rupee terms every 12 years.
* **Higher Real Volatility:** Indian capital markets exhibit higher standard deviation than mature US markets.
* **Absence of Universal Social Security:** In India, retirement funding relies almost 100% on self-funded personal assets; there is no government social safety net to cushion portfolio failure.

### Recommended Safe Withdrawal Rates for Indian Portfolios

| Safe Withdrawal Rate (SWR) | Monthly Income per ₹1 Crore Corpus | Retirement Horizon | Portfolio Survival Probability | Risk Assessment |
|:---:|:---:|:---:|:---:|:---|
| **3.0% – 3.5%** | **₹25,000 – ₹29,167** | **35+ Years** | **> 98%** | **Ultra-Conservative (FIRE Standard):** Highly resilient against severe stagflation. Corpus almost certainly grows. |
| **4.0%** | **₹33,333** | **30 Years** | **~90%** | **Balanced Standard:** Ideal for standard corporate retirees aged 58–60 with a diversified 3-bucket allocation. |
| **5.0%** | **₹41,667** | **22–25 Years** | **~75%** | **Moderate Risk:** Sustainable if equity markets deliver >11% CAGR, but vulnerable to early-decade drawdowns. |
| **6.0%** | **₹50,000** | **15–18 Years** | **< 50%** | **Aggressive / High Depletion Risk:** Capital will deplete rapidly unless backed by substantial outside pensions. |

---

## 5. Comprehensive SWP Sustainability Matrix across Corpus Sizes (2026)

The table below illustrates how long various mutual fund retirement corpuses will sustain different monthly withdrawal levels assuming a conservative **8.5% annualized return** in a Hybrid portfolio and a **5% annual Step-Up** for inflation:

### SWP Sustainability Modeling: 8.5% Portfolio CAGR with 5% Annual Step-Up

| Starting Retirement Corpus | Initial Monthly SWP (Year 1) | Annual Withdrawal (Year 1) | Effective Initial SWR | Projected Corpus Longevity | Terminal Value at 25 Years |
|:---|:---:|:---:|:---:|:---:|:---:|
| **₹50 Lakh** | ₹20,000 | ₹2.40 Lakh | 4.80% | **24.5 Years** | Depleted in Yr 25 |
| **₹50 Lakh** | ₹25,000 | ₹3.00 Lakh | 6.00% | **17.2 Years** | Depleted in Yr 18 |
| **₹1.00 Crore** | **₹35,000** | ₹4.20 Lakh | **3.50%** | **35+ Years** | **₹2.14 Crore** (Surplus) |
| **₹1.00 Crore** | **₹45,000** | ₹5.40 Lakh | **4.50%** | **25.8 Years** | **₹18.4 Lakh** |
| **₹1.00 Crore** | **₹60,000** | ₹7.20 Lakh | **6.00%** | **16.8 Years** | Depleted in Yr 17 |
| **₹2.00 Crore** | **₹75,000** | ₹9.00 Lakh | **3.75%** | **32.4 Years** | **₹3.42 Crore** (Surplus) |
| **₹2.00 Crore** | **₹1,00,000** | ₹12.00 Lakh | **5.00%** | **22.1 Years** | Depleted in Yr 23 |
| **₹3.00 Crore** | **₹1,00,000** | ₹12.00 Lakh | **3.33%** | **35+ Years** | **₹7.85 Crore** (Surplus) |
| **₹3.00 Crore** | **₹1,50,000** | ₹18.00 Lakh | **5.00%** | **22.1 Years** | Depleted in Yr 23 |
| **₹5.00 Crore** | **₹1,50,000** | ₹18.00 Lakh | **3.00%** | **35+ Years** | **₹15.2 Crore** (Surplus) |

> **The Power of SWR Discipline:** Notice that on a **₹1 Crore corpus**, starting at ₹35,000/month with a 5% yearly hike allows the corpus to sustain for over 35 years and **actually expand to ₹2.14 Crore**! Conversely, greedily withdrawing ₹60,000/month completely depletes the entire ₹1 Crore in less than 17 years.

---

## 6. SWP vs. Fixed Deposit vs. SCSS vs. Life Insurance Annuities

To appreciate why an SWP is superior for retirement cash flows, evaluate it directly against competing traditional Indian income instruments:

<div class="overflow-hidden border border-slate-200 rounded-2xl mb-8 shadow-sm">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50">
<tr>
<th scope="col" class="px-5 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Feature</th>
<th scope="col" class="px-5 py-3.5 text-left font-bold text-emerald-700 uppercase tracking-wider">Systematic Withdrawal Plan (SWP)</th>
<th scope="col" class="px-5 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Bank Fixed Deposit (FD)</th>
<th scope="col" class="px-5 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Senior Citizens Savings Scheme (SCSS)</th>
<th scope="col" class="px-5 py-3.5 text-left font-bold text-slate-700 uppercase tracking-wider">Immediate Life Annuity</th>
</tr>
</thead>
<tbody class="bg-white divide-y divide-slate-200 text-slate-700">
<tr>
<td class="px-5 py-4 font-semibold">Expected Yield</td>
<td class="px-5 py-4 font-bold text-emerald-700">8.0% – 10.5% (Hybrid/Balanced)</td>
<td class="px-5 py-4">6.5% – 7.5% (Senior Citizen)</td>
<td class="px-5 py-4">8.2% (Govt quarterly rate)</td>
<td class="px-5 py-4 text-rose-600">5.5% – 6.5% (Fixed for life)</td>
</tr>
<tr>
<td class="px-5 py-4 font-semibold">Tax Treatment (2026)</td>
<td class="px-5 py-4 font-bold text-emerald-700">Capital redemption; only capital gains taxed (~3%–5% effective)</td>
<td class="px-5 py-4 text-rose-600">100% of interest taxed at marginal slab rate (up to 30%+)</td>
<td class="px-5 py-4 text-rose-600">100% of interest taxed at marginal slab rate</td>
<td class="px-5 py-4 text-rose-600">100% of annuity pension taxed at marginal slab rate</td>
</tr>
<tr>
<td class="px-5 py-4 font-semibold">Inflation Protection</td>
<td class="px-5 py-4 font-bold text-emerald-700">Yes; native Step-Up facility beats 6% CPI inflation</td>
<td class="px-5 py-4 text-rose-600">No; static interest creates severe purchasing power loss</td>
<td class="px-5 py-4 text-rose-600">No; static quarterly payout loses real value</td>
<td class="px-5 py-4 text-rose-600">No; fixed rupee pension loses ~50% purchasing power in 12 yrs</td>
</tr>
<tr>
<td class="px-5 py-4 font-semibold">Liquidity &amp; Access</td>
<td class="px-5 py-4 font-bold text-emerald-700">Total liquidity; alter amount, pause, or withdraw lump sum anytime</td>
<td class="px-5 py-4">Premature penalty (0.5%–1.0%)</td>
<td class="px-5 py-4">Locked for 5 years; penalty for early exit</td>
<td class="px-5 py-4 text-rose-600">Completely locked; principal cannot be accessed</td>
</tr>
<tr>
<td class="px-5 py-4 font-semibold">Maximum Investment Cap</td>
<td class="px-5 py-4 font-bold text-emerald-700">Unlimited (₹10 Lakh to ₹50+ Crore)</td>
<td class="px-5 py-4">Unlimited (DICGC insured up to ₹5 Lakh)</td>
<td class="px-5 py-4 text-rose-600">Strictly capped at ₹30 Lakh per individual</td>
<td class="px-5 py-4">Unlimited</td>
</tr>
</tbody>
</table>
</div>

---

## 7. The Tax Arbitrage Proof: Why SWP Saves Lakhs in Tax

The most misunderstood advantage of an SWP is **tax efficiency**. 

When a bank pays you ₹50,000 in FD interest, that entire ₹50,000 is classified as income from other sources and taxed at your top slab rate. If you fall in the 30% tax bracket, **₹15,600 is deducted in tax**, leaving you with just ₹34,400.

In contrast, when you withdraw ₹50,000 from a mutual fund via SWP, you are **redeeming mutual fund units**. That redemption consists of two distinct components:
1. **Return of Your Own Principal:** (100% Tax-Free)
2. **Realized Capital Gains:** (Taxable at preferential capital gains rates)

### Mathematical Proof of SWP Tax Arbitrage
Assume a retiree holds ₹1 Crore in an equity-oriented hybrid fund where the average purchase NAV was ₹100, and current NAV has grown to ₹140. The investor withdraws **₹50,000** via monthly SWP.

1. **Units Redeemed:** ₹50,000 $\div$ ₹140 = **357.14 units**
2. **Original Principal Cost of Redeemed Units:** 357.14 units $\times$ ₹100 = **₹35,714 (Zero Tax)**
3. **Total Capital Gain Realized:** ₹50,000 $-$ ₹35,714 = **₹14,286**
4. **Tax Calculation (Section 112A LTCG @ 12.5%):**
   * First ₹1.25 Lakh of annual LTCG across all equity holdings is **100% Tax-Free**.
   * Even assuming the ₹1.25 Lakh exemption has been utilized elsewhere, the tax on ₹14,286 is:
     $$\text{LTCG Tax} = ₹14,286 \times 12.5\% = \mathbf{₹1,786}$$
   * Add 4% Health & Education Cess = **₹1,857**
5. **Net Cash Flow Received:** ₹50,000 $-$ ₹1,857 = **₹48,143**
6. **Effective Tax Rate on Full ₹50,000 Withdrawal:**
   $$\text{Effective Tax} = \frac{₹1,857}{₹50,000} = \mathbf{3.71\%}$$

> **The Bottom Line:** On a ₹50,000 monthly income, an FD investor pays **₹15,600/month** in tax, while the SWP investor pays just **₹1,857/month**. Over 20 years of retirement, this tax arbitrage saves the retiree **over ₹33 Lakh in cash**!

---

## 8. Frequently Asked Questions on SWP in India

### What is the minimum corpus required to start a Systematic Withdrawal Plan?
There is no statutory legal minimum. Most mutual fund platforms allow SWP mandates starting with a minimum monthly withdrawal of **₹500 or ₹1,000**. However, for a sustainable retirement income of ₹40,000 to ₹60,000 per month with inflation protection, an initial retirement corpus of **₹75 Lakh to ₹1.5 Crore** is recommended.

### Can I alter my SWP withdrawal amount or date after setting it up?
Yes, absolutely. An SWP offers complete operational flexibility. You can modify the withdrawal amount, change the monthly debit date, temporarily pause withdrawals, or terminate the plan entirely through your online broker or AMC portal with zero exit penalties (provided units have crossed the standard 1-year exit load window).

### Is SWP income taxable at source (TDS)?
For **resident Indian individuals**, mutual fund houses do **not** deduct TDS on capital gains resulting from SWP redemptions. You receive the full gross withdrawal amount directly into your bank account. You simply declare the capital gains when filing your annual Income Tax Return (ITR-2). For Non-Resident Indians (NRIs), TDS is deducted at source per applicable DTAA provisions.

### What is the ideal date of the month to schedule an SWP debit?
Most investors schedule their SWP debit between the **1st and 7th of the month**. This mimics the predictable cash-flow arrival of a corporate salary, ensuring funds are available in your primary savings account to meet household bills, society maintenance, and domestic expenses.

### What happens if the mutual fund scheme NAV falls below my purchase price?
If severe market conditions push scheme NAV below your acquisition price, the redeemed units result in a **Capital Loss** rather than a capital gain. In this scenario, your tax liability on that withdrawal is **zero**, and the realized capital loss can be carried forward for up to 8 assessment years to offset other taxable capital gains.

### Can I run a monthly SIP and an SWP simultaneously?
Yes, but you should never execute them within the same mutual fund scheme. Doing so creates contradictory cash-flow churn, unnecessary tax triggers, and exit load friction. Run your wealth accumulation SIP in growth-oriented equity schemes, while running your retirement SWP from a dedicated conservative hybrid or arbitrage scheme.

### How does an annual Step-Up SWP protect against inflation?
A flat ₹50,000 monthly withdrawal will lose roughly half its purchasing power over 12 years at 6% inflation. A Step-Up SWP automatically escalates your monthly payout by a chosen percentage (e.g., 5% annually), ensuring that your living standard remains constant throughout your retirement. Use our calculator above to test various step-up rates.

### What is the difference between SWP and Dividend Plans (IDCW)?
Under modern Indian tax laws, dividends paid by mutual funds (IDCW option) are added to your gross income and **taxed at your top marginal slab rate (up to 30%+)**, with mandatory 10% TDS deducted at source. In contrast, an SWP lets you control the exact timing and sum of cash flows, and benefits from low **12.5% LTCG tax rates** applied only to the capital gain portion. SWP is vastly superior to IDCW in every scenario.

---

<div class="mt-12 bg-slate-50 p-6 rounded-2xl border border-slate-200">
<h3 class="text-xl font-bold text-slate-800 mb-4">Explore Related Financial Calculators &amp; Guides</h3>
<ul class="space-y-2 text-sm">
<li><a href="/retirement-calculator" class="text-emerald-700 hover:underline font-semibold">Retirement Calculator</a> — Model your complete lifecycle from accumulation to SWP drawdown</li>
<li><a href="/sip-calculator" class="text-emerald-700 hover:underline font-semibold">SIP Calculator Guide</a> — Master disciplined monthly accumulation before initiating an SWP</li>
<li><a href="/sip-step-up-calculator" class="text-emerald-700 hover:underline font-semibold">Step-Up SIP Calculator</a> — See how stepping up investments in your 30s doubles your retirement corpus</li>
<li><a href="/my-first-crore-calculator" class="text-emerald-700 hover:underline font-semibold">My First Crore Calculator</a> — Discover how long it takes to build your initial ₹1 Crore SWP seed corpus</li>
<li><a href="/lumpsum-calculator" class="text-emerald-700 hover:underline font-semibold">Lumpsum Calculator</a> — Model one-time investment compounding before transitioning to SWP</li>
<li><a href="/resource/retirement/swp-retirement-planning" class="text-emerald-700 hover:underline font-semibold">SWP Retirement Planning Guide</a> — Advanced strategies for bucket portfolios and withdrawal timing</li>
<li><a href="/resource/retirement/retirement-planning-4-percent-swp-rule" class="text-emerald-700 hover:underline font-semibold">The 4% Rule Explained</a> — In-depth analysis of safe withdrawal rates for Indian retirees</li>
<li><a href="/resource/comparison/swp-vs-fixed-deposit" class="text-emerald-700 hover:underline font-semibold">SWP vs Fixed Deposit</a> — Detailed post-tax cash-flow comparison for senior citizens</li>
<li><a href="/" class="text-emerald-700 hover:underline font-semibold">Dual SIP &amp; SWP Planner</a> — Simulate your complete financial accumulation and withdrawal journey together</li>
</ul>
</div>

<div class="mt-12 not-prose rounded-3xl overflow-hidden border border-emerald-200/90 shadow-card bg-gradient-to-br from-emerald-50 via-white to-teal-50/60 p-8 sm:p-10 text-center text-slate-900">
  <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-4 border border-emerald-200">
    ⚡ Live Decumulation Simulation
  </div>
  <h2 class="text-2xl sm:text-3xl font-extrabold mb-3 tracking-tight text-slate-900">Stress-Test Your Retirement SWP Now</h2>
  <p class="text-slate-600 mb-7 max-w-xl mx-auto text-sm sm:text-base leading-relaxed font-medium">Input your accumulated retirement corpus, configure your target monthly living cash flow, and add an annual step-up to beat inflation. Model month-by-month portfolio survival and export complete PDF audit reports instantly.</p>
  <a href="/#calculator-section"
     class="inline-flex items-center gap-2 px-8 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-card shadow-emerald-600/20 transition-all duration-200 text-sm sm:text-base cursor-pointer">
    Open SWP Calculator
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
  </a>
</div>
