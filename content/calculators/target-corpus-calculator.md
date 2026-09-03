---
title: "Target Corpus Calculator India (2026) — Goal-Seek Tool"
subtitle: "Find the exact monthly SIP needed to reach any target corpus (₹25L, ₹50L, ₹1 Cr, ₹5 Cr). Reverse-engineer your investment goals with annual step-up optimization."
meta_desc: "Calculate the exact monthly SIP needed for any financial goal (₹25L, ₹50L, ₹1Cr, ₹5Cr). Model timelines, annual step-ups, inflation, and 2026 LTCG tax."
keywords: "target corpus calculator, goal sip calculator, required sip calculator, reverse sip calculator, how much sip for target, sip goal planner, financial goal calculator india, goal-based investment planner"
schema_name: "Target Corpus Calculator — Find Required Monthly SIP"
seo_category: "growth"
type: "calculator"
date: "2026-08-24"
---

---

Every major life milestone in India—purchasing a residential apartment, funding overseas undergraduate education, orchestrating a child's wedding, or securing a fully self-sustaining retirement nest egg—demands an explicit **Target Corpus**. Most personal finance tools take an investment amount (e.g., ₹10,000/month) and project an arbitrary future value. However, goal-based financial planning works in reverse: you define the future capital required, the exact target date, and your risk tolerance, and reverse-engineer the precise monthly contribution needed to meet that commitment with mathematical certainty.

This **Target Corpus Calculator** operates as a high-precision **Goal-Seek Solver**. By combining the closed-form inverse annuity-due equation with an iterative binary search algorithm for annual top-up (step-up) increments, this engine computes your exact required starting SIP, visualizes the contribution-to-gain ratio, and factors in real-world Indian inflation benchmarks and capital gains tax liabilities.

---

## The Mathematics of Goal-Seeking: Inverse Annuity-Due Derivation

To understand how our engine reverse-engineers your required monthly investment, consider the standard future value formula for an annuity due (where investments occur at the beginning of each monthly compounding period):

$$ FV = P \times \frac{(1 + r)^n - 1}{r} \times (1 + r) $$

Where:
* **$FV$**: Target Corpus (the desired maturity sum).
* **$P$**: Monthly SIP installment required.
* **$r$**: Effective monthly rate of return, calculated as $\frac{\text{Annual Rate (in \%)}}{12 \times 100}$.
* **$n$**: Total number of monthly compounding periods ($\text{Tenure in Years} \times 12$).

### Algebraic Inversion for Required Monthly SIP ($P$)

By isolating the installment term $P$, we obtain the closed-form inverse equation:

$$ P = \frac{FV \times r}{(1 + r) \times \left[ (1 + r)^n - 1 \right]} $$

### The Step-Up Challenge: Solving Non-Linear Iterations via Binary Search
When an investor introduces an **Annual Step-Up (Top-Up)** percentage (e.g., increasing monthly contributions by 10% every 12 months), the closed-form algebraic formula breaks down into a piecewise geometric series:

$$ FV = \sum_{y=1}^{Y} \left[ P_y \times \sum_{m=1}^{12} (1 + r)^{12(Y - y) + (13 - m)} \right] $$

Where $P_y = P_1 \times (1 + s)^{y-1}$ and $s$ is the annual step-up rate. 

Because solving for the starting installment $P_1$ cannot be expressed as a simple single-variable fraction, our calculation engine employs a **Binary Search Convergence Protocol**. The algorithm establishes bounds $[P_{\min} = 1, P_{\max} = FV]$, iteratively calculating projected returns with floating-point precision until the difference between the modeled future value and your target corpus is less than **₹0.01 (1 paisa)**. This zero-latency computation happens entirely in your local browser runtime.

---

## Master Target Corpus Matrix: Required Monthly SIPs

Below is the definitive reference table illustrating the required monthly SIP across five major Indian wealth milestones (₹25 Lakhs, ₹50 Lakhs, ₹1 Crore, ₹2 Crores, and ₹5 Crores) across different investment horizons, assuming a standard **12% CAGR** from diversified equity mutual funds (without step-up):

### Required Monthly SIP at 12% CAGR (Standard Market Return)

