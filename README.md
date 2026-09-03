# SIP & SWP Calculator

A production-grade, server-rendered financial calculator for Systematic Investment Plans (SIP) and Systematic Withdrawal Plans (SWP). Features a fully bi-directional calculation engine (back-calculates Monthly SIP required to hit a Target Corpus or sustain a target SWP retirement plan), directly exposed primary investment parameters with clean semantic adjustment grouping, and viewport-aware responsive scaling. Built with PHP (MVC), Twig, Vite, Tailwind CSS v4, and Chart.js.

**Live:** [sipswpcalculator.com](https://sipswpcalculator.com)

---

## Architecture Overview

The project follows a modern MVC architecture, separating concerns between routing, core logic, and Twig-based views, bundled efficiently by Vite.

```text
sipswpcalculator/
├── index.php                 # Main front-controller and router
├── .htaccess                 # Apache config (HTTPS redirect, clean URLs)
├── package.json              # Node dependencies & Vite build scripts
├── composer.json             # PHP dependencies (Twig, DomPDF)
├── vite.config.js            # Vite bundler configuration
│
├── src/
│   ├── Controllers/          # Request handling and view rendering
│   ├── Core/                 # Framework utilities, ActionDispatcher, Router, Middleware, and App initialization
│   │   ├── Middleware/       # Security (CSRF), Session Management & Routing (Trailing Slash 301) Middleware
│   │   ├── Strategies/       # Strategy Patterns for Calculators
│   │   └── Factories/        # Factories (e.g. SEO SchemaFactory)
│   ├── Services/             # Business logic (Calculations, PDF, SEO generation)
│   └── Views/                # Twig templates
│       ├── layouts/          # Base HTML structures
│       ├── pages/            # Individual route templates
│       └── components/       # Reusable UI elements (forms, charts)
│
├── assets/                   # Source CSS and JS (processed by Vite)
│   ├── css/
│   │   └── input.css         # Tailwind v4 entry point
│   └── js/
│       └── script.js         # Client-side application entry
│
├── dist/                     # Compiled Vite output (Ignored in Git, built in CI/CD)
├── database/                 # SQLite databases (if applicable)
└── .github/workflows/        # Automated CI/CD pipelines
```

### Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| **Server/Routing** | PHP 8.x (MVC) on Apache | Route handling, business logic, form processing |
| **Templating** | Twig 3.x | Secure, modular server-side HTML rendering |
| **Frontend Logic** | TypeScript 5.x | Strictly-typed OOP calculation engines, DOM adapters, and strategies |
| **Asset Bundling** | Vite 5.x | High-performance TS/CSS compiling and Hot Module Replacement (HMR) |
| **Styling** | Tailwind CSS v4 | Utility-first CSS framework |
| **Charts** | Chart.js 3.7 | Interactive financial projection graphs |
| **PDF Generation** | DomPDF 2.x | Branded PDF report generation |

---

## Prerequisites

| Requirement | Version | Check Command |
|---|---|---|
| **PHP** | 8.0+ | `php -v` |
| **Apache** | 2.4+ (with mod_rewrite) | Bundled with XAMPP |
| **Node.js** | 20+ | `node -v` |
| **Composer** | 2.x | `composer -V` |

---

## Local Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/sumeet5090/sipswpcalculator.git
cd sipswpcalculator
```

### 2. Install Dependencies

```bash
# PHP dependencies (Twig, DomPDF, PHPStan)
composer install

# Node dependencies (Vite, Tailwind)
npm ci
```

### 3. Local Server setup

You can serve the application locally using PHP's built-in server or XAMPP.
```bash
php -S localhost:8080 index.php
```

### 4. Database Initialization

To set up your local SQLite database and run the initial schema migrations:

```bash
php bin/migrate
```

This CLI migrator executes all outstanding PHP schema migrations. You can also trigger database upgrades in the browser at `/admin_insights/migrate` once logged in.

---

## Technical Details & Testing

### Calculator & System Configuration Single Sources of Truth
- `content/calculator_defaults.json` is the single source of truth for all calculator bounds, minimum/maximum limits, and default field values. The backend accesses this via `Services\ConfigService` (registered as a singleton in the DI container), avoiding redundant file reads.
- `content/rate_limits.json` is the centralized configuration for rate-limiting thresholds and window durations across `admin_auth`, `pdf_generation`, and `log_insight` endpoints.

### Architectural Refactorings & Services
- **Decoupled Security & Composite Middleware**: Bot prevention (`Core\Middleware\HoneypotMiddleware`) and CSRF validation (`Core\Middleware\AdminCsrfMiddleware`) are individual singletons composed cleanly into `Core\Middleware\CsrfHoneypotMiddleware` via constructor dependency injection (DIP / Hollywood Principle).
- **Pure Composite Facades & Strict DIP**: Composite controllers (`AdminAuthAction`, `PageController`, `BlogController`) receive their specialized child actions directly via constructor dependency injection, eliminating concrete `new` instantiations in accordance with the Dependency Inversion Principle (DIP) and Hollywood Principle.
- **Client-Side Clipboard DOM Abstraction**: `assets/js/adapters/DOMAdapter.ts` encapsulates `copyToClipboard()` to manage `navigator.clipboard` access and fallback DOM element creation, eliminating raw `document` queries from UI controllers (`ShareController`).
- **Single-Responsibility Resource Actions**: Blog and educational resources are decomposed into dedicated invokable controllers (`ListResourcesAction`, `ShowResourceCategoryAction`, `ShowResourcePostAction`), with `BlogController` acting as a backward-compatible composite facade.
- **Centralized Rate Limiting Configuration**: `GeneratePdfAction`, `LogInsightApiAction`, and `ProcessAdminLoginAction` query rate limits dynamically from `content/rate_limits.json` via `Services\ConfigService`.
- **Client-Side Telemetry Transport Abstraction**: `assets/js/calculators/AnalyticsLogger.ts` decouples `AnalyticsTransport` (`BeaconFetchTransport`) from `AnalyticsService` page lifecycle observers and debounce timers.
- **GuideViewModelBuilder Service**: `Services\GuideViewModelBuilder` decouples the educational guide presentation model assembly (markdown content parsing, SEO metadata/schema graph compilation, related post lookups, and strategy input hydration) from HTTP view response emission (`GuideRenderer`).
- **Template Serialization Encapsulation**: `Core\InvestmentInputs::toTemplateData()` encapsulates presentation array formatting for calculation parameters, eliminating manual getter unpacking and variable bloat across render actions (`RenderHomeAction`, `GeneratePdfAction`, `GuideRenderer`).
- **Single-Responsibility Page Actions**: Static page routing is split into dedicated, invokable single-action controllers (`RenderAboutAction`, `RenderFaqAction`, `RenderGlossaryAction`, `RenderPrivacyAction`, `RenderTermsAction`).
- **Single-Responsibility Admin Auth Controllers**: Admin authentication is split into dedicated, invokable single-action controllers (`ShowAdminLoginAction`, `ProcessAdminLoginAction`, `ProcessAdminLogoutAction`).
- **SitemapGenerator Service**: `Services\SitemapGenerator` encapsulates canonical URL node and last-modified date aggregation across 5 content layers for `sitemap.xml`, leaving `SitemapController` as a thin HTTP view responder.
- **HomeSchemaBuilder**: `Core\Factories\HomeSchemaBuilder` modularizes monolithic structured data schemas (`SoftwareApplication`, `FinancialProduct`, `WebSite`, `Organization`, `Person`, `HowTo`) away from `SchemaFactory`.
- **TelemetryPruningService**: `Services\TelemetryPruningService` decouples background SQLite data retention pruning from the real-time `AnonymizedInsightLogger` request ingestion cycle.
- **MigrationInterface**: `Core\Database\MigrationInterface` enforces a strict, typed contract for database schema migrations across `DatabaseMigrator`.
- **FilenameSanitizer Service**: `Services\FilenameSanitizer` encapsulates ASCII and Unicode HTTP Content-Disposition header filename generation.
- **HtmlHeadingEnhancer Service**: `Services\HtmlHeadingEnhancer` parses rendered markdown HTML to inject slug IDs and scroll-margin utility classes for SSR deep-linking and Table of Contents (TOC) parity.
- **DIC Reflection Caching**: `Core\Container` and `Core\ActionDispatcher` cache `\ReflectionClass` and `\ReflectionMethod` metadata in memory, eliminating per-request reflection instantiation overhead.

### Rendering Strategy (Client Owns the Component)
To ensure zero-latency feedback (60fps) and eliminate duplicated rendering logic ("The Two Masters Problem"), this repository implements the **"Client Owns the Component"** pattern:
- **PHP's Role:** PHP (`Twig`) serves only the SEO meta-data, base layout, and *empty skeleton containers* for the interactive calculator elements (like the table body and summary metric cards).
- **TypeScript's Role:** Instantly upon page load, `CalculatorApp.ts` computes the initial state using `MathEngine.ts` and surgically hydrates the DOM. As the user interacts with sliders, TypeScript entirely controls the DOM recalculation and rendering without any network/AJAX overhead.

### Architecture & Service Decoupling
- **Full PHP-TypeScript Calculation Parity:** Calculation math (`InvestmentCalculator.php` and `MathEngine.ts`) is strictly synchronized across PHP and TypeScript, including month-by-month compounding and LTCG tax calculations (12.5% tax on gains exceeding ₹1.25 Lakh). Calculation parity is continuously enforced via automated integration tests (`tests/parity_check.php`).
- **DRY Calculation Engine & Zero Duplicate Tax Equations:** Both `ChartManager.ts` and `CalculatorApp.ts` (`updateTable()` and `updateSummaryMetrics()`) directly access `row.ltcg_tax` and `row.post_tax_total` calculated by `MathEngine.ts`, removing duplicate LTCG tax equations across frontend components.
- **Instance-Based Error Handling & Injection:** `ErrorController` provides constructor-injected instance methods (`render404()`, `render500()`) for controller DI dispatching (`BlogController`, `GuideRenderer`), while maintaining static fallbacks for top-level catches in `index.php` and `App.php`.
- **Negative Currency Formatting & Postel's Law:** `CurrencyHelper::formatInr()` handles negative balance amounts and losses cleanly with leading minus signs (e.g. `-₹ 5,000`).
- **Explicit FQCN Routing & Zero Constructor Side-Effects:** `Router.php` resolves action class strings strictly via explicit Fully Qualified Class Names without magic namespace prepending. `App.php` defers routes loading (`routes.php`) from `__construct()` to `boot()`, enforcing side-effect-free object initialization.
- **DOMAdapter Cache & Selector Encapsulation:** `DOMAdapter.ts` provides `getElements()` selector querying, `getViewportHeight()` viewport metric resolution, and explicit `clearCache()` support for dynamic DOM re-rendering and tab switching. 100% of DOM queries across `CalculatorApp.ts`, `TabController.ts`, and `PdfExportController.ts` are routed strictly through `DOMAdapter.ts`.
- **DOMDocument Sitemap Generator (`SitemapController`):** Refactored XML sitemap builder using `DOMDocument` for robust XML node building, formatting, and character escaping (adhering to Postel's Law). Modification dates are resolved cleanly through injected `ViewRenderer` and `BlogRepository` services.
- **Configurable RateLimiter & Robust ViteHelper:** `RateLimiter` accepts configurable base storage directories via constructor, eliminating hardcoded system temp assumptions and silence operator `@` calls. `ViteHelper` supports dual-stack dev host socket resolution (`127.0.0.1` and `localhost`), flexible manifest key normalization (Postel's Law supporting leading slashes, shorthand names, and direct CSS file queries), and dev/production diagnostic fallback safety.
- **Decoupled PDF Stylesheet (`PdfReportStylesheet`):** HTML report CSS generation is cleanly extracted into `PdfReportStylesheet.php` and injected into `PdfReportTemplate.php`, enforcing SRP and decoupling presentation styling from layout assembly.
- **Pristine Architecture & POLA Governance:** Strict single-contract keys (`seo_category`), consistent URL sanitization (`$cleanSlug`), explicit `RenderGuideAction` container bindings, lazy repository initialization, and default `Content-Type` headers enforced across all response streams.
- **Asia/Kolkata (IST) Telemetry & Admin Insights:** Application bootstrapper sets default timezone to `Asia/Kolkata`. `InsightRepository` volume series queries dynamically convert SQLite UTC timestamps to IST (`+5 hours`, `+30 minutes`), reporting all telemetry statistics in Indian Standard Time.
- **PdfGeneratorService (`Services\PdfGeneratorService`):** Dedicated service encapsulating Dompdf configuration and PDF binary stream generation, decoupling HTML template compilation from PDF binary rendering and controller action handling (`GeneratePdfAction`).
- **Unified HTTP Request & JSON Parsing:** `Core\Http\Request` fully encapsulates HTTP inputs, providing `getRawBody()`, `getJsonBody()`, and an enhanced `getParsedBody()` that transparently auto-detects and decodes JSON bodies (The Pit of Success & Postel's Law).
- **Command-Query Separation (CQS) & Lazy Repository Loading:** Data repositories (`GlossaryRepository`, `FaqRepository`, `ConfigService`, `ContentManager`) defer disk file reading and parsing out of constructors into lazy-loading methods, ensuring side-effect-free object instantiation.
- **Tailwind CSS v4 CSS-First Architecture & Full Source Scanning:** `resources/css/input.css` encapsulates all design tokens via `@theme` and enforces full `@source` scanning across `src/Views/`, `src/Core/`, `src/Controllers/`, `content/**/*.md`, `assets/js/`, and `resources/js/`, guaranteeing that dynamic classes in articles and TypeScript controllers are preserved during production Vite bundling.
- **Pure Light-Mode Fintech Theme & Ambient Aurora Glows:** Strict architectural standard mandating 100% pure light-mode UI surfaces across all cards, modals, tables, and widgets (`bg-white/95`, `bg-slate-50`, `border-slate-200`, `text-slate-900`). Dark surfaces (`bg-slate-900`) and inverted cards are forbidden. Ambient depth is rendered using soft pastel aurora background glows (`from-emerald-400/12 via-teal-300/8`) ensuring an open, high-readability, breathable fintech aesthetic.
- **Ergonomic 3-Part Stepper Dock & Quantum Wealth Studio:** Form inputs (`input-range-pair.twig`) employ a decoupled 3-part flex layout where steppers reside outside the numeric input boundary, eliminating digit-squishing and text clipping on mobile devices for high-denomination figures (e.g. `₹ 1.50 Crore`). Summary KPI cards are consolidated into the **Quantum Wealth Prism Ribbon** with zero-CLS height reservations, unified chart command deck, and real-time **Delta DNA Comparison Matrix** (`scenario-comparison.twig`) for side-by-side scenario diffing.
- **Double-Tap & Double-Click Label Reset Ergonomics:** Field `<label>` elements support double-click on desktop and double-tap on mobile touchscreens to instantly reset individual inputs back to factory benchmark defaults with haptic and audio feedback (`SliderManager.ts`).
- **Flat SIP Baseline & Multi-Decade Lumpsum Compounding Integrity:** `ChartManager.ts` calculates the `Flat SIP Baseline (0% Step-Up)` comparison dataset using the explicit user return rate (`rate`), initial lumpsum (`yr1.begin_balance`), and exact month-by-month annuity due compounding ($B_m = (B_{m-1} + C) \times (1 + r/12)$). This eliminates artificial interest rate estimation artifacts and prevents astronomical floating-point overflow explosions on multi-decade horizons with large lumpsums.
- **SEBI & AMFI Regulatory Compounding Parity & Mathematical Solver Standards:** `InvestmentCalculator.php` and `MathEngine.ts` maintain 100% cross-runtime parity across 20 automated multi-decade test vectors (including 40-year horizons, combined 20-year SIP accumulation + 20-year SWP retirement, delay cost `calculateDelayCost`, inflation discounting `calculateInflationDiscount`, required SIP `calculateRequiredSip`, and SWP starting corpus `calculateRequiredStartingCorpusForSwp` reverse solvers). Calculations adhere strictly to SEBI and AMFI standards (nominal monthly compounding $r/1200$, annuity due timing $B_m = (B_{m-1} + C_m - W_m) \times (1 + r/12)$, zero-indexed annual step-up $(1+g)^{y-1}$, and Budget 2024 Section 112A LTCG tax with ₹1,25,000 annual exemption @ 12.5%). All educational guide tables across `/sip-calculator`, `/swp-calculator`, `/sip-step-up-calculator`, `/lumpsum-calculator`, and `/my-first-crore-calculator` are 100% mathematically synchronized with the live computation engine.
- **Dynamic Currency Formatting Parity (`CurrencyHelper`):** `CurrencyFormatterInterface` and `CurrencyHelper.php` implement `formatDynamic()` matching `CurrencyHelper.ts` (`₹1.50 Lakh`, `₹2.50 Crore`, `₹10.5k`), ensuring unified monetary representation across server-side PDF generation and client DOM rendering.
- **Env Wrapper (`Core\Env`):** Centralizes environment variable resolution, guaranteeing that OS-level CLI/testing environment overrides (`getenv`) take precedence over `.env` defaults. Env reads are **exclusively performed at the DI boundary** inside `App::registerDependencies()` — domain services, strategies, and controllers never call `Env::get()` directly.
- **Strongly-Typed Callable Routing:** Routes in `routes.php` and `App.php` use class-string callable tuples (e.g. `[PageController::class, 'about']`), eliminating brittle string-based route definitions and enabling compile-time / static analysis verification. Both `calculators` and `pages` entries use a **uniform tuple syntax**.
- **Immutable HTTP Pipeline & Middleware System:** Controllers strictly return `Core\Http\Response` objects (e.g. via `Response::html()`), separating view compilation from response emitting. `Router` supports a pipeline of `Core\Middleware\MiddlewareInterface` middlewares (such as `CsrfHoneypotMiddleware`), handling security checks like CSRF validation and honeypot bot detection globally before reaching controllers.
- **Dedicated Single-Responsibility Actions:** Specific controller actions like CSV export downloads (`DownloadCsvAction`) and admin authentication handlers (`AdminController::login` & `logout`) are extracted into dedicated, single-responsibility methods and routes (`POST /download-csv`, `POST /admin_insights`), adhering strictly to SRP and REST standards.
- **Externalized Metric Bucketing Configuration:** `InsightRepository` dynamically generates SQL bucket queries using `content/dashboard_buckets.json`, decoupling UI metric ranges and labels from raw SQL string statements.
- **Strict Command-Query Separation (CQS):** `SessionManager::getCsrfToken()` is a pure query method returning string values without mutating state. State-changing token initialization is handled via `ensureCsrfToken()` (Command) called during session bootstrap.
- **ViewRenderer (`Core\ViewRenderer`):** Twig rendering is a proper injectable service catching `\Throwable` errors. `ViewRenderer` receives `SessionManager`, `ViteHelper`, `$env`, and `$appUrl` via constructor and resolves view paths dynamically using Twig's `FilesystemLoader`.
- **PSR-11 Container & Strict DI:** `Core\Container` implements `Psr\Container\ContainerInterface` with `has()` and PSR-11 exception primitives (`NotFoundException`, `ContainerException`). Class name resolution strips leading backslashes (`ltrim($id, '\\')`) to guarantee exact key resolution between callable tuple routes and DI container registrations. 100% of core services (`ActionDispatcher`, `Router`, `AppTwigExtension`, `AdminDashboardPresenter`) are bound in DI providers without inline fallback instantiations.
- **Centralized Milestone Configuration:** Wealth milestone thresholds are centralized in `content/calculator_defaults.json` and consumed by both PHP (`PdfReportTemplate`) and TypeScript (`ChartManager.ts`), eliminating cross-layer duplicate array declarations.
- **StrategyFactory (`Core\Strategies\StrategyFactory`):** Resolves calculator strategies dynamically via `Psr\Container\ContainerInterface`, eliminating redundant manual strategy instantiations in service providers.
- **Pure Analytics Command Logging (`AnalyticsService`):** `AnalyticsService` is 100% free of DOM element queries or window inspection, consuming viewport signals (`table_viewed`, `device_type`) passed explicitly via `ExtraSignals` to enforce Command-Query Separation (CQS). Currency codes are read dynamically via `CurrencyFormatter::getCurrency()`.
- **Decomposed UI Controllers (`ResultsController` & `SummaryMetricsController`):** Frontend table DOM rendering (`ResultsController`) and summary stats tile calculations/font auto-scaling (`SummaryMetricsController`) are extracted from `CalculatorApp.ts` into specialized single-responsibility controllers, with batched query measurement and command styling phases. 100% of DOM element reads and updates are strictly routed through `DOMAdapter.ts`.
- **Centralized SEO & Metadata Single Source of Truth (`MetaManager` & `meta_pages.json`):** All static page metadata (`about`, `faq`, `glossary`, `privacy`, `terms`) is consolidated into `content/meta_pages.json` and served via `MetaManager::getMeta()`, eliminating inline Twig configuration bypasses.
- **PHP-Driven Home Structured Data (`SchemaFactory::generateForHome`):** Home page structured data (`SoftwareApplication`, `FinancialProduct`, `WebSite`, `Organization`, `Person`, `HowTo`, `FAQPage`) is generated dynamically in PHP via `SchemaFactory`, eliminating raw inline JSON-LD script blocks from `home.twig`.
- **Fail-Fast File Modification & Template Resolution:** `ViewRenderer::getTemplateModifiedDate()` and `ContentManager::getFileModifiedDate()` strictly throw `RuntimeException` on missing template or content files rather than swallowing errors with silent date fallbacks, ensuring missing files are immediately flagged during CI/CD.
- **Dynamic Route Metadata & Sitemap Priority (`routes.php` & `SitemapController`):** Sitemap priorities (`priority`) and change frequencies (`changefreq`) are defined directly in `src/Core/Config/routes.php` per route definition, decoupling `SitemapController` from hardcoded path lists.
- **Automated Git Pre-Commit Quality Assurance (`composer setup-hooks`):** Local development environment includes a git pre-commit hook installer (`scripts/setup-hooks.sh`), executing `composer check-all` (PHPStan, PHPCS, and PHPUnit) automatically before allowing commits.
- **Granular Micro-Componentization (`home-guide-content.twig`):** Bloated structural templates are split into focused, single-responsibility components (`guide-definitions`, `guide-how-to`, `math-transparency`, `guide-examples`, `guide-historical-data`, `guide-risks`, `guide-faq`) included by the parent view.
- **State-Driven SWP Disclosure Panel Parity & WCAG Accessibility:** `CalculatorApp.ts` and Twig templates (`calculator-form.twig`, `calculator-guide.twig`) enforce state-driven initial SSR and client hydration parity for disclosure panels (`#swp-fields`). When `enable_swp` is OFF, fields are visually hidden (`display: none`), marked with `aria-hidden="true"`, and child inputs are set to `disabled` for keyboard accessibility.
- **Declarative Bidirectional URL State Engine & Postel's Law Hydration:** `UrlStateController.ts` and `ShareController.ts` implement schema-driven serialization and liberal hydration supporting `inflation`, `cur`, `post_tax`, `wealth_map`, `goal_mode`, `target_corpus`, and mode-aware starting `corpus` without gatekeeper constraints.
- **Full DTO Contract Parity for PDF Export:** `PdfExportController.ts` and `GeneratePdfAction.php` strictly synchronize `enable_swp` and `inflation` form data vectors, ensuring PDF reports render exact SWP distribution cashflows.
- **Kernel Container Factory (`App::createContainer`):** Standardized static DI container bootstrapping method for isolated test suites and CLI tools, guaranteeing zero unresolvable primitive parameters.
- **Query-String Preserving Trailing Slash Redirects:** `TrailingSlashRedirectMiddleware` preserves `$_SERVER['QUERY_STRING']` and restricts 301 canonical redirects strictly to `GET` and `HEAD` methods, ensuring marketing UTM tags and URL parameters are never lost.
- **Excel-Compatible UTF-8 BOM CSV Streaming & DDE Defense:** `CsvExportService` automatically prepends `\xEF\xBB\xBF` Byte Order Marks to raw CSV output streams, guaranteeing correct rendering of Rupee `(₹)` currency symbols in Microsoft Excel across all operating systems. It securely neutralizes CSV Dynamic Data Exchange (DDE) formula injection triggers (`=`, `+`, `-`, `@`, `\t`, `\r`, `|`) by prepending a single quote while preserving valid negative numbers.
- **Dompdf Executive Report Engine & Binary Stream Purity:** `PdfGeneratorService` encapsulates Dompdf rendering with isolated font and temp cache directories (`var/cache/fonts`, `var/cache/dompdf`), disabled remote SSRF resources, and pure CSS 2.1 table styling via `PdfReportStylesheet` (enforcing `font-family: 'DejaVu Sans'` for full Indian Rupee `₹` Unicode glyph coverage). Output buffers and `display_errors` suppression wrap all Dompdf execution to prevent third-party vendor PHP deprecations from leaking into HTTP binary streams, and client-side blob download timeouts (`PdfExportController.ts`) are extended to 60s to guarantee zero file truncation.
- **Robust Canonical Metadata Key Normalization:** `MetaManager::getMeta` normalizes leading and trailing slashes (`trim($pageKey, '/')`), ensuring both `about` and `/about` resolve canonical URLs and meta descriptions deterministically.
- **Client-Side Negative Currency Formatting Parity:** `CurrencyFormatter.ts` handles negative numbers with leading negative prefixes (`-₹ 50 Lakh`), maintaining visual parity with backend `CurrencyHelper.php`.
- **Reverse Proxy Client IP Discovery & Trusted Proxies:** `Request::getClientIp()` validates reverse proxies via `TRUSTED_PROXIES` and inspects `CF-Connecting-IP`, `X-Forwarded-For`, and `Client-IP` headers with `FILTER_VALIDATE_IP` validation, preventing rate-limiting collisions on cloud load balancers.
- **Hierarchical Breadcrumb Category Routing:** `BlogController::category()` and `/resource/{category}` route provide category-filtered archives for complete internal linking without 404 dead ends.
- **Opportunistic Rate-Limiting Garbage Collection:** `FileRateLimitStorage` automatically prunes stale IP JSON files older than 2x the time window to safeguard server disk inodes.
- **Immediate-Withdrawal Standalone SWP Numerical Engine:** `MathEngine.calculateRequiredStartingCorpusForSwp()` calculates starting corpus requirements based strictly on active accumulation duration without artificial Year 1 offsets.
- **SQLite WAL Mode & Concurrency Architecture:** `CoreServiceProvider` automatically configures `PRAGMA journal_mode = WAL;`, `PRAGMA synchronous = NORMAL;`, and `PRAGMA busy_timeout = 5000;` on all SQLite PDO connections, eliminating concurrency lock contention during traffic surges.
- **Session Security & Fixation Protection:** `SessionManager` enforces `HttpOnly` and `SameSite=Lax` cookie attributes, and `AdminAuthService` regenerates session IDs upon login to eliminate session fixation vectors.
- **Lumpsum SSR Initial Input Parity:** `LumpsumStrategy` provisions explicit `getInitialInputs()` with `lumpsum: 500000` and `sip: 0` so SSR renders genuine Lumpsum calculations.
- **Mobile Virtual Keyboard CLS Prevention:** `CalculatorApp` filters `window.resize` events to ignore height-only resizes caused by mobile virtual keyboard deployment.
- **WCAG 2.1 Focus Restoration:** `SmartNudgeController` returns keyboard focus to the trigger button upon popover dismissal.
- **Yearly Breakdown Row Inflation Discounting:** `ResultsController.ts` passes `row.year` into `MathEngine.calculateInflationDiscount()`, ensuring the breakdown table reflects precise per-year compound discounting.
- **Hydration Layout Shift Prevention:** `CurrencyHelper.php` and `CurrencyHelper.ts` standardize on the canonical unspaced Rupee format (`"₹1,00,000"`), eliminating text-shift and CLS during client-side hydration.
- **OWASP CSV Formula Injection Neutralization:** `CsvExportService` automatically sanitizes cells starting with formula characters (`=`, `+`, `-`, `@`, `\t`, `\r`) with single quotes and supports multi-currency column headers.
- **Reverse Proxy SSL Security:** `SessionManager` detects `HTTP_X_FORWARDED_PROTO: https` to set `cookie_secure: true` when running behind Cloudflare or AWS ALBs, and purges `$_SESSION` in memory upon destruction.
- **Container Circular Dependency Detection:** `Container.resolve()` tracks resolving classes via recursion stack, throwing descriptive exceptions on cyclical references.
- **Surgical Micro-Toggletip & Contextual Guidance System:** Deploys accessible, pure light-mode micro-toggletip badges (`ℹ`) on high-cognitive-friction financial concepts (**Auto-Heal Safe SWP Alert**, **Budget 2024 Section 112A LTCG Tax**, **Annual Step-Up Compounding**, **SWP Inflation Escalation**, and **Inflation Discounting**), providing plain-English explanations on desktop hover and mobile single-tap with zero layout shift (0.00 CLS).
- **HTTP 308 Trailing Slash Normalization:** `TrailingSlashRedirectMiddleware` uses RFC 9110 HTTP 308 Permanent Redirect for POST requests, preserving request bodies and HTTP methods.
- **Utility-First Homepage Architecture & Multi-Mode Analytical Studio:** Redesigns the homepage into a 3-zone high-performance layout: (1) Hero Calculator Workspace positioning sliders, live Chart.js visualization, and summary metrics 100% above the fold; (2) Multi-Mode Analytical Studio (`analytical-studio.twig` & `StudioTabController.ts`) housing the Yearly Breakdown Table, Milestone Roadmap (`wealth-roadmap.twig` & `MilestoneCelebrationController.ts`), Black Swan Stress-Test (`stress-test-simulator.twig` & `StressTestController.ts`), City FIRE Benchmark (`city-fire-benchmark.twig` & `CityBenchmarkController.ts`), and Asset Rebalancing (`asset-rebalancing.twig` & `AssetRebalanceController.ts`) in accessible ARIA tab panels with server-rendered DOM persistence for complete SEO indexing. Features a Live Financial Telemetry HUD with real-time computational micro-badging (`#tab-telemetry-breakdown`, `#tab-telemetry-fire`, `#tab-telemetry-milestones`, `#tab-telemetry-stress`, `#tab-telemetry-rebalance`), pure light-mode SVG duotone glyphs, mobile gradient edge-fade masks, and hardware-accelerated smooth auto-scroll centering (`scrollIntoView`). Panel 2 incorporates a real-time Personal FIRE Freedom Radar with dynamic readiness gauges (`#fire-readiness-percent`), status surplus/deficit badges (`#fire-status-badge`), freedom horizon milestone projections (`#fire-horizon-date`), Tri-Tier Indian SWR Multipliers (25×, 30×, 35×), Housing Tenure Deductions (-35% for own home), and Geo-Arbitrage relocation intelligence. Panel 3 delivers a 5-Tier Adaptive Wealth Roadmap (₹10L Seed, ₹25L Ignition, ₹50L Waypoint, ₹1Cr Club, ₹5Cr Freedom) featuring real purchasing power deflators (`.checkpoint-real-value`), live hit counters (`#milestone-hit-counter`), and a Compounding Acceleration Velocity Engine (`#milestone-velocity-text`) demonstrating exponential time compression between milestone unlocks. Panel 4 delivers a behavioral Rupee Cost Averaging (RCA) Conviction Engine with dynamic crash timing epoch selection (Early, Mid, Late), real-time Disciplined SIP vs. Panic Selling delta calculations (`#stress-conviction-gain`, `#stress-path-disciplined`, `#stress-path-panic`), iconic Indian market shock presets (2008 Lehman GFC, 2020 COVID Flash Crash, 2015-16 Midcap Bear), and synchronized master chart overlay telemetry. Panel 5 introduces a Tri-Asset Smart Cashflow Inflow Router (Equity, Debt, Gold) with stacked allocation progress bars (`#rebalance-bar-equity`, `#rebalance-bar-debt`, `#rebalance-bar-gold`), simulated market drift states (`#drift-state-normal`, `#drift-state-bull`, `#drift-state-bear`), and Section 112A Tax Alpha savings estimation (`#rebalance-tax-savings`) demonstrating the monetary gains of zero-redemption cashflow rebalancing; (3) Editorial Knowledge & Research Vault structuring mathematical transparency proofs, category returns, and FAQ accordions with zero layout shift; and (4) Floating Discovery HUD (`floating-discovery-hud.twig`) providing smooth in-page navigation anchors.
- **Privacy-First SEO & Studio Telemetry Pipeline (`AnalyticsLogger.ts`, `InsightPayload.php`, `AnonymizedInsightLogger.php`):** Extends internal anonymous calculation telemetry with organic search attribution (`landing_path`, `referrer_category`, `utm_*`), search intent satisfaction & Helpful Content signals (passive throttled `scroll_depth_pct`, active `dwell_time_seconds`, `quick_answer_viewed`, `faq_item_expanded`, `glossary_term_clicked`), interactive studio signals (`active_studio_tab`, `strategy_starter_used`, `guided_wizard_completed`, `stress_test_scenario`, `city_benchmark_city`, `scenario_diff_saved`), high-intent conversion signals (`pdf_downloaded`, `csv_exported`, `qr_modal_opened`, `tax_waterfall_opened`, `goal_pledge_created`), and real user Core Web Vitals RUM monitoring (`cwv_lcp_ms`, `cwv_cls`, `cwv_inp_ms`, `connection_speed`, `viewport_bucket`) using non-blocking `navigator.sendBeacon` and `PerformanceObserver` with 100% zero main-thread calculation blocking.
- **Institutional Single Source of Truth (SSoT) Design Token & Typography Architecture:** Eliminates fragmented color scales, inconsistent typography hierarchy, and sub-pixel alignment drift by establishing four synchronized, strongly typed token pillars:
  1. **Strict 9-Tier Modular Fluid Typography Scale:** Built in Tailwind CSS v4 `@theme` (`--text-display-2xl`, `--text-display-xl`, `--text-heading-lg`, `--text-heading-md`, `--text-heading-sm`, `--text-body`, `--text-ui-sm`, `--text-ui-xs`, `--text-caption`, `--text-micro`) with preloaded `JetBrains Mono (500, 600, 700, 800)` webfonts for cross-platform zero-jitter lining figures, paired with `Plus Jakarta Sans` geometric display apexes on all primary section headings.
  2. **Institutional Semantic Financial Palette:** Replaces hardcoded arbitrary Tailwind classes with high-contrast, WCAG AAA contrast-locked semantic tokens (`--color-growth`, `--color-payout`, `--color-principal`, `--color-tax`, `--color-inflation`, `--color-warning`), completely purging the conflicting `gray-*` palette in favor of pure Cool Slate (`slate-*`).
  3. **5-Tier Composite Light-Mode Elevation Stack:** Standardizes surface elevation across `--shadow-flat`, `--shadow-subtle`, `--shadow-card`, `--shadow-card-hover`, `--shadow-floating`, `--shadow-modal`, and `@utility fintech-glass-card` incorporating Apple/Linear 1px top-lit specular zenith highlights (`inset 0 1px 0 0 rgba(255, 255, 255, 1)`).
  4. **Financial Data Precision & Tabular Alignment:** Enforces `@utility font-financial-mono` (`tabular-nums lining-nums zero`), standardized `.currency-affix` baseline tracking, and seamless frozen first-column shadow seams with zero sub-pixel text bleed during horizontal scroll. Parity and compliance are strictly validated via automated unit tests in `DesignTokenAuditTest.php` and `TypographyAndThemeTokensTest.php`.
- **Institutional-Grade Canvas & Visualizer Hardening (`ChartManager.ts`, `ChartScrubbingController.ts`, `chart-visualization.twig`):** Implements a high-DPI subpixel canvas scaling engine with a safe 2.5x DPR ceiling, quantized linear gradient bucketing (30px quantizing intervals), and RAF render queue throttling to guarantee 60fps interaction during rapid slider adjustments. Features: (1) Two-Tier Master Command Deck structurally separating core view switching (`Line` vs `Split`) and utility tools from secondary analytical projection lenses (`± Corridor 10–90%`, `§112A LTCG 12.5%`, `Wealth Map Stack`), eliminating jagged multi-row mobile wrapping; (2) Decoupled Mobile Tactile Range Scrubber (`#mobile-chart-scrubber`) with haptic pulse feedback and active timeline bubble (`#scrubber-active-indicator`), resolving thumb occlusion and eliminating mobile canvas scroll-locking; (3) Compounding Ignition Zone plugin with pure light theme aurora gradients highlighting the exact inflection point where annual returns surpass annual SIP contributions; (4) HUD-First Focus Mode routing live hover/scrub telemetry directly into the persistent top Zero-CLS Master HUD (`#chart-telemetry-hud`) with a hairline crosshair, removing intrusive canvas floating box clutter; (5) Time-Travelling Donut Morpher with Bankruptcy/Depletion Sentinels displaying Rose-700 warnings on exhausted SWP portfolios; (6) Dynamic 30-Year X-Axis Auto-Thinning (5-year benchmark steps `Y1`, `Y5`, `Y10`... `Y30`) and right-aligned Indian metric axis ticks (`₹Cr`, `₹L`, `₹k`); and (7) Strict Lens Mutual Exclusivity Protocol between Stacked Wealth Decomposition and Historical Volatility Corridors.
- **Ergonomic Financial Input Architecture (`calculator-form.twig`, `input-range-pair.twig`, `sip-fields.twig`, `swp-fields.twig`):** Refactors the primary investment input deck with: (1) High-Density Unified Input Capsule System with streamlined vertical card padding (`p-2.5 sm:p-3`) and isolated 32px touch steppers (`w-7 h-7 sm:w-8 sm:h-8`), reducing total form footprint by over 180px for zero-scroll desktop visibility; (2) Instant Indian Word Denomination Badges (`#{id}_word_badge`, e.g. `₹25 Thousand / mo`, `₹15.0 Lakh`) eliminating zero-counting anxiety; (3) Clip-Free AMFI Category Return Guidance with smooth inline matrix (Index 12%, Flexi Cap 14%, Hybrid 9%, Debt 7%); (4) Wealth Accelerators Dock featuring Career Appraisal 10% Step-Up Booster (`#apply-10pct-stepup-btn`) and Expected Inflation adjustments; (5) 1-Tap SWP Lifecycle Accumulation Bridge (`#apply-sip-to-swp-btn`) seeding retirement withdrawals with matured SIP corpus; and (6) Strict Pure Light Mode surfaces (`bg-white/95`, `border-slate-200/80`, `bg-slate-50/70`) with soft emerald and teal ambient glows.
- **Unified HTML5 Native Dialog & Ergonomic Modal Architecture (`ModalScrollLockHelper.ts`, `TaxWaterfallController.ts`, `QrShareModalController.ts`, `GoalCommitmentController.ts`):** Unifies all modal overlays under standard HTML5 `<dialog>` semantics with native `.showModal()` / `.close()`, bounding-box click-outside dismiss listeners, and strict Pure Light Mode styling (zero `bg-slate-900` button violations). Features: (1) Responsive Bottom-Sheet Morphing (`fixed inset-x-0 bottom-0 md:inset-0 md:m-auto rounded-t-[32px] md:rounded-3xl`) with tactile grab handles on mobile; (2) 16px iOS Input Zoom Defense (`text-base md:text-sm`) eliminating involuntary Safari viewport zooming on pledge and search inputs; (3) Ultra-High-Contrast QR Quiet-Zone card (`border-2 border-emerald-500/30 bg-white`) with zero-loss parameter sync status; (4) Effective Wealth Retention Gauge (`#tax-modal-retention-pct`, `#tax-modal-effective-rate`) in Section 112A Tax Waterfall; (5) 1-Tap WhatsApp Investment Pledge Dispatcher with rich markdown and scenario URLs; and (6) Top-Anchored Command Palette Spotlight Tray on mobile devices.

### PHPUnit Database & Test Server Isolation
To ensure test runs do not pollute your development environment, PHPUnit is configured with dedicated isolation primitives:
- **Test Database Isolation:** PHPUnit uses `tests/bootstrap.php` which automatically creates and runs migrations on `database/database.test.sqlite` before the suite runs. The test database is automatically unlinked upon shutdown.
- **Integration Test Server Abstraction (`IntegrationTestCase`):** Background local PHP servers (`php -S`) required for end-to-end integration tests are managed by `Tests\Integration\IntegrationTestCase`, automating port binding, OS environment forwarding, and process termination.

---

## Production Deployment

### Automated CI/CD Pipeline (GitHub Actions)

Deployments are entirely automated via GitHub Actions on every push to the `main` branch. The pipeline utilizes a strict **Zero-Downtime Atomic Deployment** architecture.

#### How the Pipeline Works:
1. **Build:** The runner installs PHP dependencies (`composer install --no-dev`), Node dependencies, and compiles the production CSS/JS via Vite (`npm run build`).
2. **Secure Sync:** It connects securely to the server using `shimataro/ssh-key-action` (strict host key verification) and uses `rsync` to upload only the changed files into an isolated, timestamped release directory (e.g., `releases/20260716180000/`).
3. **Atomic Swap:** Once the upload is fully complete, an SSH command instantly swaps the `public_html` symlink from the old release to the new one. Users never experience a broken site mid-deployment.
4. **Post-Deploy:** Database migrations run automatically, and older releases are pruned (keeping only the last 3 for instant rollback capability).

### Deployment Prerequisites

To deploy automatically, you must configure the following **GitHub Secrets**:
- `FTP_SERVER`: The server IP or domain (e.g., `145.79.212.58` or `sipswpcalculator.com`)
- `FTP_USERNAME`: The SSH/FTP username (e.g., `u12345678`)
- `SSH_PRIVATE_KEY`: Your private Ed25519 or RSA key authorized on the server.
- `KNOWN_HOSTS`: The exact server cryptographic fingerprints (generated via `ssh-keyscan`) to prevent Man-In-The-Middle (MITM) attacks.

### Production Checklist
- [ ] No `cdn.tailwindcss.com` references in any Twig file
- [ ] `.htaccess` HTTPS redirect is active
- [ ] Apache `mod_rewrite`, `mod_deflate`, and `mod_expires` are enabled
- [ ] Server `public_html` is correctly converted to a symlink (handled automatically on first run)

---

## SEO & Structured Data

The site includes several modern SEO optimizations built into the core services:
- **Schema markup:** `SoftwareApplication`, `FinancialProduct`, `FAQPage`, `BreadcrumbList`, `Article`, `WebPage` generated dynamically across all calculators and guides.
- **Open Preview & Snippet Directives:** Default `robots` meta directive (`max-snippet:-1, max-image-preview:large, max-video-preview:-1`) allows unconstrained snippet generation and unlocks high-CTR mobile and Google Discover thumbnail cards.
- **Blog Frontmatter & Description Architecture:** All 14 educational resource articles enforce dedicated 120–160 character `meta_desc` tags, validated via automated TDD suite (`BlogPostFrontmatterTest.php`), eliminating reliance on oversized subtitles.
- **Compressed SERP Titles & Intent Differentiation:** Titles across all tools and guides are strictly optimized to 50–58 characters (enforced $\le 65$ chars in `SeoMetadataValidatorTest.php`), preventing desktop (~580px) and mobile (~55 chars) SERP truncation while eliminating keyword cannibalization between the homepage and standalone `/swp-calculator`.
- **Internal Contextual Linking Hub:** Semantic, crawlable link hub on the homepage header channeling PageRank directly to specialized tools (`/swp-calculator`, `/target-corpus-calculator`, etc.).
- **Deferred Third-Party Analytics:** Microsoft Clarity and Ahrefs Analytics deferred via `requestIdleCallback` to protect First Contentful Paint (FCP) and Total Blocking Time (TBT).
- **AI Crawler Guidance (`llms.txt`):** Root-level standard markdown manifest providing explicit navigation, mathematical parameters, and feature summaries for AI assistants and LLM crawlers.
- **Internal Cross-Linking Engine:** Contextual `related-calculators.twig` and `calculator_links.json` graph connecting standalone calculators with high-intent anchor text and zero self-referencing links.
- **Above-The-Fold Activation & Social Proof:** Hero action CTA smoothly targeting `#calculator-heading` paired with live 7-day calculation count telemetry from `InsightRepository` and Free Excel / CSV export callouts.
- **Client-Side Plan Persistence (`save-calculation.ts`):** `localStorage`-backed plan saving with FIFO eviction, zero server roundtrips, and graceful degradation in private browsing mode.
- **Combo Keyword Optimization:** Front-loaded titles, metadata, and structured data targeted specifically for combined SIP + SWP calculator search queries ("SIP and SWP Calculator Together").
- **Internal Linking Engine:** Reusable `related-resources.twig` component generating cross-category links between calculators and guides for optimal PageRank flow.
- **Open Graph & Twitter Cards:** Dynamic `og:type` (`article` on educational guides, `website` on landing/calculator tools), `author`, and Twitter creator attribution.
- **Canonical URLs:** Deterministic canonical URL and meta description rendering per route.
- **Dynamic XML Sitemap (`/sitemap.xml`):** Automatically aggregates canonical calculator, blog, and page routes while strictly filtering out `noindex` routes (e.g. `/privacy`, `/terms`) via route-level `sitemap_exclude` configuration.
- **Search Engine Crawlability (`robots.txt`):** Permits search engine bots to crawl bundled Vite assets (`/dist/assets/`) for accurate page rendering, mobile-friendly scoring, and Core Web Vitals assessment.

---

## Contributing

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/my-change`
3. Run the development server: `npm run dev`
4. Make your changes in `src/` or `assets/`
5. Validate code quality: `composer check-all`
6. Open a Pull Request

---

## Architecture Quality Standards

This codebase is maintained to strict architectural quality standards as documented in `AGENTS.md`. Every subsystem adheres to the following principles:

| Principle | Key Implementation |
|---|---|
| **Modular Dependency Injection** | All DI bindings decoupled into SRP-compliant `ServiceProvider` modules (`CoreServiceProvider`, `RepositoryServiceProvider`, `DomainServiceProvider`, `ControllerServiceProvider`) |
| **SRP Controller Actions** | Single-responsibility action classes (`ShowAdminDashboardAction`, `AdminAuthAction`, `LogInsightApiAction`, `RenderHomeAction`, `GeneratePdfAction`, `DownloadCsvAction`) decoupled from monolithic controllers |
| **Dependency Inversion (DIP)** | Kernel `App::run()` catches domain exceptions (`RouteNotFoundException`) and resolves `ErrorController` via DI container rather than static fallbacks |
| **Strategy Pattern for RateLimiting** | `RateLimiter` delegates persistence to `RateLimitStorageInterface` (`FileRateLimitStorage`), utilizing sharded sub-directories and bounded pruning |
| **Lazy Session Architecture** | Anonymous public GET requests do not initialize PHP sessions, eliminating session locks and enabling CDN edge caching |
| **Trusted Proxy & IP Resolution** | `Request::getClientIp()` validates remote peer headers (`CF-Connecting-IP`, `X-Forwarded-For`) to prevent rate-limit spoofing |
| **CSV DDE Injection Defense** | `CsvExportService` automatically neutralizes spreadsheet formula injection by prefixing non-numeric formula strings |
| **Image EXIF & Polyglot Neutralization** | `FileUploadService` re-encodes uploaded images via GD to strip malicious EXIF metadata and polyglot payloads |
| **Shared Directory Auto-Discovery** | Database and rate-limit storage automatically discover parent shared directory structures across atomic symlink releases |
| **Separation of Concerns (Views)** | `SitemapController` delegates XML rendering to `Views/sitemap.xml.twig` rather than constructing DOM nodes inline |
| **Custom Twig Extensions** | `AppTwigExtension` encapsulates custom Twig filters (`formatInr`, `array_values`, `json_island`) and Vite helper functions |
| **Data Island XSS Protection** | `json_island` Twig filter automatically escapes HTML entities, quotes, and closing tags with `JSON_HEX_*` flags to protect embedded JSON state |
| **LiteSpeed LSAPI Hardening** | Dedicated `.user.ini` file safely enforces Hostinger LSAPI runtime PHP configurations (`display_errors = Off`, `expose_php = Off`, upload limits) |
| **Dompdf Cache Isolation** | Dedicated `var/cache/fonts` and `var/cache/dompdf` directories prevent permission issues on read-only vendor folders across atomic releases |
| **Static Tailwind Class Mapping** | Category accent badges mapped to static utility classes to prevent Tailwind CSS v4 JIT class purging in production |
| **DRY Calculation Engine** | PDF reporting (`GeneratePdfAction` / `PdfReportTemplate`) uses `InvestmentCalculator` cashflow vectors directly without duplicated math loops |
| **Mathematical Engine & Cross-Runtime Parity** | `MathEngine.ts` and `InvestmentCalculator.php` enforce exact sub-rupee alignment across monthly compounding, Budget 2024 Section 112A LTCG taxation, geometric inflation discounting, 40-iteration binary search solvers, and SWP longevity modeling, validated via `tests/parity_check.php` and PHPUnit alignment suites |
| **Single Source of Truth** | LTCG tax rates in `calculator_defaults.json`; `InvestmentInputs` is the only clamping layer for both web and PDF |
| **Explicit > Implicit** | `Router` explicitly resolves routes and supports `HEAD` methods for all `GET` routes without magic URI trimming |
| **Rich Markdown Content Delivery** | `ContentManager` and `Parsedown` render curated repository markdown with embedded callouts, tables, and deep linking IDs |
| **JSON-LD Schema Hex Escaping** | `SchemaHelper` and `SchemaFactory` enforce `JSON_HEX_TAG | JSON_HEX_AMP` on all structured data output to prevent script tag injection |
| **DI Container Reflection & Autowiring** | `Container` provides strict reflection autowiring, detecting circular dependencies (`A -> B -> A`), union/intersection types, variadics, nullable types, and providing `forget()` and `flush()` lifecycle resets |
| **Action Parameter Injection & Coercion** | `ActionDispatcher` matches controller action parameters by typehint (`Request`), exact route slug name, positional index, and default values, with automatic scalar type coercion (`int`, `float`, `bool`) |
| **Comprehensive Unit Test Suite** | 564 unit and integration tests (8,748 assertions) across Container, ActionDispatcher, Env, SiteConfig, DatabaseMigrator, FileRateLimitStorage, AdminAuthService, AdminDashboardPresenter, ShowAdminDashboardAction, CurrencyHelper, IndianNumberParser, NaturalLanguageQueryParser, TaxHarvestingCalculation, RateRealismSubtext, StepupSubtext, DelayCostSummary, SliderCurve, AccessibilityColorContrast, AccessibilityAndTrustArchitecture, LongevityGuardianTest, TaxWaterfallTest, CanvasVisualizerTest, YearlyBreakdownTableTest, TargetCorpusCalculatorTest, SessionManager, AppTwigExtension, ViteHelper, ServiceProviders, Middleware, Repositories, Security, Template Parity, Form Structure Parity, Subsystems, Multi-Asset Allocation, Daily Accrual, Scenario Diff, SEO Telemetry, FaqCoverage, CalculatorFrontmatter, BlogPostFrontmatter, CalculatorLinksValidator, LlmsTxtValidator, MobileLayoutDock, and Cross-Runtime Parity |
| **Mobile Viewport Ergonomics** | Context-scoped `#mobile-action-dock` and `#mobile-sticky-mini-hud` rendering strictly on interactive calculator routes, preventing text occlusion and dead clicks on editorial pages; direction-aware `#mobile-scroll-top-fab` auto-hiding during downward reading to prevent text obstruction; zero-specificity `:where()` base input selectors preserving Tailwind v4 padding utilities (`pl-11`); CSS Grid `minmax(0, 1fr)` and `min-width: 0` constraints preventing editorial callout horizontal overflow; responsive formula title stacking in Mathematical Transparency; single-line swipeable `.no-scrollbar` carousels for Glossary A-Z letter tracks and FAQ category filter chips, reclaiming >80% viewport height on mobile devices |
| **Mathematical Precision Guardrails** | Full encyclopedia of 345 mathematical engine, simulation, financial precision hazards, and parity constraints codified in `.agents/skills/mathematical-precision-guardrails/SKILL.md` |
| **Production Traps Guardrails** | Full encyclopedia of 65 production server, LiteSpeed, CloudLinux, and concurrency traps codified in `.agents/skills/production-traps/SKILL.md` |
| **Dynamic Canvas & Visualizer Architecture** | `ChartManager` encapsulates Vite dynamic chunk bundling, 30px quantized linear gradient caching (`createGradients`), safe DPR clamping (`2.5x`), dual-view mode switcher (multi-stop Growth Curves vs Asset Allocation Doughnut with center-cutout ROI multiplier hero plugin `donutCenterTextPlugin` and SWP Depletion Sentinel), compounding ignition aurora zones (`compoundingIgnitionPlugin`), ₹1 Crore sovereign milestone line (`croreMilestoneLinePlugin`), Bank FD Alpha Delta pill (`fdAlphaDeltaPlugin`), spline milestone beacons (`splineMilestonesPlugin`) plotted directly along the compounding line curve with two-way crosshair/milestone hover synchronization, Section 112A LTCG "Tax Ghost" dynamic gradient area fill between pre-tax and post-tax curve trajectories, accessible keyboard canvas traversal (`tabindex="0"`, `ArrowLeft`/`ArrowRight`, `Home`, `End`) with `A11yAnnouncer` live voiceover announcements, zero-latency dual-mode `requestAnimationFrame` debounced continuous slider scrubbing (`update('none')`), branded high-DPI canvas export pipeline with SEBI/AMFI methodology stamp (`CanvasExportHelper.ts`), Historical Volatility Corridor overlay (10th–90th percentile rolling Nifty returns: 10.2%–15.8% CAGR), accessible WCAG 2.1 AAA canvas patterns and distinct point styles (`rectRot` for Invested, `circle` for Pre-Tax, `triangle` for Post-Tax via `ChartPatternHelper`), historical benchmark overlays (Nifty 50 @ 12%, Gold @ 9%, FD @ 6.5%), custom vertical crosshair line guide plugin, inflation purchasing power curve, and bi-directional table row highlighting bridge |
| **Fintech UI/UX Design System & Ergonomics** | WCAG 2.1 AA/AAA compliant high-contrast color tokens (`#047857` Emerald-700, `#064e3b` Emerald-900, `#be123c` Rose-700), WCAG AAA 7.2:1 subtext metadata tokens (`.field-unit-hint` text-slate-600), 2px offset keyboard focus ring system (`focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2`), Desktop Floating Sticky Summary & Chart Layout (`lg:sticky lg:top-[84px] lg:self-start z-20` with height-matched desktop scrollable left form container `lg:max-h-[500px] xl:max-h-[530px]` eliminating empty dead space below chart on high-resolution displays), Dynamic Morphological Summary Cards Grid (`#summary-cards-grid`: 3-card layout in pure SIP mode, 4-card layout with `#card-withdrawn` in SWP mode), Unified Compounding Alpha Beacon (`#contextual-alpha-radar`) with deterministic priority scoring (Exhaustion Risk $\to$ Wealth Crossover $\to$ Delay Cost $\to$ Doubling Multiplier $\to$ Daily Accrual) and expandable progressive disclosure drawer (`#alpha-drawer-toggle`, `#alpha-secondary-insights`), 2-Tier Unified Master Console (`chart-visualization.twig`) uniting Tier 1 Master Command Dock (Identity & AMFI seal, Segmented Line/Donut Switcher, Analytical Lenses with micro-telemetry, and compact Action buttons) and Tier 2 Persistent Zero-CLS Telemetry HUD (`#chart-telemetry-hud`) cross-fading resting horizon stats and live point-in-time yearly cashflows with exact 0.00 CLS, Bifurcated Operational & Dispatch Hub in Yearly Breakdown Ledger (`yearly-breakdown-table.twig`) separating view density toggles from export actions, Compounding Ignition Ribbon Row insertion (`ResultsController.ts`), inline Wealth Multiplier Badges ($N\times$), sticky Year column with elevation drop-shadows (`sticky left-0 bg-white/98 z-10`), and mobile progressive disclosure accordion (`#mobile-expand-all-years-btn`), Unified AMFI Institutional Micro-Seal with formula verification popover, minimum 44×44px accessible touch target hitboxes on benchmark chips, custom 24px tactile range slider grab handles with active spring scaling, magnetic Indian milestone snapping (`MagneticSnapHelper.ts`), elastic dynamic slider autoscaling (`SliderManager.ts`) for high-ticket inputs (₹2.5L, ₹1Cr) with WAI-ARIA extended keyboard range stepping, global FinTech accessibility hotkeys (`Alt+S` for SIP, `Alt+W` for SWP, `Alt+P` for PDF Plan), trailing debounced live screen-reader announcer (`A11yAnnouncer.ts`, `#calculator-a11y-live-announcer`), mobile soft-keyboard occlusion defense with live projected corpus floating preview capsule (`KeyboardViewportController.ts`), Empathetic SWP Longevity Guardian with 1-tap Safe SWP Auto-Healer (`LongevityGuardianController.ts`, `#swp-longevity-guardian-alert`), 1-Click SIP $\rightarrow$ SWP Continuous Lifecycle Bridge (`LifecycleBridgeController.ts`, `#lifecycle-retirement-bridge`), 1-Tap WhatsApp Investment Proposal Dispatcher with rich markdown formatting (`ShareController.ts`), 10% Corporate Salary Appraisal Step-Up Optimizer Callout (`#salary-stepup-nudge-box`), 4-Column Essential vs 11-Column Full Audit table density switcher, Indian numeral parser (`IndianNumberParser.ts`) supporting Crores, Lakhs, and Thousands, debounced session draft persistence (`SessionStorageController.ts`), 15-state linear undo/redo parameter history buffer, responsive Mobile Milestone Cards view alongside desktop tables, Amortization Ledger Sticky Frozen Year Column, Dedicated Standalone Target Corpus Goal Seek Calculator (`/target-corpus-calculator`), historical crash stress testing, dynamic asset allocation, Budget 2024 Section 112A Interactive Tax Waterfall, and 100% Client-Side Privacy trust signals |
| **Hardened PDF Chart Export Pipeline** | `PdfExportController` draws charts onto white-backed offscreen canvases with animation stopping and HiDPI dimension clamping, preventing transparent black box rendering and staying within `HtmlSanitizer`'s 5MB threshold |
| **CQS Compliance** | `SessionManager::generateCsrfToken()`, `App::boot()`, and `DatabaseMigrator::migrate()` return `void` or explicitly separate state mutation from query methods |
| **Environment Security** | Schema migrations execute strictly via CLI (`bin/migrate`) in deployment pipelines; no administrative web migration endpoints |

---

## License

MIT
