# System Patterns & Architectural Invariants

This file codifies core invariant patterns to ensure Antigravity maintains architectural consistency without re-investigating design conventions.

---

## 1. Pure Light-Mode Theme Invariant & Intent Translation Matrix
- **Rule:** Under NO circumstances should dark surfaces, dark card backgrounds (`bg-slate-900`, `bg-gray-950`), or inverted widgets be used. Enforced automatically by `TelemetryIntegrationTest.php`.
- **Card Styling:** `bg-white/95`, `bg-slate-50/80`, `border-slate-200/80`, `shadow-flat` or `shadow-card`.
- **Typography:** `text-slate-900`, `text-slate-700`, `text-slate-500`.
- **Accent Palettes:**
  - Growth / SIP: `emerald-600` / `emerald-500` / `emerald-50`
  - Withdrawal / SWP: `rose-600` / `rose-500` / `rose-50`
  - Specialized / Goals: `teal-600`, `indigo-600`, `amber-600`
- **Ambient Lighting:** Soft pastel aurora blur gradients (`emerald-400/12`, `teal-300/8`, `indigo-300/6`).
- **Design Review Intent Translation Matrix:** When visual design critiques suggest dark surfaces, map them immediately:
  - *Dark navy/slate card (#0F172A)* $\rightarrow$ **Executive Light-Mode Anchor:** `bg-emerald-50/90 border-2 border-emerald-600 shadow-card ring-2 ring-emerald-500/15` with `text-slate-900` typography and solid emerald pill badge.
  - *Dark drop shadows / ambient vignette* $\rightarrow$ **Crisp Subtle Elevation:** `shadow-card border-slate-200/90` with soft aurora glow (`emerald-400/12`, `teal-300/8`).
  - *Dark hero header* $\rightarrow$ **Crisp Light FinTech Console:** `bg-white/95` with emerald/teal underline tabs.

---

## 1.1 Asymmetric Dashboard Layout Primitive
- **Rule:** In two-column dashboard layouts with asymmetric heights (control console on left, interactive chart & long ledger on right), the control column MUST use:
  ```html
  <div class="col-span-12 md:col-span-6 lg:col-span-5 xl:col-span-4 flex flex-col min-h-0 self-start md:sticky md:top-20">
  ```
- **Rationale:** CSS Grid items stretch by default (`align-items: stretch`). Adding `self-start` prevents empty white tails when left sidebar stages collapse. Adding `md:sticky md:top-20` keeps controls accessible while scrolling through deep table ledgers.

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
- **Currency Format:** INR (`₹`) using Indian numbering system (Lakhs, Crores — e.g. `₹1,00,00,000`, `₹10,00,000`, `₹1,00,000`).
- **Terminology:** SIP (Systematic Investment Plan), SWP (Systematic Withdrawal Plan), Step-up (Top-up), Lumpsum, CAGR, PPF, FD.
- **Audience:** Indian retail investors and FIRE community.

---

## 5. Architectural Planning, Zero-Guesswork & Implementation Protocol
- **Multi-Angle & Minute-Detail Planning:** All features, bugfixes, and refactors require generating a deeply technical `implementation_plan.md` artifact before writing code. The plan must evaluate performance & Core Web Vitals, software engineering standards (SOLID, DRY, POLA), security, accessibility, SEO & schemas, and SSR-client parity down to exact CSS tokens, DOM IDs, and mathematical formulas.
- **Zero-Guesswork & Human Admin Clarification:** Never extrapolate, assume, or guess on the fly. If any ambiguity or trade-off arises at the planning stage, immediately pause and approach the human admin for complete clarification. During actual code execution, there must be ZERO ambiguity.
- **Deep Technical Rigor:** Implementation plans must be exhaustive, detailing precise file paths, line numbers, function signatures, data contracts, failure modes, and deterministic CLI verification steps (`composer check-all`, localized tests, curl verifications).

---

## 5. Documentation Synchronicity & Anti-Drift Contract
- **Atomic Doc Updates:** Code and its documentation are considered a single atomic unit. Any commit or PR that modifies routes, logic, formulas, or UI contracts must update referencing docs in the exact same step.
- **Audit Checklist on Changes:**
  1. `README.md`
  2. `.agents/ARCHITECTURE_MAP.md`
  3. `.agents/memory/activeContext.md` and `systemPatterns.md`
  4. Relevant `.agents/skills/*/SKILL.md` references
- **Zero Obsolete References:** Proactively remove references to renamed classes, deprecated parameters, or deleted templates. Never allow documentation to reference phantom or obsolete artifacts.

---

## 6. Vendor Investigation & Tiered Verification Invariants
- **Zero-Rabbit-Hole Rule:** Never perform iterative text greps or ad-hoc character parsing on minified production bundles (`dist/assets/*.js`).
- **Unminified Source Debugging:** When debugging vendor crashes (Chart.js, plugins), immediately inspect unminified source code in `node_modules/` or build an isolated minimal Node.js test fixture.
- **Hypothesis Transparency:** Clearly state the bug hypothesis, target vendor file, and next step to the user before running deep inspection commands.
- **Tiered Verification:**
  - Fast Tier (UI/Visual): Run `npm run build` and single browser check to verify visual rendering and 0 console errors.
  - Gate Tier (Pre-Commit): Run `composer check-all` (839 tests, PHPStan L5, PHPCS) and `parity_check.php` before committing or finalizing.
