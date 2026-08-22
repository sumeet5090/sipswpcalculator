---
name: mathematical-precision-guardrails
description: Comprehensive encyclopedia of 165 mathematical engine, simulation, financial precision hazards, compounding nuances, and PHP/TypeScript cross-runtime parity guardrails.
---

# Mathematical Precision, Simulation & Calculation Parity Guardrails

This skill serves as the definitive reference manual for all mathematical, financial, simulation, and cross-runtime parity engineering across the SIP and SWP calculation stack.

---

## 1. Core Compounding & Time-Value Rules

1. **End-of-Period Timing:** Contribution is added *before* monthly compounding is calculated: $B_m = (B_{m-1} + C_m - W_m) \times (1 + r/12)$. Ordinary annuity lag must never be introduced.
2. **Nominal Rate Division:** Monthly rate is strictly $r / 100 / 12$ (Indian mutual fund standard), never geometric $(1+r)^{1/12} - 1$.
3. **Year 0 Singularity:** Zero-horizon requests (`years <= 0`) must return a single-row structured vector (`year: 0`, `combined_total: lumpsum`), never an empty array `[]`.
4. **Zero-Rate Linear Fast-Path:** When return and step-up are 0, final corpus is strictly linear: $\text{Lumpsum} + (\text{SIP} \times 12 \times \text{Years})$.
5. **Delta Reconciliation for Interest:** Annual interest is $B_{\text{end}} - (B_{\text{begin}} + \text{AnnualContrib} - \text{AnnualWithdrawn})$. Never sum truncated monthly floats.
6. **Opening Balance Snapshot:** `$yearBegin = $netBalance` must be captured before the 12-month loop begins.
7. **Compounding Non-Negative Clamp:** If `$netBalance < 0`, reset to `0.0` immediately to prevent negative debt compounding.
8. **Annual Step-Up Frequency:** Step-up occurs strictly at Year $y+1$, not monthly.
9. **Cumulative Invested Cost Basis:** Freezes during SWP retirement years; never decrement cost basis when withdrawals occur.
10. **Phase Boundary Transition:** Rate switches from accumulation `rate` to retirement `swpRate` at year $\text{sipYears} + 1$.

---

## 2. PHP 8 vs. V8 TypeScript Cross-Runtime Parity

1. **`Math.round` & `Number.EPSILON`:** In TypeScript, always use `Math.round((val + Number.EPSILON) * 100) / 100` to prevent binary float truncation drift (e.g. `2.55 * 100 = 254.99999999999997`).
2. **Strict Nullness Contract:** Accumulation years must return `swp_monthly: null` and `annual_withdrawal: null`. SWP years must return `sip_monthly: null`. Never return `0` or `0.0` in place of `null`.
3. **Dynamic Assertion Tolerance:** In large portfolios ($₹10^{14}$), use `max(0.05, abs(val * 0.00001))` for float parity checks.
4. **Shell Argument Escaping:** Always wrap JSON payloads in `escapeshellarg()` when calling Node.js from PHP tests.
5. **Dynamic Solver Action Dispatching:** Centralize helper tests in `tests/run_js_calc.js` using the `action` property.

---

## 3. Step-Up (Top-Up) SIP Mathematics

1. **Zero-Indexed Exponent:** $\text{SIP}_y = \text{SIP}_1 \times (1 + g)^{y-1}$. Using exponent $y$ erroneously steps up in Year 1.
2. **SWP Escalation Exponent:** $\text{SWP}_y = \text{SWP}_{\text{start}} \times (1 + g_{\text{swp}})^{y - \text{swpStartYear}}$.
3. **50% Slider Max Bound:** Hard-cap step-up sliders at 50% p.a. over 50 years to prevent numeric overflow beyond `Number.MAX_SAFE_INTEGER`.
4. **Crossover Point Logic:** First year where `annual_contribution > 0 && interest > annual_contribution`. Hidden in pure lumpsum mode.
5. **2-Decimal Contribution Rounding:** Round monthly step-up contributions to 2 decimals before monthly compounding.

---

## 4. SWP Depletion & Longevity Dynamics

