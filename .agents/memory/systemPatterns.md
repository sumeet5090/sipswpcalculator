# System Patterns & Architectural Invariants

This file codifies core invariant patterns to ensure Antigravity and developers maintain architectural consistency without re-investigating design conventions.

---

## 1. Pure Light-Mode Theme Invariant & Intent Translation Matrix
- **Rule:** Under NO circumstances should dark surfaces, dark card backgrounds (`bg-slate-900`, `bg-gray-950`), or inverted widgets be used. Enforced automatically by `TelemetryIntegrationTest.php`.
- **Card Styling:** `bg-white/95`, `bg-slate-50/80`, `border-slate-200/80`, `shadow-flat` or `shadow-card`.
- **Typography:** `text-slate-900`, `text-slate-700`, `text-slate-500`.
- **Accent Palettes (Disciplined Dual-Engine):**
  - **Stage 1 (SIP / Wealth Accumulation):** Institutional Emerald (`emerald-600` / `emerald-700`, `emerald-50/90`, `text-emerald-900`).
  - **Stage Transition Bridge:** Multi-stop gradient connector (`from-emerald-600 via-teal-600 to-amber-600`).
  - **Stage 2 (SWP / Retirement Cashflow & Payouts):** Warm Amber & Golden Harvest (`amber-600` / `amber-700`, `amber-50/90`, `text-amber-900`). **Generic blue is completely eliminated.**
  - **Sovereign Instruments (Fixed Deposit):** Sovereign Indigo (`indigo-600` / `indigo-700`, `text-indigo-950`).
  - **Specialized & Strategic Goals:** `teal-600`, `amber-500`.