| Target Corpus | 3 Years Horizon | 5 Years Horizon | 7 Years Horizon | 10 Years Horizon | 15 Years Horizon | 20 Years Horizon | 25 Years Horizon |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **₹10 Lakhs** | ₹23,094 / mo | ₹12,123 / mo | ₹7,518 / mo | ₹4,304 / mo | ₹1,982 / mo | ₹1,001 / mo | ₹527 / mo |
| **₹25 Lakhs** | ₹57,736 / mo | ₹30,308 / mo | ₹18,795 / mo | ₹10,760 / mo | ₹4,955 / mo | ₹2,502 / mo | ₹1,318 / mo |
| **₹50 Lakhs** | ₹1,15,471 / mo | ₹60,616 / mo | ₹37,589 / mo | ₹21,520 / mo | ₹9,910 / mo | ₹5,005 / mo | ₹2,635 / mo |
| **₹1 Crore** | ₹2,30,942 / mo | ₹1,21,232 / mo | ₹75,178 / mo | ₹43,041 / mo | ₹19,819 / mo | ₹10,009 / mo | ₹5,270 / mo |
| **₹2 Crores** | ₹4,61,884 / mo | ₹2,42,464 / mo | ₹1,50,356 / mo | ₹86,082 / mo | ₹39,638 / mo | ₹20,018 / mo | ₹10,540 / mo |
| **₹5 Crores** | ₹11,54,710 / mo | ₹6,06,160 / mo | ₹3,75,890 / mo | ₹2,15,205 / mo | ₹99,095 / mo | ₹50,045 / mo | ₹26,350 / mo |
| **₹10 Crores** | ₹23,09,420 / mo | ₹12,12,320 / mo | ₹7,51,780 / mo | ₹4,30,410 / mo | ₹1,98,190 / mo | ₹1,00,090 / mo | ₹52,700 / mo |

### High-Alpha vs. Conservative Return Sensitivity Matrix (10-Year Horizon)
Market returns are not static. To ensure your goal planning remains resilient under diverse market conditions, the matrix below highlights how your required monthly deposit changes for a **10-Year Horizon** under varying CAGRs:

| Target Corpus | At 8% CAGR (Debt/Conservative) | At 10% CAGR (Large-Cap / Hybrid) | At 12% CAGR (Broad Market Index) | At 14% CAGR (Aggressive Flexi/Mid) | At 15% CAGR (High Growth Alpha) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **₹25 Lakhs** | ₹13,674 / mo | ₹12,104 / mo | **₹10,760 / mo** | ₹9,551 / mo | ₹8,993 / mo |
| **₹50 Lakhs** | ₹27,348 / mo | ₹24,207 / mo | **₹21,520 / mo** | ₹19,103 / mo | ₹17,986 / mo |
| **₹1 Crore** | ₹54,696 / mo | ₹48,414 / mo | **₹43,041 / mo** | ₹38,206 / mo | ₹35,972 / mo |
| **₹2 Crores** | ₹1,09,392 / mo | ₹96,828 / mo | **₹86,082 / mo** | ₹76,412 / mo | ₹71,944 / mo |
| **₹5 Crores** | ₹2,73,480 / mo | ₹2,42,070 / mo | **₹2,15,205 / mo** | ₹1,91,030 / mo | ₹1,79,860 / mo |

---

## The Goal Categorization & Time-Horizon Framework

Not all financial goals should be funded through the same asset class. Attempting to accumulate a 2-year goal through aggressive equity mutual funds introduces catastrophic volatility risk, while parking a 15-year goal in fixed deposits guarantees negative real returns after taxes and inflation.

