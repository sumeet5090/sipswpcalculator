# Architecture Coordinate Map (Zero-Latency File Index)

This document is the authoritative coordinate map and architectural cheat-sheet for the `sipswpcalculator` codebase.
**Rule for Agents:** Consult this document first before performing exploratory `grep_search` or `list_dir`.

---

## 1. Calculator Matrix (Route -> Logic -> View)

| Route Slug | PHP Action | Twig Layout & Form Partial | TS Strategy / Driver | Engine / Math Parity | Defaults Key (`calculator_defaults.json`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/` (Home) | `Controllers\RenderHomeAction` | `calculators/home.twig`<br/>`components/calculator-form.twig`<br/>`forms/sip-fields.twig`<br/>`forms/lumpsum-fields.twig`<br/>`forms/swp-fields.twig` | `CalculatorApp.ts`<br/>`controllers/TabController.ts`<br/>`controllers/LumpsumDisclosureController.ts`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | Dual default (`combo`: `sip`, `years`, `rate`, `stepup`, `inflation`, `lumpsum`, `swp_withdrawal`, `swp_years`, `swp_rate`, `swp_stepup`) |
| `/sip-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig`<br/>`forms/lumpsum-fields.twig` | `Core\Strategies\SipStrategy`<br/>`controllers/LumpsumDisclosureController.ts`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `sip`, `years`, `rate`, `stepup`, `lumpsum` (Slot 1B optional) |
| `/swp-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/corpus-field.twig`<br/>`forms/swp-fields.twig` | `Core\Strategies\SwpStrategy`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `corpus`, `swp_withdrawal`, `years`, `rate`, `inflation` |
| `/sip-step-up-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig`<br/>`forms/lumpsum-fields.twig` | `Core\Strategies\StepUpSipStrategy`<br/>`controllers/LumpsumDisclosureController.ts`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `sip`, `years`, `rate`, `stepup`, `lumpsum` (Slot 1B optional) |
| `/lumpsum-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/lumpsum-only-fields.twig` | `Core\Strategies\LumpsumStrategy`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `lumpsum`, `years`, `rate` |
| `/retirement-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig`<br/>`forms/lumpsum-fields.twig`<br/>`forms/swp-fields.twig` | `Core\Strategies\ComboStrategy`<br/>`controllers/LumpsumDisclosureController.ts`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | Combo defaults (`lumpsum` Slot 1B optional) |
| `/my-first-crore-calculator`<br/>`/target-corpus-calculator`<br/>`/reach-5-crore-via-sip` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/target-corpus-fields.twig` | `Core\Strategies\TargetCorpusStrategy`<br/>`strategies/TargetCorpusStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `target_corpus`, `years`, `rate`, `stepup` |
| `/compound-interest-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/compound-interest-fields.twig` | `Core\Strategies\CompoundInterestStrategy`<br/>`drivers/CompoundInterestDriver.ts` | `engines/CompoundInterestEngine.ts` | `ci_*` |
| `/cagr-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/cagr-fields.twig` | `Core\Strategies\CagrStrategy`<br/>`drivers/CagrDriver.ts` | `engines/CagrEngine.ts` | `cagr_*` |
| `/emi-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/emi-fields.twig` | `Core\Strategies\EmiStrategy`<br/>`drivers/EmiDriver.ts` | `engines/EmiEngine.ts` | `emi_*` |
| `/inflation-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/inflation-fields.twig` | `Core\Strategies\InflationStrategy`<br/>`drivers/InflationDriver.ts` | `engines/InflationEngine.ts` | `inflation_*` |
| `/ppf-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/ppf-fields.twig` | `Core\Strategies\PpfStrategy`<br/>`drivers/PpfDriver.ts` | `engines/PpfEngine.ts` | `ppf_*` |
| `/fd-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/fd-fields.twig` | `Core\Strategies\FdStrategy`<br/>`drivers/FdDriver.ts` | `engines/FdEngine.ts` | `fd_*` |
| `/gratuity-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/gratuity-fields.twig` | `Core\Strategies\GratuityStrategy`<br/>`drivers/GratuityDriver.ts` | `engines/GratuityEngine.ts`<br/>`Core\Math\GratuityEngine.php` | `gratuity_*` |
| `/nps-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/nps-fields.twig` | `Core\Strategies\NpsStrategy`<br/>`drivers/NpsDriver.ts` | `engines/NpsEngine.ts`<br/>`Core\Math\NpsEngine.php` | `nps_*` |
| `/post-retirement-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/post-retirement-fields.twig` | `Core\Strategies\PostRetirementStrategy`<br/>`drivers/PostRetirementDriver.ts` | `engines/PostRetirementEngine.ts`<br/>`Core\Math\PostRetirementEngine.php` | `post_ret_*` |
| `/epf-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/epf-fields.twig` | `Core\Strategies\EpfStrategy`<br/>`drivers/EpfDriver.ts` | `engines/EpfEngine.ts`<br/>`Core\Math\EpfEngine.php` | `epf_*` |
| `/embed/{slug}` | `Controllers\RenderEmbedAction` | `calculators/embed.twig` | `CalculatorApp.ts` | `MathEngine.ts` | Embedded config |

---

## 2. Interactive Modals & Studio Subsystems Matrix

| Feature / Widget | Twig Template (`src/Views/components/`) | Orchestrating TS Controller / Subsystem | Key Purpose & Capabilities |
| :--- | :--- | :--- | :--- |
| **City FIRE Benchmark** | `city-fire-benchmark.twig` | `subsystems/EngagementSubsystem.ts` | Indian tier-1/tier-2/tier-3 city geo-arbitrage, housing tenure toggles, SWR multipliers (25x-40x) |
| **Stress Test Simulator** | `stress-test-simulator.twig` | `subsystems/LifecycleSubsystem.ts` | Historical crash modeling (2008 GFC, 2020 Covid, 2013 Taper Tantrum) with recovery projections |
| **Tax Waterfall Modal** | `tax-waterfall-modal.twig` | `subsystems/LifecycleSubsystem.ts` | Union Budget 2024 equity LTCG (12.5% above ₹1.25L) & STCG (20%) tax net return impact |
| **Asset Rebalancing** | `asset-rebalancing.twig` | `subsystems/LifecycleSubsystem.ts` | Equity/Debt drift rebalancing optimizer and trigger alerts |
| **Yearly Breakdown Table** | `yearly-breakdown-table.twig`<br/>`analytical-studio.twig` | `controllers/ResultsController.ts`<br/>`controllers/StudioTabController.ts` | Amortization schedule, yearly SIP invested, returns, closing balances, SWP drawdowns, swipeable mobile card carousel (`mobile-breakdown-cards`), and compact 3-col list toggle; fuses seamlessly into `#breakdown-studio` via `in_studio: true` while rendering standalone in `calculator-guide.twig` (`in_studio: false`) |
| **Interactive Chart** | `chart-visualization.twig` | `ChartManager.ts`<br/>`chart/ChartPlugins.ts`<br/>`chart/ChartGradientFactory.ts`<br/>`chart/ChartMilestoneCalculator.ts`<br/>`chart/ChartDatasetBuilder.ts`<br/>`controllers/ChartScrubbingController.ts` | Modularized Chart.js coordinator, 7 lifecycle plugins, GPU cached gradient factory, milestone calculator, line/donut dataset builder, and scrub cursor |
| **Command Palette & HUD** | `command-palette.twig`<br/>`floating-discovery-hud.twig`<br/>`layouts/base.twig` | `subsystems/ErgonomicsSubsystem.ts`<br/>`controllers/MobileErgonomicDeckController.ts`<br/>`controllers/KeyboardViewportController.ts`<br/>`controllers/FloatingHudController.ts` | Keyboard navigation (`Cmd+K`), quick calculator switching, 4-metric real-time mobile floating action dock, honeypot-safe keyboard field stepper bar (`#keyboard-docked-preview`), and sticky tablet/mobile mini-HUD |
| **Hub-and-Spoke Quick Dock & Contextual Bridges** | `calculator-dock.twig`<br/>`forms/sip-fields.twig`<br/>`forms/swp-fields.twig`<br/>`forms/corpus-field.twig` | `controllers/ContextualBridgeController.ts`<br/>`CalculatorApp.ts` | Tier-1 Core Pillars navigation dock, dynamic active milestone tab projection, and real-time client-side in-flow milestone blueprint bridges |
| **Slider, Number Input & Slot 1B Disclosure** | `form/input-range-pair.twig`<br/>`forms/lumpsum-fields.twig` | `SliderManager.ts`<br/>`controllers/StepperController.ts`<br/>`controllers/LumpsumDisclosureController.ts` | Two-way synchronized range slider and currency text inputs with `footer_action_html` action hook, 48px ergonomic touch stepper targets, isolated `group/field` preset chip drawers, auto-step rounding, and WAI-ARIA Slot 1B Initial Lumpsum disclosure (`Expanded = Active, Collapsed = ₹0`) |
| **QR Share & Multi-Format Exports** | `qr-share-modal.twig`<br/>`chart-visualization.twig`<br/>`pdf-modal.twig` | `subsystems/ExportSubsystem.ts`<br/>`controllers/ExcelExportController.ts`<br/>`controllers/CsvExportController.ts`<br/>`controllers/PdfExportController.ts` | Encoded URL state generation, canvas QR render, native Web Share API, Branded PDF proposal, CSV download, and lazy-loaded SheetJS `.xlsx` multi-sheet workbook export |
| **SEBI Benchmark & Trust** | `sebibenchmark-modal.twig`<br/>`privacy-trust-badge.twig` | `subsystems/EngagementSubsystem.ts` | Regulatory disclosure compliance, index benchmark comparisons, privacy audit badges |
| **Scenario Benchmark Tables** | `scenario-benchmark-table.twig`<br/>`components/benchmarks/*.twig` (10 modular partials) | Polymorphic `CalculatorStrategyInterface::getBenchmarkTemplate()` & `getBenchmarkTitle()` | Pre-calculated static benchmark matrices for Google rich answers and AI search engine extraction (Gemini / Copilot), partitioned into 10 decoupled partials with sticky first-columns |
| **3-Mode Segmented Selector** | `components/calculator-form.twig` | `controllers/TabController.ts` | Instant toggle between Dual (SIP + SWP), SIP Only, and SWP Only with automatic phase staging and zero reload |
| **Strategy Starter & Guided Wizard** | `strategy-starter.twig` | `controllers/StrategyBlueprintController.ts`<br/>`controllers/WealthQuizController.ts` | 1-click popular goal presets (₹1 Cr, FIRE, Education, Senior SWP) and modal 3-step wealth questionnaire |
| **Embed Calculator Modal** | `embed-modal.twig` | Frontend `<dialog>` / `copyEmbedCodeBtn` | 1-click responsive iframe generator for external financial bloggers and advisors with attribution backlink |

---

## 3. Document Generation & Export Pipeline

| Export Type | HTTP Trigger Route | Backend Controller Action | Core Service / Template | Frontend Orchestrator |
| :--- | :--- | :--- | :--- | :--- |
| **PDF Report** | `POST /generate-pdf` | `Controllers\GeneratePdfAction` | `Services\PdfGeneratorService`<br/>`Services\SpecializedReportBuilder`<br/>`Core\PdfReportTemplate`<br/>`Views/pdf/report.twig`<br/>`Views/pdf/components/*.twig`<br/>`Views/components/pdf-modal.twig`<br/>`Core\PdfReportStylesheet`<br/>`Core\PdfReportTableBuilder` | `subsystems/ExportSubsystem.ts`<br/>`controllers/PdfExportController.ts` |
| **CSV Amortization** | `POST /download-csv` | `Controllers\DownloadCsvAction` | `Services\CsvExportServiceInterface`<br/>(`Services\CsvExportService`, `generateDynamic()`)<br/>`Services\SpecializedReportBuilder`<br/>`Core\Math\CagrEngine::generateSchedule()` | `subsystems/ExportSubsystem.ts` (`downloadCsv()`) |

---

## 4. Admin Telemetry & Analytics Pipeline

| Component | Route / Path | Action / Class | Database / Storage Layer | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **Live Telemetry API** | `POST /log_insight` | `Controllers\LogInsightApiAction` | `Core\AnonymizedInsightLogger`<br/>`Core\InsightRepository`<br/>`database/insights.sqlite` | Privacy-safe calculation telemetry logging without PII |
| **Admin Dashboard** | `GET /admin_insights`<br/>`POST /admin_insights` | `Controllers\ShowAdminDashboardAction`<br/>`Controllers\ProcessAdminLoginAction`<br/>`Controllers\ProcessAdminLogoutAction` | `src/Views/admin/dashboard.twig`<br/>`Core\AdminDashboardPresenter`<br/>`Core\AdminAuthService` | Operational telemetry dashboard with session auth & rate-limiting |
| **Database Migrations** | CLI / App bootstrap | `Core\DatabaseMigrator` | `database/migrations/` | SQLite automated schema initialization & column migrations |
| **IndexNow Submission CLI** | `bin/indexnow` | Standalone CLI | `ade10b6f29714f90b6e4666dc36468b3.txt`<br/>`url.txt` | Submits published/updated site URLs to IndexNow (Bing/Yandex) |
| **SEO & Analytics MCP Server** | `scripts/seo-mcp/server.py` | FastMCP Service | GSC API, Bing Webmaster API, GA4 Data & Admin API | Model Context Protocol server exposing GSC (`gsc_*`), Bing (`bing_*`), and GA4 (`ga4_query_report`, `ga4_realtime_report`) tools |
| **Telemetry Pruning** | Service | `Services\TelemetryPruningServiceInterface`<br/>(`Services\TelemetryPruningService`) | `Core\InsightRepository` | Prunes telemetry logs beyond retention policy limits |

---

## 5. Content Repositories & JSON Data Matrix

| Content Domain | JSON / Markdown Source | Repository / Loader Service | Twig Consumer / Template |
| :--- | :--- | :--- | :--- |
| **Defaults & Range Limits** | `content/calculator_defaults.json` | `Services\ConfigService` | `calculator-guide.twig`<br/>`home.twig`<br/>`CalculatorApp.ts` |
| **SEO FAQs** | `content/faqs.json` | `Core\FaqRepository` | `components/guide-faq.twig`<br/>`Core\Factories\SchemaFactory` (FAQPage Schema) |
| **Financial Glossary** | `content/glossary.json` | `Core\GlossaryRepository` | `Controllers\RenderGlossaryAction`<br/>`src/Views/pages/glossary.twig` |
| **Educational Guides** | `content/calculators/*.md` | `Core\ContentManager` | `src/Services/GuideViewModelBuilder`<br/>`calculators/calculator-guide.twig` |
| **Blog Articles** | `content/blog/*.md` | `Core\BlogRepository` | `Controllers\ShowResourcePostAction`<br/>`src/Views/pages/post.twig` |
| **Categories & Pillars** | `content/categories.json`<br/>`content/calculator_pillar_guides.json` | `Services\GuideViewModelBuilder` | `components/related-pillar-guides.twig`<br/>`components/related-calculators.twig` |
| **Cross-Links** | `content/calculator_links.json` | `Services\GuideViewModelBuilder`<br/>`Controllers\RenderHomeAction` | `components/related-calculators.twig`<br/>`components/calculator-dock.twig`<br/>`calculators/home.twig` |
| **Meta Tags & OpenGraph** | `content/meta_pages.json` | `Core\MetaManager` | `layouts/base.twig`<br/>`components/page-hero.twig` |
| **URL Redirects** | `content/redirects.json` | `Core\RedirectLoader` | `Core\Router.php` (301 Permanent Redirects) |
| **Rate Limit Rules** | `content/rate_limits.json` | `Services\ConfigService`<br/>`Services\RateLimiter` | `Controllers\LogInsightApiAction`<br/>`Controllers\GeneratePdfAction` |
| **Theme Design Tokens** | `content/theme_tokens.json` | `Services\ConfigService` | Injected into JSON Island `calculator-app-state` |

---

## 6. Critical Architecture Nuances & Runtime Guardrails

### A. The Data Island Pattern (Hydration Standard)
- **Invariant:** NEVER inject global JavaScript state (`window.mode = 'sip'`) or scatter state across HTML attributes.
- **Contract:** PHP injects a single structured tag: `<script type="application/json" id="calculator-app-state">`. TypeScript (`CalculatorApp.ts`) reads and validates this payload on DOM initialization.

### B. Dual-Phase Hydration & Dynamic DOM Ownership
- **SSR Initial Hydration:** `RenderHomeAction` and `GuideViewModelBuilder` (with `Core\PurchasingPowerHelper`) pre-compute `initial_kpis`, mode-accurate `initial_telemetry`, and `initial_schedule` (`extractMilestoneRows`) from canonical `calculator_defaults.json` defaults so every calculator route renders zero `--` KPI placeholders and populated mobile milestone cards/lists in raw HTML.
- **Dynamic Client Ownership:** `CalculatorApp.ts` (and `drivers/*.ts`) owns all post-load recalculations, live chart updates (`<canvas id="results-chart">`), and desktop ledger table rows (`<tbody id="breakdown-body">`). Never replace frontend calculations with AJAX calls.

### C. Mathematical Engine Rules & Rounding
- **End-of-Period Timing:** Contributions are added *before* monthly compounding: $B_m = (B_{m-1} + C_m - W_m) \times (1 + r/12)$.
- **Nominal Rate Standard:** Monthly rate is strictly $r / 100 / 12$ (Indian mutual fund standard), never geometric $(1+r)^{1/12} - 1$.
- **Binary Floating Point Drift Guard:** In TypeScript, always use `Math.round((val + Number.EPSILON) * 100) / 100` to prevent IEEE 754 precision drift.
- **Strict Nullness Contract:** Accumulation years must have `swp_monthly: null` and `annual_withdrawal: null`. SWP years must have `sip_monthly: null`. Never emit `0` or `0.0` in place of `null`.
- **40-Iteration Binary Search:** Goal-seeking solvers (`TargetCorpusStrategy.ts`) run a fixed 40-step binary search to guarantee exact convergence in $<5\text{ms}$ with zero risk of infinite looping.

### D. SEO Metadata, Schema, Deterministic Lastmod & Export Contract
- **Mandatory Schemas:** Every calculator route outputs `SoftwareApplication`, `FAQPage`, `BreadcrumbList`, and `HowTo` via `SchemaFactory.php` and `HomeSchemaBuilder.php` (using PNG `Organization.logo` at `assets/logo-512.png`). `Article` schema is omitted on `type === 'calculator'` routes, and `/` emits a single `<head>` `WebPage` JSON-LD (with `SpeakableSpecification` `#quick-answer`) via `SchemaFactory::generateForHome()`.
- **No Self-Served Ratings:** Self-served `AggregateRating` is strictly forbidden to prevent algorithmic suppression of rich results across Google Search Console.
- **Title Length Limit & H1 Decoupling:** Page `<title>` tags must strictly remain between **10 and 65 characters** (enforced by `tests/Integration/SeoMetadataValidatorTest.php`). Frontmatter `h1` optionally decouples hero `<h1>` and breadcrumb labels from SERP `<title>` (e.g. removing `(with Free Excel...)` suffixes).
- **Deterministic `<lastmod>` & Production-Only Analytics:** `ContentManager::getFileModifiedDate()` resolves frontmatter `updated_at` -> `date` -> `DateConstants::SITE_LAST_UPDATED` fallback (`SitemapGenerator.php` and `RenderHomeAction.php` avoid volatile CI `filemtime()`). `base.twig` gates GA4/Clarity/Ahrefs behind `window.__IS_PROD_HOST__`, and `related-resources.twig` prioritizes same-category posts first.
- **AI Crawlers, Crawlability & Redirect Consolidation:** `llms.txt` and `llms-full.txt` served from web root; `robots.txt` explicitly allows `GPTBot`, `Google-Extended`, `PerplexityBot`, `ClaudeBot`, `Amazonbot`, `anthropic-ai`, `cohere-ai`, and `OAI-SearchBot`, and leaves legacy redirect paths (`/0.6.10`, `/sipswpcalculator.com`) crawlable so `301` redirects in `content/redirects.json` consolidate authority onto `/`.
- **Universal Export Fulfillment:** All 17 calculator routes expose Excel (CSV) and PDF exports via `ExportSubsystem.ts` + `Services\SpecializedReportBuilder`.

### E. Vite & Tailwind CSS v4 Pipeline & CSS Cascade Layers
- **Tailwind v4 Directive:** Configured exclusively in `resources/css/input.css` (`@theme`, `@source`). Never create `tailwind.config.js`.
- **Cascade Layer Discipline (`input.css` & `styles.css`):** Both stylesheets import after Tailwind CSS v4 (`resources/js/app.ts`) and MUST wrap element resets in `@layer base` and component selectors in `@layer components` (keeping only `@theme`, `@source`, `@utility`, `@keyframes`, and top-level `:root` tokens unlayered). Never re-introduce unlayered `!important` utility polyfills or bare deprecated `.glass-card` / `CardSpotlightController`.
- **Single-Source Range Sliders:** All slider tracks and thumbs live solely in `resources/css/input.css` (`@layer components`). Do not put Tailwind `h-2` or `bg-slate-200` utilities on `<input type="range">` in Twig (`input-range-pair.twig`, `#mobile-chart-scrubber` in `chart-visualization.twig`), as `@layer utilities` would override desktop `6px` track height.
- **Scoped Editorial `.prose`:** `styles.css` uses zero-specificity `:where(.prose) ...:not(:where(.not-prose, .not-prose *))` and `table:not([class])` so bare Markdown pipe tables remain stylized without overriding UI blocks wrapped in `.not-prose` (`generic-post.twig`, `interactive-cta-banner.twig`, `related-resources.twig`) or classed HTML tables.
- **Twig Layout Requirements:** Every base layout must include:
  1. `{{ vite_client() }}` (HMR dev script)
  2. `{{ vite_css('resources/js/app.ts') }}` (Compiled CSS link)
  3. `<script type="module" src="{{ vite_asset('resources/js/app.ts') }}"></script>` (Main client bundle)

### F. Production Security & Hosting Traps (Hostinger / LiteSpeed / CloudLinux)
- **No Direct Files in `/tmp`:** CageFS virtualizes `/tmp` per execution context. Standardize on `var/` and `database/`.
- **LiteSpeed Headers:** Send `X-Accel-Buffering: no` on PDF and CSV downloads to prevent reverse proxy buffer deadlocks.
- **Session Edge Caching:** Do not call `session_start()` on public GET calculator routes so Cloudflare/LiteSpeed edge caching remains functional.
- **CSV Formula Injection:** Prefix non-numeric formula cells starting with `=`, `+`, `-`, `@`, `\t` with a single quote `'`.
- **Excel INR Encoding:** Always prepend `\xEF\xBB\xBF` (UTF-8 BOM) to CSV downloads so Excel displays the `₹` symbol correctly.

### G. Foldable, Tablet, Slot 1B Disclosure & Analytical Studio Fusion Guardrails
- **3-Child Home Grid (`home.twig` & `analytical-studio.twig`):** Row-1 wrapper (`grid grid-cols-1 md:grid-cols-12 items-start gap-5 lg:gap-8 mb-5 lg:mb-8 xl:mb-0 xl:contents`) inside the desktop grid (`xl:grid xl:grid-cols-12 xl:grid-rows-[auto_1fr] xl:items-start xl:gap-8`) isolates Child 1 Form (`md:col-span-5 xl:row-span-2 xl:sticky xl:top-24`) + Child 2 Chart/Quick Answer (`md:col-span-7 self-start md:sticky md:top-24 xl:static`) on `<1280px` so tablet `md:sticky` unsticks at Row-1's bottom boundary with zero collision against full-width opaque `bg-white` Child 3 Studio (`xl:col-span-7 xl:col-start-6 min-w-0`), while `xl:contents` flattens Row 1 cleanly at `>=1280px`; plus `min-w-0` shrink guards and gentle `< xl` form padding/gap compaction (`calculator-form.twig`, `input-range-pair.twig`, `sip-fields.twig`, `lumpsum-fields.twig`, `swp-fields.twig`).
- **Unified Studio & Ledger Workbench (`analytical-studio.twig` + `yearly-breakdown-table.twig`):** `#breakdown-studio` renders as a solid white `rounded-3xl bg-white border border-slate-200/90 shadow-card` container (no root `overflow-hidden` so `#export-share-dropdown` stays unclipped) and includes `yearly-breakdown-table.twig` with `in_studio: true`, suppressing the duplicate `"Yearly Cashflow Ledger"` header and mobile Tier 1 toolbar while preserving standalone rendering (`calculator-guide.twig`), `#yearly-breakdown` scroll anchor, and test-locked IDs.
- **Slot 1B Initial Lumpsum Progressive Disclosure (`lumpsum-fields.twig` + `LumpsumDisclosureController.ts`):** Uses a semantic WAI-ARIA `<button type="button" id="lumpsum-toggle-btn" aria-expanded aria-controls="lumpsum-drawer">` + CSS `hidden` drawer `#lumpsum-drawer` + `#lumpsum-remove-btn` directly below `#sip_container` (`FormStructureParityTest` forbids `<details>`/`<summary>`), keeping `Monthly SIP` and `Initial Lumpsum` in separate `group/field` hover scopes. Enforces **`Expanded = Active, Collapsed = ₹0`** across `/` (`RenderHomeAction`) and `sip`, `sip-step-up`, `combo`, `all` guides (`GuideViewModelBuilder::build`), while `GuideRenderer::renderEmbed()` locks `show_lumpsum = ($calculator_type === 'lumpsum')` for compact `/embed/*` widgets.
- **Monotonic Chart Aspect Curve (`chart-visualization.twig`):** `#chart-canvas-container` uses monotonic landscape breakpoints `h-[250px] sm:h-[290px] md:h-[260px] lg:h-[320px] xl:h-[395px]` (`maintainAspectRatio: false`) paired with compact `< xl` `#chart-card` chrome padding (`transition-shadow`) to prevent foldable/tablet letterboxing.
- **KPI Matrix SSR/TS Parity:** `#summary-cards-grid` uses `grid-cols-2 xl:grid-cols-4` (4-card) or `grid-cols-2 xl:grid-cols-3` + `col-span-2 xl:col-span-1` on `#card-corpus` (3-card), synchronized identically across `chart-visualization.twig`, `SummaryMetricsController.ts`, `EmiDriver.ts`, and `SpecializedCalculatorController.ts`.
- **Chromium GPU Compositor & Glassmorphism Standards:** Always use `body { background-attachment: scroll }` across all viewports (never apply `background-attachment: fixed` on body or card containers as it triggers Skia rasterizer canvas drops on mobile/tablet viewports). Authentic frosted glassmorphism is implemented via `@utility fintech-glass-card` (`backdrop-blur-md`, `bg-white/85` to `bg-white/95`, `border-slate-200/90`, inner specular highlights `box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.9)`), with `backdrop-blur-xl` on modal backdrops (`#wealth-guided-wizard-drawer`). Keep `.metric-pulse-active` GPU transform-only (`input.css`), immediate `.stagger-row` `opacity: 1`, `z-behind` glow blobs behind `z-10` headers, `@supports`-guarded `.text-gradient`, and `#mini-hud-anchor` at `top-20`.

### H. 4-Color Semantic Pillar System (Light-Mode Mid-Tone Saturation Standards)
- **Invariant:** Retain 100% pure light-mode card surfaces (`bg-white` / `bg-slate-50`, zero inverted dark cards) and cool slate token exclusivity (zero `gray-*`). Tonal contrast and instant section recognition are delivered via mid-tone and deeper executive luxury chromatic anchors:
  - **Pillar 1 — Institutional & Forest Emerald (`emerald-700` / `#047857`, `emerald-600` / `#059669`):** Stage 1 SIP & Wealth Accumulation (SIP, Step-Up, Lumpsum, CAGR, Compound Interest, Definitions, How-To, Math Transparency proofs, and SIP Returns Benchmark Matrix). Affordances: `border-t-4 border-emerald-700`, solid canopies `from-emerald-700 via-emerald-800 to-teal-800`, solid icon badges `bg-emerald-700 text-white shadow-emerald-700/20`, tag pills `bg-emerald-100/90 text-emerald-950 border-emerald-300/80`.
  - **Pillar 2 — Warm Amber & Burnished Harvest (`amber-700` / `#b45309`, `amber-600` / `#d97706`):** Stage 2 SWP & Retirement Cashflow (Retirement Hub, SWP payouts, SWR Benchmarks, Real-world retirement case studies). Affordances: `border-t-4 border-amber-700`, solid canopies `from-amber-700 via-amber-800 to-amber-900`, solid icon badges `bg-amber-700 text-white shadow-amber-700/20`, tag pills `bg-amber-100/90 text-amber-950 border-amber-300/80`. Generic blue is completely eliminated.
  - **Pillar 3 — Sovereign Indigo (`indigo-600` / `#4F46E5`):** Fixed Deposit, Tax Optimization (§112A LTCG), Inflation & Market Volatility (Tax waterfall, inflation adjustments, historical drawdowns & recoveries). Affordances: `border-t-4 border-indigo-600`, solid icon badges `bg-indigo-600 text-white shadow-indigo-600/20`, tag pills `bg-indigo-100/90 text-indigo-950 border-indigo-200`.
  - **Pillar 4 — Sovereign Gold & Milestones (`amber-500` / `emerald-700`):** Milestones (₹1 Cr, ₹5 Cr, FIRE), Sovereign Benchmarks (PPF), Privacy & Security Trust, and FAQ Vault. Affordances: `border-t-4 border-t-amber-500`, solid icon badges `bg-emerald-700 text-white shadow-emerald-700/20`, tag pills `bg-slate-100/90 text-slate-700 border-slate-200/80`.

---

## 7. Testing & Verification Suite

- **Asset Compilation & TypeScript Validation:** `npm run typecheck && npm run build` (0 TypeScript errors, Vite CSS & JS production bundle)
- **Parity Verification Script:** `php tests/parity_check.php` (71/71 TypeScript vs. PHP mathematical engine parity tests)
- **Full PHPUnit Test Suite:** `vendor/bin/phpunit` (820 tests, 12,776 assertions)
- **Strict Linter & Static Analysis Gate:** `composer check-all` (PHPStan Level Max on 253 files + PSR-12 CodeSniffer on 253 files + all 820 PHPUnit tests)
