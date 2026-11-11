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
- **Rule (Chromium GPU Compositor Stability Across `393px–1366px+`):**
  - Never apply `background-attachment: fixed` at ANY breakpoint (`body` in `input.css`, `styles.css`, `base.twig`, or `admin.twig`); always use `background-attachment: scroll`. Even `@media (min-width: 1024px)` fixed backgrounds cause Chromium's Skia rasterizer at `1024px` (`iPad Pro 13`) to drop `<canvas id="corpusChart">` when paired with card surfaces.
  - Never apply `backdrop-filter: blur(...)` (`backdrop-blur-*`) or `transition-all` to large scrollable cards (`#chart-card`, `#calculator-app`, `.fintech-glass-card`, component cards, sticky ledger cells `#results-table th/td:first-child` / `.table-sticky-col-0`, `.slider-floating-tooltip`), or to fixed HUD/navbar/dock bars (`bg-white` / `bg-white/98`); reserve `backdrop-blur-*` strictly for lightweight overlay modal/wizard backdrops (`dialog::backdrop`). Keep `background-color: rgba(255, 255, 255, 0.98)` inside `@utility fintech-glass-card` (`TypographyAndThemeTokensTest`), transform-only `.metric-pulse-active` (`input.css`), and pure light `bg-white/95` on `strategy-starter.twig` (`ArchetypeInjectorTest`). Keep `#breakdown-studio` solid opaque (`bg-white`, zero root `overflow-hidden` so `#export-share-dropdown` stays unclipped) while preserving `bg-white/95` in `yearly-breakdown-table.twig` (`YearlyBreakdownTableTest#L122`).
  - Keep `#mini-hud-anchor` at `top-20` (`80px`) to clear the `73px` navbar.
- **Rationale:** `items-start` + `self-start` eliminates Grid row stretch tails, and isolating Row 1 with `xl:contents` ensures CSS `position: sticky` on `md:col-span-7` uses Row 1 as its containing block rather than the full outer grid spanning `#breakdown-studio`. Child 2 tracks cleanly alongside Stage 2 SWP inputs on `768px–1279px` and unsticks with a constant 20px–32px gutter above opaque `#breakdown-studio` (`0px` overlap), then dissolves into desktop side-by-side layout (`xl:row-span-2`) at `>=1280px`. Eliminating `background-attachment: fixed` and large-card `backdrop-filter` prevents Chromium `<canvas>` and card layer drops at `800px–1366px`.

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
  - Gate Tier (Pre-Commit): Run `composer check-all` (814 tests, PHPStan Level Max, PHPCS) and `parity_check.php` before committing or finalizing.
