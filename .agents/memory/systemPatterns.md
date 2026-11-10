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

## 1.1 Asymmetric Dashboard Layout & Responsive GPU Compositing Primitives
- **Rule (Home 3-Child Grid Workspace):** On `home.twig`, use a uniform `5 / 7` column split from `md:` (`768px+`) with explicit desktop row distribution (`xl:grid-rows-[auto_1fr]`), `items-start`, `min-w-0` shrink guards, and responsive Studio placement:
  ```html
  <div class="grid grid-cols-1 md:grid-cols-12 xl:grid-rows-[auto_1fr] items-start gap-5 lg:gap-8 mt-4 sm:mt-6">
      <div class="md:col-span-5 xl:row-span-2 flex flex-col min-h-0 min-w-0 self-start h-fit xl:sticky xl:top-24">...</div>
      <div class="md:col-span-7 space-y-4 min-h-0 min-w-0">...</div>
      <div class="md:col-span-12 xl:col-span-7 xl:col-start-6 min-w-0">...</div>
  </div>
  ```
- **Rule (Sub-Calculator Guide Grid):** On `calculator-guide.twig`, use matching `items-start`, `md:col-span-5` (left), `md:col-span-7` (right), `min-w-0`, and desktop-only sticky (`xl:sticky xl:top-24`).
- **Rule (KPI Card Responsive Matrix — Twig & TS Sync):** `#summary-cards-grid` MUST stay 2-up across `320px–1279px` and only expand to 4-up/3-up at `xl:` (`>=1280px`):
  - 4-Card Modes (`combo`, `swp`, `emi`): `grid grid-cols-2 xl:grid-cols-4` (`#card-corpus` = `col-span-1`).
  - 3-Card Modes (`sip`, `lumpsum`, `target_corpus`, specialized): `grid grid-cols-2 xl:grid-cols-3` (`#card-corpus` = `col-span-2 xl:col-span-1`).
  - Both SSR (`chart-visualization.twig`) and TypeScript (`SummaryMetricsController.ts`, `EmiDriver.ts`, `SpecializedCalculatorController.ts`) MUST declare identical breakpoint tokens.
- **Rule (Chromium Tablet/Foldable GPU Compositor Stability):**
  - Never apply `background-attachment: fixed` on `<1024px` (either CSS or inline `<body>` style in `base.twig`); scope it strictly inside `@media (min-width: 1024px)`.
  - Never apply `backdrop-filter: blur(...)` (`backdrop-blur-*`) or infinite animated gradients to dozens of sticky table cells (`#results-table th/td:first-child`, `.table-sticky-col-0`, `ResultsController.ts`) or `.text-gradient`; use solid opaque `#ffffff`/`#f8fafc` sticky cell surfaces (`background-clip: padding-box`) and solid `#047857` fallback inside `@supports`.
  - Keep `#mini-hud-anchor` at `top-20` (`80px`) to clear the `73px` navbar (`bg-white/95`).
- **Rationale:** `items-start` + `self-start` eliminates Grid row stretch tails; scoping sticky sidebars to `xl:` (`>=1280px`) prevents tall Dual-mode inputs from clipping or floating mid-scroll on `768px–1279px` tablets; `md:col-span-12` gives the 4–11 column Cashflow Ledger full tablet width while `xl:grid-rows-[auto_1fr]` prevents intra-column vertical gaps on desktop.

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
