# System Patterns & Architectural Invariants

This file codifies core invariant patterns to ensure Antigravity maintains architectural consistency without re-investigating design conventions.

---

## 1. Pure Light-Mode Theme Invariant
- **Rule:** Under NO circumstances should dark surfaces, dark card backgrounds (`bg-slate-900`, `bg-gray-950`), or inverted widgets be used.
- **Card Styling:** `bg-white/95`, `bg-slate-50/80`, `border-slate-200/80`, `shadow-flat` or `shadow-card`.
- **Typography:** `text-slate-900`, `text-slate-700`, `text-slate-500`.
- **Accent Palettes:**
  - Growth / SIP: `emerald-600` / `emerald-500` / `emerald-50`
  - Withdrawal / SWP: `rose-600` / `rose-500` / `rose-50`
  - Specialized / Goals: `teal-600`, `indigo-600`, `amber-600`
- **Ambient Lighting:** Soft pastel aurora blur gradients (`emerald-400/12`, `teal-300/8`, `indigo-300/6`).

---

## 2. Calculator Computation Paradigm
- **Client-Side Zero Latency:** ALL slider interactions and numeric inputs MUST evaluate directly in TypeScript (`MathEngine.ts` / driver engines) in real time.
- **NEVER Call Backend on Input:** Do not introduce AJAX/fetch calls to PHP controllers during user input scrubbing or slider movement.
- **Cross-Runtime Parity:** Any financial math adjustment made to TypeScript must be mirrored in PHP (`src/Services/InvestmentCalculator.php`) and verified via `php tests/parity_check.php`.

---

## 3. Rendering & Bundling Pipeline
- **Router:** Native lightweight PHP router (`src/Core/Router.php`) called by `index.php`.
- **Templates:** Twig 3.x (`src/Views/`). In `calculator-guide.twig`, components are dynamically injected based on `calculator_type`.
- **Asset Bundling:** Vite (`vite.config.js`). Assets are referenced via Vite manifest helpers.
- **No Console Debug Output in Controllers:** Never use `echo`, `var_dump()`, or `print_r()` inside PHP Controllers or Actions as it breaks JSON/HTTP streams. Use `error_log()` instead.

---

## 4. Indian Financial Localization Standards
- **Currency Format:** INR (`₹`) using Indian numbering system (Lakhs, Crores — e.g. `₹1,00,000`, `₹10,00,000`, `₹1,00,00,000`).
- **Terminology:** SIP (Systematic Investment Plan), SWP (Systematic Withdrawal Plan), Step-up (Top-up), Lumpsum, CAGR, PPF, FD.
- **Audience:** Indian retail investors and FIRE community.

---

## 5. Documentation Synchronicity & Anti-Drift Contract
- **Atomic Doc Updates:** Code and its documentation are considered a single atomic unit. Any commit or PR that modifies routes, logic, formulas, or UI contracts must update referencing docs in the exact same step.
- **Audit Checklist on Changes:**
  1. `README.md`
  2. `.agents/ARCHITECTURE_MAP.md`
  3. `.agents/memory/activeContext.md` and `systemPatterns.md`
  4. Relevant `.agents/skills/*/SKILL.md` references
- **Zero Obsolete References:** Proactively remove references to renamed classes, deprecated parameters, or deleted templates. Never allow documentation to reference phantom or obsolete artifacts.