- **Ambient Lighting:** Soft pastel aurora blur gradients (`emerald-500/12`, `amber-500/12`, `teal-400/6`).
- **Design Review Intent Translation Matrix:** When visual design critiques suggest dark surfaces, map them immediately:
  - *Dark navy/slate card (#0F172A)* $\rightarrow$ **Executive Light-Mode Anchor:** `bg-emerald-50/90 border-2 border-emerald-600 shadow-card ring-2 ring-emerald-500/15` with `text-slate-900` typography and solid emerald pill badge.
  - *Dark drop shadows / ambient vignette* $\rightarrow$ **Crisp Subtle Elevation:** `shadow-card border-slate-200/90` with soft aurora glow (`emerald-500/12`, `amber-500/12`).
  - *Dark hero header* $\rightarrow$ **Luminous Architectural Daylight Canopy:** `from-slate-50 via-white to-slate-50/60 text-slate-900 border-b border-slate-200/90 shadow-subtle` with soft ambient emerald aura (`bg-emerald-500/8 blur-3xl`), commanding dark slate-900 typography, and solid emerald conversion CTA (`bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold`).

---

## 1.1 Asymmetric Dashboard Layout & Responsive GPU Compositing Primitives
- **Rule (Home 3-Child Grid Workspace & Row-1 `xl:contents` Sticky Boundary):** On `home.twig`, wrap Child 1 (`md:col-span-5`) and Child 2 (`md:col-span-7`) inside an `xl:contents` Row-1 grid wrapper so tablet `md:sticky md:top-24` on Child 2 (`768px–1279px`) is bounded strictly to Row 1 height (`height(Child 1)`) and unsticks cleanly before Child 3 `#breakdown-studio` (`analytical-studio.twig`, solid opaque `bg-white`), while `xl:contents` dissolves the wrapper at `>=1280px` into the 12-column desktop layout:
  ```html
  <div class="xl:grid xl:grid-cols-12 xl:grid-rows-[auto_1fr] xl:items-start xl:gap-8 mt-4 sm:mt-6">
      <div class="grid grid-cols-1 md:grid-cols-12 items-start gap-5 lg:gap-8 mb-5 lg:mb-8 xl:mb-0 xl:contents">
          <div class="md:col-span-5 xl:row-span-2 flex flex-col min-h-0 min-w-0 self-start h-fit xl:sticky xl:top-24">...</div>
          <div class="md:col-span-7 space-y-4 min-h-0 min-w-0 self-start md:sticky md:top-24 xl:static">...</div>
      </div>
      <div class="xl:col-span-7 xl:col-start-6 min-w-0">...</div>
  </div>
  ```
- **Rule (Sub-Calculator Guide Grid):** On `calculator-guide.twig`, use matching `items-start`, `md:col-span-5` (left), `md:col-span-7` (right), `min-w-0`, and desktop-only sticky (`xl:sticky xl:top-24`).
- **Rule (Monotonic Chart Canvas Heights):** Keep `#chart-canvas-container` (`chart-visualization.twig`) on monotonic landscape height steps (`h-[250px] sm:h-[290px] md:h-[260px] lg:h-[320px] xl:h-[395px]`) with `maintainAspectRatio: false` (`ChartManager.ts`) and compact `< xl` card padding (`transition-shadow`) so narrower tablet/foldable columns never exhibit square vertical letterboxing.
- **Rule (KPI Card Responsive Matrix — Twig & TS Sync):** `#summary-cards-grid` MUST stay 2-up across `320px–1279px` and only expand to 4-up/3-up at `xl:` (`>=1280px`):
  - 4-Card Modes (`combo`, `swp`, `emi`): `grid grid-cols-2 xl:grid-cols-4` (`#card-corpus` = `col-span-1`).
  - 3-Card Modes (`sip`, `lumpsum`, `target_corpus`, specialized): `grid grid-cols-2 xl:grid-cols-3` (`#card-corpus` = `col-span-2 xl:col-span-1`).
  - Both SSR (`chart-visualization.twig`) and TypeScript (`SummaryMetricsController.ts`, `EmiDriver.ts`, `SpecializedCalculatorController.ts`) MUST declare identical breakpoint tokens.
- **Rule (Chromium GPU Compositor Stability & Glassmorphism Standards):**
  - Never apply `background-attachment: fixed` at ANY breakpoint (`body` in `input.css`, `styles.css`, `base.twig`, or `admin.twig`); always use `background-attachment: scroll`. Even `@media (min-width: 1024px)` fixed backgrounds cause Chromium's Skia rasterizer at `1024px` (`iPad Pro 13`) to drop `<canvas id="corpusChart">` when paired with card surfaces.
  - Authentic frosted glassmorphism is implemented via `@utility fintech-glass-card` (`backdrop-blur-md`, `bg-white/85` to `bg-white/95`, `border-slate-200/90`, inner specular highlights `box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.9)`), with `backdrop-blur-xl` on modal backdrops (`#wealth-guided-wizard-drawer`).
  - Keep `.metric-pulse-active` GPU transform-only (`input.css`), immediate `.stagger-row` `opacity: 1`, `z-behind` glow blobs behind `z-10` headers, `@supports`-guarded `.text-gradient`, and `#mini-hud-anchor` at `top-20` (`80px`) to clear the `73px` navbar.
- **Rationale:** `items-start` + `self-start` eliminates Grid row stretch tails, and isolating Row 1 with `xl:contents` ensures CSS `position: sticky` on `md:col-span-7` uses Row 1 as its containing block rather than the full outer grid spanning `#breakdown-studio`. Child 2 tracks cleanly alongside Stage 2 SWP inputs on `768px–1279px` and unsticks with a constant 20px–32px gutter above opaque `#breakdown-studio` (`0px` overlap), then dissolves into desktop side-by-side layout (`xl:row-span-2`) at `>=1280px`. Eliminating `background-attachment: fixed` prevents Chromium `<canvas>` and card layer drops at `800px–1366px`.

---

## 1.2 Slot 1B Progressive Seed Capital Disclosure & Unified Studio (`in_studio`)
- **Rule (`Expanded = Active, Collapsed = ₹0` Invariant):** `Initial Lumpsum` lives in Slot 1B directly beneath `#sip_container` (`Monthly SIP`) above `Period (Yrs)` (`sip-fields.twig`, `lumpsum-fields.twig`, `LumpsumDisclosureController.ts`). Use WAI-ARIA `<button type="button" id="lumpsum-toggle-btn" aria-expanded aria-controls="lumpsum-drawer">` + CSS `hidden` drawer `#lumpsum-drawer` + `#lumpsum-remove-btn` via `footer_action_html` (`input-range-pair.twig`), with zero `<details>`/`<summary>` tags (`FormStructureParityTest`) and isolated `group/field` hover scopes.
- **Rule (Route & Embed Scoping):** `GuideViewModelBuilder::build()` sets `show_lumpsum = true` for `['lumpsum', 'sip', 'sip-step-up', 'combo', 'all']`, unlocking optional seed capital on `/`, `/sip-calculator`, `/sip-step-up-calculator`, and `/retirement-calculator`. `GuideRenderer::renderEmbed()` locks `show_lumpsum = ($calculator_type === 'lumpsum')` so `/embed/*` widgets stay compact, and `/lumpsum-calculator` uses `lumpsum-only-fields.twig`.
- **Rule (Honeypot-Safe Keyboard Traversal):** `KeyboardViewportController.ts` excludes `#website_url`, `name="website_url"`, `tabIndex < 0`, `[aria-hidden="true"]`, `hidden`, and `display: none` inputs from both listener binding and `getVisibleInputs()`, falling back to `document.activeElement` in `navigateInput()`.
- **Rule (`in_studio` Studio/Ledger Fusion):** `analytical-studio.twig` includes `yearly-breakdown-table.twig` with `{'in_studio': true}` inside `#panel-yearly-breakdown`, fusing both containers into one solid white `rounded-3xl` workbench (`#breakdown-studio`), omitting duplicate `"Yearly Cashflow Ledger"` headers and mobile Tier 1 controls (`hidden sm:flex`), while preserving standalone rendering (`calculator-guide.twig`), `#yearly-breakdown` scroll anchor, and test-locked DOM IDs.

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

### 3.1 CSS Cascade Layers, Range Slider SSoT & `.prose` Isolation
- **Cascade Discipline (`input.css` & `styles.css`):** Since `resources/js/app.ts` imports CSS after `@import "tailwindcss";`, ALL document/element rules MUST live in `@layer base` (using `:where(...)` for `input`, `select`, `textarea`, `label`, `:focus-visible`) and ALL custom selectors MUST live in `@layer components`. Only `@theme`, `@source`, `@utility`, `@keyframes`, and top-level `:root` tokens sit outside `@layer`. Never inject unlayered `!important` utility polyfills or re-introduce dead bare `.glass-card` / `CardSpotlightController`.
- **Range Slider SSoT (`resources/css/input.css` `@layer components`):** Range track and thumb styles live solely in `input.css` (`6px` track + `18px` thumb `margin-top: -6px` on desktop; `8px` track + `22px` thumb `margin-top: -7px` on `<640px` mobile; transparent `::-webkit-slider-runnable-track` & `::-moz-range-track` preserving `--range-progress`). Never attach `h-2` or `bg-slate-200` utility classes to `<input type="range">` in Twig (`input-range-pair.twig`, `#mobile-chart-scrubber` in `chart-visualization.twig`), as `@layer utilities` overrides `height: 6px`.
- **Editorial `.prose` Isolation (`styles.css`):** Wrap `.prose` element rules in `:where(.prose) :where(<tag>):not(:where(.not-prose, .not-prose *))` (`0,0,0` specificity) and scope Markdown pipe tables to `table:not([class])` with zero `!important`. Always place `.not-prose` on embedded UI blocks inside `<article class="prose">` (`generic-post.twig` header, `interactive-cta-banner.twig`, `related-resources.twig`).
- **Authentic Contrast Tokens:** Use `text-slate-500` (`#64748b`, WCAG AA `>=4.7:1` on `#ffffff`) for readable captions, unit affixes, subtitles, and ScrollSpy/TS muted states; reserve `text-slate-400` (`#94a3b8`) strictly for decorative SVGs and `•`/`→`/`▼` glyphs. Keep SSR and TS class lists synchronized (`PurchasingPowerController.ts` ↔ `chart-visualization.twig`, `ChartManager.ts` ↔ `chart-visualization.twig`).

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

## 6. Documentation Synchronicity & Anti-Drift Contract
- **Atomic Doc Updates:** Code and its documentation are considered a single atomic unit. Any commit or PR that modifies routes, logic, formulas, or UI contracts must update referencing docs in the exact same step.
- **Audit Checklist on Changes:**
  1. `README.md`
  2. `.agents/ARCHITECTURE_MAP.md`
  3. `.agents/memory/activeContext.md` and `systemPatterns.md`
  4. Relevant `.agents/skills/*/SKILL.md` references
- **Zero Obsolete References:** Proactively remove references to renamed classes, deprecated parameters, or deleted templates. Never allow documentation to reference phantom or obsolete artifacts.

---

## 7. Vendor Investigation & Tiered Verification Invariants
- **Zero-Rabbit-Hole Rule:** Never perform iterative text greps or ad-hoc character parsing on minified production bundles (`dist/assets/*.js`).
- **Unminified Source Debugging:** When debugging vendor crashes (Chart.js, plugins), immediately inspect unminified source code in `node_modules/` or build an isolated minimal Node.js test fixture.
- **Hypothesis Transparency:** Clearly state the bug hypothesis, target vendor file, and next step to the user before running deep inspection commands.
- **Tiered Verification Pipeline:**
  - *Tier 1 (Fast-Path Local Build & Parity):* Run `npm run typecheck && npm run build` and `php tests/parity_check.php` (verifying 119/119 parity tests).
  - *Tier 2 (Integration & Local Curl):* Run `vendor/bin/phpunit tests/Integration` (dynamic ephemeral local ports) and curl checks against the local server.
  - *Tier 3 (Gate Tier):* Run `composer check-all` (957 tests [743 Unit + 214 Integration], 18,722 assertions, PHPStan Level 8 across 285 files, PSR-12 clean) before concluding and handing off to the human developer.

## 9. Registry SSoT, Table Schema & Static Analysis Governance
- **Calculator Registry SSoT (`CalculatorRegistry`):** `content/calculator_registry.json` is the single declarative registry for all 19 calculator modes and 34 routes, consumed by `Core\Config\CalculatorRegistry` (`routes.php`, `StrategyFactory`, `DomainServiceProvider`, `AppTwigExtension`) and `assets/js/calculators/constants/CalculatorRegistry.ts` (`CalculatorApp`, `QrShareModalController`, `ExcelExportController`, `MobileErgonomicDeckController`, `SummaryMetricsController`). Twig exposes `calculator_route_count()`, `specialized_calculator_count()`, and `is_calculator_route(active_page)` directly from `CalculatorRegistry`.
- **Table & Excel Multi-Mode Schema (`ModeTableSchema`):** `assets/js/calculators/constants/ModeTableSchema.ts` centralizes desktop/mobile headers, mobile card labels, multiplier denominators, interest polarity styles, and 10-column Excel sheet metadata across core and all 12 specialized modes.
- **Theme Token SSoT & Zero-Blue Discipline:** `assets/js/calculators/constants/ThemeTokens.ts` imports `content/theme_tokens.json` with `with { type: 'json' }` (compatible across `tsc`, Vite 8, and Node 24 ESM `--experimental-strip-types`), guarded by `ThemeTokenParityTest.php` and zero-blue `DesignTokenAuditTest.php`.
- **Canonical CLI Migrator & Ephemeral Test Ports:** `bin/migrate` is the sole database migration CLI entrypoint (executed in `.github/workflows/deploy.yml`). `IntegrationTestCase::findAvailablePort()` dynamically binds an ephemeral loopback port (`tcp://127.0.0.1:0`) per integration test class.

---

## 8. Git Commit & Branch Governance (Human-Only Invariant)
- **Zero AI Commits & Merges:** Under NO circumstances should AI agents execute `git commit`, `git merge`, or `git push` on any branch.
- **Human Developer Exclusivity:** The human administrator will inspect, stage, and commit changes on any branch regularly and merge branches to target branches as needed.
- **Agent Scope Boundary:** The AI agent's task lifecycle strictly concludes at Tiered Verification and Anti-Drift Documentation. Working tree changes must remain uncommitted for the human developer.