Use this institutional framework to match your goal horizon with the appropriate financial instruments:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                 GOAL HORIZON & ASSET ALLOCATION BLUEPRINT               │
├──────────────────┬─────────────────┬────────────────────────────────────┤
│ Horizon Category │ Tenure Window   │ Target Instruments & Allocation    │
├──────────────────┼─────────────────┼────────────────────────────────────┤
│ Ultra-Short Term │ < 12 Months     │ Overnight Funds, Liquid Funds, FDs │
│ Short-Term Goals │ 1 to 3 Years    │ Ultra-Short Debt, Arbitrage Funds  │
│ Medium-Term Goals│ 3 to 7 Years    │ Conservative/Balanced Advantage    │
│ Long-Term Goals  │ 7 to 15 Years   │ Flexi-Cap, Nifty 50, Large & Mid   │
│ Ultra Long-Term  │ > 15 Years      │ Mid-Cap, Small-Cap, Broad Equity   │
└──────────────────┴─────────────────┴────────────────────────────────────┘
```

### 1. Short-Term Goals (< 3 Years)
* **Examples:** Emergency fund buffer, annual vacation, car down payment, home interior renovation.
* **Risk Mandate:** Capital preservation is paramount. Equity market drawdowns cannot be recovered within 36 months.
* **Recommended Instruments:** High-yield Bank FDs, Arbitrage Funds (equity taxation with liquid debt risk), Ultra-Short Duration Debt Funds. Target Return: 6.5%–7.5% p.a.

### 2. Medium-Term Goals (3 to 7 Years)
* **Examples:** Down payment for a residential apartment, commercial certification, starting capital for an entrepreneurial venture.
* **Risk Mandate:** Moderate growth with buffered downside protection.
* **Recommended Instruments:** Dynamic Asset Allocation Funds (Balanced Advantage Funds), Multi-Asset Allocation Funds (Equity + Debt + Gold), Equity Savings Funds. Target Return: 9.0%–11.0% p.a.

### 3. Long-Term Goals (7 to 15+ Years)
* **Examples:** Child's undergraduate or postgraduate degree, early retirement corpus, accumulating family wealth.
* **Risk Mandate:** Maximum compounding power to outpace lifestyle inflation.
* **Recommended Instruments:** Pure Equity Mutual Funds (Nifty 50 Index, Flexi-Cap Funds, Mid-Cap Funds). Target Return: 12.0%–14.0% p.a.

---

## Inflation-Proofing Your Target Corpus: Education & Healthcare Realism

The single biggest error in goal planning is fixing a target corpus in **nominal today's rupees** without adjusting for sector-specific inflation. While headline consumer price inflation (CPI) in India hovers around 5%–6%, education and healthcare inflation consistently compound at nearly double that rate.

To determine your actual required future corpus, apply the Future Value inflation equation:

$$ FV_{\text{inflated}} = PV \times (1 + i)^t $$

Where $PV$ is the cost if paid today, $i$ is sector inflation, and $t$ is the number of years until the goal arrives.

### Inflation Multipliers Across Indian Life Goals

| Goal Type | Baseline Cost Today ($PV$) | Specific Inflation Rate ($i$) | Time Horizon ($t$) | Actual Inflated Target Needed ($FV$) | What Happens If You Ignore Inflation |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Child Higher Education (India)** | ₹20,00,000 | **10.0% p.a.** | 12 Years | **₹62,76,857** | **68% Shortfall** (Short by ₹42.7 Lakhs) |
| **Overseas Master's Degree (US/UK)** | ₹50,00,000 | **11.0% p.a.** (Incl. Forex) | 10 Years | **₹1,41,97,105** | **65% Shortfall** (Short by ₹92.0 Lakhs) |
| **Daughter/Son Wedding** | ₹30,00,000 | **8.0% p.a.** | 15 Years | **₹95,16,509** | **68% Shortfall** (Short by ₹65.1 Lakhs) |
| **3BHK Flat Down Payment** | ₹35,00,000 | **7.0% p.a.** | 7 Years | **₹56,20,243** | **38% Shortfall** (Short by ₹21.2 Lakhs) |
| **Post-Retirement Medical Emergency** | ₹15,00,000 | **12.0% p.a.** | 20 Years | **₹1,44,69,500** | **90% Shortfall** (Short by ₹1.30 Crores) |

> **Strategic Takeaway:** If an engineering or MBA degree costs ₹20 Lakhs today, planning an investment to reach ₹20 Lakhs 12 years from now will cover barely one-third of the tuition fees. Enter the **inflated future target** (e.g., ₹62.8 Lakhs) into our Target Corpus Calculator to determine the true required SIP.

---

## Step-Up Optimization: Slashing Your Starting SIP Commitment

If the required monthly deposit for your financial goal exceeds your current disposable surplus, do not compromise on the target amount or push back your timeline. Instead, activate the **Annual Step-Up (Top-Up)** feature.

By committing to increase your monthly investment by 5%, 10%, or 15% each year as your salary increases, you can begin with a significantly smaller initial deposit while finishing with the exact same target corpus.

### Case Study: Reaching a ₹50 Lakh Milestone in 10 Years (12% Return)

```
┌─────────────────────────────────────────────────────────────────────────┐
│             ₹50 LAKH GOAL IN 10 YEARS: FLAT VS. STEP-UP SIP            │
├───────────────────┬───────────────────┬─────────────────────────────────┤
│ Strategy          │ Starting SIP (Yr 1│ Total Amount Contributed        │
├───────────────────┼───────────────────┼─────────────────────────────────┤
│ Flat Fixed SIP    │ ₹21,520 / month   │ ₹25,82,400 (51.6% of goal)      │
│ 5% Annual Step-Up │ ₹16,925 / month   │ ₹25,56,921 (51.1% of goal)      │
│ 10% Annual Step-Up│ ₹13,275 / month   │ ₹24,97,416 (49.9% of goal)      │
│ 15% Annual Step-Up│ ₹10,400 / month   │ ₹24,07,740 (48.1% of goal)      │
└───────────────────┴───────────────────┴─────────────────────────────────┘
```

Notice the tremendous leverage: with a **10% annual step-up**, your starting requirement drops from **₹21,520/month to just ₹13,275/month**—a **38.3% immediate reduction** in cash-flow pressure!

---

## Dynamic Rebalancing & The 3-Year Glide Path Strategy

A fatal mistake made by retail investors is keeping 100% of their accumulated funds in equity mutual funds right up until the day the money is needed. If a severe market correction of 20% to 30% occurs during your final year (such as during the 2008 global financial crisis or the March 2020 pandemic), your accumulated ₹1 Crore could suddenly shrink to ₹70 Lakhs just weeks before college tuition or home registration payments are due.

To eliminate this sequence risk, implement a **3-Year Systematic De-risking Glide Path**:

```
Goal Horizon (T)
  ▲
  │  100% Equity Allocation (Nifty 50, Flexi-Cap, Mid-Cap)
  │  Years 1 to (T - 3): Aggressive compounding & unit accumulation
  │
  ├─► T - 3 Years: Shift 30% of accumulated corpus to Arbitrage / Short Debt via STP
  │
  ├─► T - 2 Years: Shift an additional 35% of corpus to Debt / Ultra-Short Funds
  │
  ├─► T - 1 Year:  Shift remaining equity; 100% of target corpus locked in liquid capital
  ▼
Goal Maturity: 100% capital protected, zero vulnerability to market crashes
```

By executing a monthly **Systematic Transfer Plan (STP)** from equity to liquid debt over the final 36 months, you systematically lock in your capital gains at market highs while insulating your principal from eleventh-hour downturns.

---

## Factoring Tax Drag into Your Target Corpus (Budget 2024 Rules)

Under the **Finance (No. 2) Act, 2024**, long-term capital gains on equity mutual funds are subject to taxation upon redemption. If you need exactly ₹1 Crore in hand for a property purchase or overseas tuition, withdrawing ₹1 Crore from an equity mutual fund will leave you with an unexpected deficit after tax deduction.

### Tax Treatment Summary for Equity Mutual Funds
* **Holding Period:** More than 12 months.
* **Annual LTCG Exemption:** **₹1,25,000** per financial year.
* **Tax Rate on Excess Gains:** **12.5%** + 4% Health & Education Cess = **13.0% effective tax**.

### Gross Target Adjustment Formula
To ensure your post-tax take-home amount precisely equals your target milestone ($FV_{\text{net}}$), gross up your target using your portfolio's estimated gain ratio:

$$ FV_{\text{gross}} = FV_{\text{net}} + \text{Estimated Capital Gains Tax} $$

For example, if you accumulate **₹1,00,00,000** over 15 years with a total contribution of **₹36,00,000**:
* **Gross Capital Gains:** ₹64,00,000
* **Taxable Gains (After ₹1.25L Exemption):** ₹62,75,000
* **Tax Payable (12.5% + 4% cess):** ₹8,15,750
* **Actual Cash Received:** ₹91,84,250 (**₹8.15 Lakh Shortfall**)

**Solution:** Increase your calculator's target corpus input from **₹1,00,00,000 to ₹1,09,00,000** so that your post-tax net redemption exactly covers your goal.

---

## 3 Real-World Goal Planning Case Studies

### Case Study 1: Rohan (Age 28) — Down Payment for a 3BHK in Pune
* **Goal:** Accumulate a ₹30 Lakh down payment in 6 Years.
* **Strategy:** Rohan allocates his investment into an aggressive hybrid fund (65% equity, 35% debt) targeting a **10.5% CAGR**.
* **Calculator Output:** A flat SIP requires **₹30,120/month**. Instead, Rohan chooses an **8% annual step-up**, starting with **₹24,200/month**.
* **Outcome:** Rohan comfortably manages his initial rent and savings commitments. At Year 6, his portfolio hits **₹30,45,000**, enabling him to secure his home loan at prime lending rates.

### Case Study 2: Dr. Meera (Age 36) — Son's Medical Education Fund
* **Goal:** Build an inflated target corpus of ₹85 Lakhs in 11 Years.
* **Strategy:** With an 11-year runway, Dr. Meera chooses a diversified equity basket (50% Flexi-Cap, 30% Large-Cap, 20% Mid-Cap) targeting a **12.5% CAGR**.
* **Calculator Output:** Required starting deposit is **₹27,850/month** with a **10% annual step-up**.
* **Outcome:** At Year 8, Dr. Meera initiates a monthly STP into liquid funds to lock in her gains. By Year 11, the tuition fund reaches **₹87,10,000**, unaffected by minor market corrections.

### Case Study 3: Sandeep & Kavita (Age 42 & 40) — Early Retirement Booster (FIRE)
* **Goal:** Reach a supplemental ₹2 Crore corpus in 8 Years.
* **Strategy:** Because both partners earn established senior corporate salaries, they deploy an aggressive **₹1,32,000/month** combined SIP at an assumed **12% CAGR**.
* **Outcome:** Their total lifetime investment of ₹1.26 Crores compounds by an additional ₹76.8 Lakhs in capital gains, reaching **₹2.03 Crores** right as Sandeep turns 50, providing the financial runway to transition to advisory consulting.

---

## Frequently Asked Questions (FAQ)

### What is a Target Corpus Calculator?
A Target Corpus Calculator is a goal-seek financial tool that reverse-engineers the exact monthly Systematic Investment Plan (SIP) required to accumulate a predetermined financial sum (such as ₹25 Lakh, ₹50 Lakh, or ₹1 Crore) over a specified time horizon and expected rate of return.

### How does the calculator solve for the required SIP when step-up is enabled?
When an annual step-up percentage is applied, contributions increase dynamically every 12 months, creating a piecewise compounding equation that cannot be solved with a simple fraction. Our engine uses an iterative **binary search algorithm** that tests candidate installment amounts until it converges on the starting monthly SIP with precision down to ₹0.01.

### How much SIP is required to build a target corpus of ₹50 Lakhs in 10 years?
At an assumed average return of **12% CAGR** from equity mutual funds, you need a monthly SIP of **₹21,520** for 10 years. Total personal investment will be ₹25,82,400, and compounding returns will add ₹24,17,600. If you use a **10% annual step-up**, your starting investment drops to just **₹13,275/month**.

### Should I include inflation in my target corpus?
Yes, absolutely. Inflation continuously reduces purchasing power over time. If a child's college education costs ₹25 Lakhs today, at 10% annual education inflation, the same degree will cost over **₹65 Lakhs in 10 years**. Always input the **future inflated cost** into the calculator rather than today's price tag.

### What rate of return should I assume for a 5-year target corpus?
For a 5-year horizon, assuming an aggressive 14%–15% return is dangerous because equity markets can experience multi-year consolidation phases. A prudent, conservative assumption for a 5-year timeline is **10% to 11% CAGR**, utilizing a balanced advantage or large-cap oriented mutual fund portfolio.

### How does the 2024 capital gains tax affect my target corpus?
Under Section 112A of the Income Tax Act (Finance No. 2 Act, 2024), long-term capital gains on equity mutual funds held for over 12 months are taxed at **12.5%** on gains exceeding **₹1,25,000 per financial year**. To ensure you receive your full target amount in hand after redemption, you should increase your target corpus by approximately 7% to 9% to cover future tax liabilities.

### What is the difference between a Target Corpus Calculator and a regular SIP Calculator?
A regular SIP calculator takes your monthly deposit and projects what it will grow to in the future (Forward Simulation). A Target Corpus Calculator takes your final financial goal and tells you how much you need to save each month to get there (Reverse Goal-Seeking).

### When should I move my accumulated money out of equity into safe debt funds?
You should initiate a **Systematic Transfer Plan (STP)** from equity mutual funds to liquid or ultra-short duration debt funds roughly **2 to 3 years before your goal's target date**. This de-risking glide path protects your accumulated wealth from sudden stock market drawdowns right before you need to withdraw the funds.

---

## Related Calculators & Planning Tools
* [1 Crore SIP Calculator](/my-first-crore-calculator) — Dedicated milestone planner for your first ₹1,00,00,000.
* [SIP Calculator](/sip-calculator) — Calculate returns and wealth accumulation for fixed monthly investments.
* [Step-Up SIP Calculator](/sip-step-up-calculator) — Model annual top-ups to accelerate your timeline to financial freedom.
* [SWP Calculator](/swp-calculator) — Plan sustainable monthly retirement withdrawals from your accumulated target corpus.
* [Inflation Calculator](/inflation-calculator) — Calculate the future cost of your goals based on Indian CPI inflation.
* [Retirement Calculator](/retirement-calculator) — Model your complete lifecycle from accumulation to retirement pension.