1. **Final Month Partial Withdrawal:** $\text{withdraw} = \min(\text{monthlySwp}, \text{potentialBalance})$. Balance becomes exactly `0.00`.
2. **Cumulative Withdrawals Freezing:** Cumulative sum freezes at peak accumulated disbursement once corpus depletes.
3. **SWR Benchmark Rule:** Safe Withdrawal Rate is benchmarked against retirement starting corpus (`data[years - 1].combined_total` or `lumpsum`), never against terminal depleted corpus.
4. **Depletion Rejection in Inversion Solvers:** Reject candidate starting corpus if intermediate balance drops to 0 before the horizon ends.
5. **Reactive Toggle Dispatch:** Always dispatch `change` events when programmatically setting `enable_swp.checked = true`.

---

## 5. Capital Gains Taxation (Budget 2024 Section 112A)

1. **Statutory Limits:** Exemption threshold = **₹1,25,000**, Tax rate = **12.5%**.
2. **Nominal Gains Basis:** Compute tax on nominal pre-tax gains: $\max(0, \text{PreTaxGains} - 125000) \times 0.125$.
3. **Evaluation Order:** Nominal tax is deducted *before* inflation discounting is applied to the remaining net balance.
4. **Zero Tax Liability:** If profit $< ₹1,25,000$, tax is strictly `0.0`.
5. **Dynamic Exemption Resolution:** View models must resolve `inputs.ltcg_exemption ?? 125000` rather than hardcoded literals.

---

## 6. Inflation Discounting & Purchasing Power

1. **Geometric Discounting:** $\text{RealCorpus} = \text{NominalCorpus} / (1 + \text{Inflation}/100)^{\text{Years}}$.
2. **Non-Positive Guard:** Return `max(0, finalCorpus)` if inflation $\le 0$ or years $\le 0$.
3. **Table vs. Summary Scope:** Table rows discount by `row.year`; top summary cards discount by `totalYears`.
4. **Simultaneous Real Gains & Real Corpus:** Both corpus and gains must be discounted symmetrically to preserve accounting identity.

---

## 7. Goal-Seeking Binary Search Solvers

1. **40-Iteration Bound:** Fixed 40 iterations guarantee exact convergence in $< 5\text{ms}$ with zero risk of infinite loops.
2. **Lumpsum Sufficiency Guard:** Return `0.0` required SIP immediately if initial lumpsum alone achieves target.
3. **Linear Fast Path:** When rate and step-up are 0, use closed-form division: $(\text{Target} - \text{Lumpsum}) / (\text{Years} \times 12)$.
4. **Dynamic Range Slider Max:** Expand HTML slider `max` if computed required corpus exceeds default 5 Crore limit.
5. **Mode Inversion State Machine:** Typing into a solved input field automatically switches mode back to standard forward calculation (`grow`).

---

## 8. Financial Formatting & Internationalization

1. **Indian 2,2,3 Grouping:** Format numbers as `10,00,000` (10 Lakh) and `1,00,00,000` (1 Crore).
2. **Negative Sign Placement:** Formatter must output `-₹50,000`, never `₹-50,000`.
3. **Zero and Negative Subtext:** Suppress contextual annualized subtexts on zero or negative values.
4. **UTF-8 Byte Order Mark:** Prepend `\xEF\xBB\xBF` to CSV streams to ensure Microsoft Excel renders `₹` correctly.
5. **CSV Formula Injection Defense:** Prefix dangerous leading characters (`=`, `+`, `-`, `@`, `\t`) with `'` while preserving valid negative numbers.

---

## 9. Animation Physics & UI Performance

1. **Quartic Easing Out:** Progress curve $1 - (1 - t)^4$ for natural deceleration.
2. **In-Flight Cancellation:** Cancel running `requestAnimationFrame` IDs and inherit current in-flight value as starting point.
3. **Layout Thrashing Separation:** Batch DOM reads (Query Phase) and DOM writes (Command Phase) separately.
4. **Mobile Keyboard Resize Guard:** Ignore resize events where window width remains unchanged.
5. **Canvas Image Export:** Draw charts to white-backed offscreen canvas before Base64 PNG export for Dompdf.
