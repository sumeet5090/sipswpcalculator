# Architecture Coordinate Map (Zero-Latency File Index)

This document is the authoritative coordinate map and architectural cheat-sheet for the `sipswpcalculator` codebase.
**Rule for Agents:** Consult this document first before performing exploratory `grep_search` or `list_dir`.

---

## 1. Calculator Matrix (Route -> Logic -> View)

| Route Slug | PHP Action | Twig Layout & Form Partial | TS Strategy / Driver | Engine / Math Parity | Defaults Key (`calculator_defaults.json`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `/` (Home) | `Controllers\RenderHomeAction` | `calculators/home.twig`<br/>`forms/sip-fields.twig`<br/>`forms/swp-fields.twig` | `CalculatorApp.ts`<br/>`strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `sip`, `years`, `rate`, `stepup`, `inflation`, `lumpsum`, `swp_withdrawal` |
| `/sip-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig` | `strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `sip`, `years`, `rate`, `stepup` |
| `/swp-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/corpus-field.twig`<br/>`forms/swp-fields.twig` | `strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `corpus`, `swp_withdrawal`, `years`, `rate`, `inflation` |
| `/sip-step-up-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig` | `strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `sip`, `years`, `rate`, `stepup` |
| `/lumpsum-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/lumpsum-only-fields.twig` | `strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `lumpsum`, `years`, `rate` |
| `/retirement-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/sip-fields.twig`<br/>`forms/swp-fields.twig` | `strategies/GrowStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | Combo defaults |
| `/my-first-crore-calculator`<br/>`/target-corpus-calculator`<br/>`/reach-1-crore-via-sip`<br/>`/reach-5-crore-via-sip` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/target-corpus-fields.twig` | `strategies/TargetCorpusStrategy.ts` | `MathEngine.ts`<br/>`Services\InvestmentCalculator` | `target_corpus`, `years`, `rate`, `stepup` |
| `/compound-interest-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/compound-interest-fields.twig` | `drivers/CompoundInterestDriver.ts` | `engines/CompoundInterestEngine.ts` | `ci_*` |
| `/cagr-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/cagr-fields.twig` | `drivers/CagrDriver.ts` | `engines/CagrEngine.ts` | `cagr_*` |
| `/emi-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/emi-fields.twig` | `drivers/EmiDriver.ts` | `engines/EmiEngine.ts` | `emi_*` |
| `/inflation-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/inflation-fields.twig` | `drivers/InflationDriver.ts` | `engines/InflationEngine.ts` | `inflation_*` |
| `/ppf-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/ppf-fields.twig` | `drivers/PpfDriver.ts` | `engines/PpfEngine.ts` | `ppf_*` |
| `/fd-calculator` | `Controllers\RenderGuideAction` | `calculators/calculator-guide.twig`<br/>`forms/fd-fields.twig` | `drivers/FdDriver.ts` | `engines/FdEngine.ts` | `fd_*` |
| `/embed/{slug}` | `Controllers\RenderEmbedAction` | `calculators/embed.twig` | `CalculatorApp.ts` | `MathEngine.ts` | Embedded config |

---

## 2. Interactive Modals & Studio Subsystems Matrix

| Feature / Widget | Twig Template (`src/Views/components/`) | Orchestrating TS Controller / Subsystem | Key Purpose & Capabilities |
| :--- | :--- | :--- | :--- |
| **City FIRE Benchmark** | `city-fire-benchmark.twig` | `subsystems/EngagementSubsystem.ts` | Indian tier-1/tier-2/tier-3 city geo-arbitrage, housing tenure toggles, SWR multipliers (25x-40x) |
| **Stress Test Simulator** | `stress-test-simulator.twig` | `subsystems/LifecycleSubsystem.ts` | Historical crash modeling (2008 GFC, 2020 Covid, 2013 Taper Tantrum) with recovery projections |
| **Tax Waterfall Modal** | `tax-waterfall-modal.twig` | `subsystems/LifecycleSubsystem.ts` | Union Budget 2024 equity LTCG (12.5% above ₹1.25L) & STCG (20%) tax net return impact |
| **Asset Rebalancing** | `asset-rebalancing.twig` | `subsystems/LifecycleSubsystem.ts` | Equity/Debt drift rebalancing optimizer and trigger alerts |
| **Yearly Breakdown Table** | `yearly-breakdown-table.twig` | `controllers/ResultsController.ts` | Amortization schedule, yearly SIP invested, returns, closing balances, SWP drawdowns, swipeable mobile card carousel (`mobile-breakdown-cards`), and compact 3-col list toggle |
| **Interactive Chart** | `chart-visualization.twig` | `ChartManager.ts`<br/>`controllers/ChartScrubbingController.ts` | Dual Chart.js donut and line graph with live scrub cursor |
| **Command Palette & HUD** | `command-palette.twig`<br/>`floating-discovery-hud.twig`<br/>`layouts/base.twig` | `subsystems/ErgonomicsSubsystem.ts`<br/>`controllers/MobileErgonomicDeckController.ts`<br/>`controllers/FloatingHudController.ts` | Keyboard navigation (`Cmd+K`), quick calculator switching, 4-metric real-time mobile floating action dock, and sticky tablet/mobile mini-HUD |
| **Slider & Number Input** | `form/input-range-pair.twig` | `SliderManager.ts`<br/>`controllers/StepperController.ts` | Two-way synchronized range slider and currency text inputs with 48px ergonomic touch stepper targets, preset chips, and auto-step rounding |
| **QR Share & Socials** | `qr-share-modal.twig` | `subsystems/ExportSubsystem.ts` | Encoded URL state generation, canvas QR render, and native Web Share API trigger |
| **SEBI Benchmark & Trust** | `sebibenchmark-modal.twig`<br/>`privacy-trust-badge.twig` | `subsystems/EngagementSubsystem.ts` | Regulatory disclosure compliance, index benchmark comparisons, privacy audit badges |

---

## 3. Document Generation & Export Pipeline

| Export Type | HTTP Trigger Route | Backend Controller Action | Core Service / Template | Frontend Orchestrator |
| :--- | :--- | :--- | :--- | :--- |
| **PDF Report** | `POST /generate-pdf` | `Controllers\GeneratePdfAction` | `Services\PdfGeneratorService`<br/>`Core\PdfReportTemplate`<br/>`Core\PdfReportStylesheet`<br/>`Core\PdfReportTableBuilder` | `subsystems/ExportSubsystem.ts` (`downloadPdf()`) |
| **CSV Amortization** | `POST /download-csv` | `Controllers\DownloadCsvAction` | `Services\CsvExportService` | `subsystems/ExportSubsystem.ts` (`downloadCsv()`) |

---

## 4. Admin Telemetry & Analytics Pipeline

| Component | Route / Path | Action / Class | Database / Storage Layer | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **Live Telemetry API** | `POST /log_insight` | `Controllers\LogInsightApiAction` | `Core\AnonymizedInsightLogger`<br/>`Core\InsightRepository`<br/>`database/insights.sqlite` | Privacy-safe calculation telemetry logging without PII |
| **Admin Dashboard** | `GET /admin_insights`<br/>`POST /admin_insights` | `Controllers\ShowAdminDashboardAction`<br/>`Controllers\ProcessAdminLoginAction`<br/>`Controllers\ProcessAdminLogoutAction` | `src/Views/admin/dashboard.twig`<br/>`Core\AdminDashboardPresenter`<br/>`Core\AdminAuthService` | Operational telemetry dashboard with session auth & rate-limiting |
| **Database Migrations** | CLI / App bootstrap | `Core\DatabaseMigrator` | `database/migrations/` | SQLite automated schema initialization & column migrations |
| **Telemetry Pruning** | Service | `Services\TelemetryPruningService` | `Core\InsightRepository` | Prunes telemetry logs beyond retention policy limits |

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
| **Cross-Links** | `content/calculator_links.json` | `Services\GuideViewModelBuilder` | `components/related-calculators.twig` |
| **Meta Tags & OpenGraph** | `content/meta_pages.json` | `Core\MetaManager` | `layouts/base.twig`<br/>`components/page-hero.twig` |
| **URL Redirects** | `content/redirects.json` | `Core\RedirectLoader` | `Core\Router.php` (301 Permanent Redirects) |
| **Rate Limit Rules** | `content/rate_limits.json` | `Services\ConfigService`<br/>`Services\RateLimiter` | `Controllers\LogInsightApiAction`<br/>`Controllers\GeneratePdfAction` |
| **Theme Design Tokens** | `content/theme_tokens.json` | `Services\ConfigService` | Injected into JSON Island `calculator-app-state` |

---

## 6. Critical Architecture Nuances & Runtime Guardrails

### A. The Data Island Pattern (Hydration Standard)
- **Invariant:** NEVER inject global JavaScript state (`window.mode = 'sip'`) or scatter state across HTML attributes.
- **Contract:** PHP injects a single structured tag: `<script type="application/json" id="calculator-app-state">`. TypeScript (`CalculatorApp.ts`) reads and validates this payload on DOM initialization.

### B. Client-Owned Rendering (DOM Output Ownership)
- **Invariant:** NEVER use PHP/Twig loops (`{% for row in results %}`) to pre-render dynamic calculation tables or charts.
- **Contract:** PHP renders only empty skeleton targets (e.g. `<tbody id="yearly-breakdown-body">`, `<canvas id="results-chart">`). TypeScript is solely responsible for rendering and updating dynamic DOM output.

### C. Mathematical Engine Rules & Rounding
- **End-of-Period Timing:** Contributions are added *before* monthly compounding: $B_m = (B_{m-1} + C_m - W_m) \times (1 + r/12)$.
- **Nominal Rate Standard:** Monthly rate is strictly $r / 100 / 12$ (Indian mutual fund standard), never geometric $(1+r)^{1/12} - 1$.
- **Binary Floating Point Drift Guard:** In TypeScript, always use `Math.round((val + Number.EPSILON) * 100) / 100` to prevent IEEE 754 precision drift.
- **Strict Nullness Contract:** Accumulation years must have `swp_monthly: null` and `annual_withdrawal: null`. SWP years must have `sip_monthly: null`. Never emit `0` or `0.0` in place of `null`.
- **40-Iteration Binary Search:** Goal-seeking solvers (`TargetCorpusStrategy.ts`) run a fixed 40-step binary search to guarantee exact convergence in $<5\text{ms}$ with zero risk of infinite looping.

### D. SEO Metadata & Triple-Schema Contract
- **Mandatory Schemas:** Every calculator route must output structured JSON-LD schemas: `SoftwareApplication`, `FAQPage`, and contextual `HowTo` (via `SchemaFactory.php` and `HomeSchemaBuilder.php`).
- **Title Length Limit:** Page `<title>` tags must strictly remain between **10 and 65 characters** (enforced by `tests/Integration/SeoMetadataValidatorTest.php`).
- **AI Crawlers & Discovery:** `llms.txt` and `llms-full.txt` served from web root; `robots.txt` explicitly allows `GPTBot`, `Google-Extended`, `PerplexityBot`, `ClaudeBot`, `Amazonbot`, `anthropic-ai`, `cohere-ai`, and `OAI-SearchBot`.

### E. Vite & Tailwind CSS v4 Pipeline
- **Tailwind v4 Directive:** Configured exclusively in `resources/css/input.css` (`@theme`, `@source`). Never create `tailwind.config.js`.
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

---

## 7. Testing & Verification Suite

- **Parity Verification Script:** `php tests/parity_check.php` (TypeScript vs. PHP mathematical parity)
- **Full PHPUnit Test Suite:** `composer test` or `vendor/bin/phpunit`
- **Strict Linter & Static Analysis:** `composer check-all` (PHPStan level 8 + PHPCS + PHPUnit)
