# SIP & SWP Calculator

A production-grade, server-rendered financial calculator for Systematic Investment Plans (SIP) and Systematic Withdrawal Plans (SWP). Features a fully bi-directional calculation engine (back-calculates Monthly SIP required to hit a Target Corpus or sustain a target SWP retirement plan), progressive disclosure of advanced inputs, and viewport-aware responsive scaling. Built with PHP (MVC), Twig, Vite, Tailwind CSS v4, and Chart.js.

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

### Calculator Configuration Source of Truth
`content/calculator_defaults.json` is the single source of truth for all calculator bounds, minimum/maximum limits, and default field values. The backend accesses this via `Services\ConfigService` (registered as a singleton in the DI container), avoiding redundant file reads. The TypeScript frontend (`InputValidator.ts`) reads this JSON from a `<script type="application/json" id="calculator-app-state">` Data Island element injected into the HTML.

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
- **Multi-Format Blog Date Ingestion:** `BlogRepository` implements a multi-format date parser cascade (`F Y`, `Y-m-d`, `d F Y`, `Y-m`, `M Y`), supporting diverse frontmatter date conventions without fallback corruption (Postel's Law).
- **Tailwind CSS v4 CSS-First Architecture & Full Source Scanning:** `resources/css/input.css` encapsulates all design tokens via `@theme` and enforces full `@source` scanning across `src/Views/`, `src/Core/`, `src/Controllers/`, `content/**/*.md`, `assets/js/`, and `resources/js/`, guaranteeing that dynamic classes in articles and TypeScript controllers are preserved during production Vite bundling.
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
- **Granular Micro-Componentization (`home-guide-content.twig`):** Bloated structural templates are split into focused, single-responsibility components (`guide-definitions`, `guide-how-to`, `guide-formulas`, `guide-examples`, `guide-historical-data`, `guide-risks`, `guide-faq`) included by the parent view.
- **State-Driven SWP Disclosure Panel Parity & WCAG Accessibility:** `CalculatorApp.ts` and Twig templates (`calculator-form.twig`, `calculator-guide.twig`) enforce state-driven initial SSR and client hydration parity for disclosure panels (`#swp-fields`). When `enable_swp` is OFF, fields are visually hidden (`display: none`), marked with `aria-hidden="true"`, and child inputs are set to `disabled` for keyboard accessibility.
- **Declarative Bidirectional URL State Engine & Postel's Law Hydration:** `UrlStateController.ts` and `ShareController.ts` implement schema-driven serialization and liberal hydration supporting `inflation`, `cur`, `post_tax`, `wealth_map`, `goal_mode`, `target_corpus`, and mode-aware starting `corpus` without gatekeeper constraints.
- **Full DTO Contract Parity for PDF Export:** `PdfExportController.ts` and `GeneratePdfAction.php` strictly synchronize `enable_swp` and `inflation` form data vectors, ensuring PDF reports render exact SWP distribution cashflows.
- **Kernel Container Factory (`App::createContainer`):** Standardized static DI container bootstrapping method for isolated test suites and CLI tools, guaranteeing zero unresolvable primitive parameters.
- **Query-String Preserving Trailing Slash Redirects:** `TrailingSlashRedirectMiddleware` preserves `$_SERVER['QUERY_STRING']` and restricts 301 canonical redirects strictly to `GET` and `HEAD` methods, ensuring marketing UTM tags and URL parameters are never lost.
- **Excel-Compatible UTF-8 BOM CSV Streaming:** `CsvExportService` automatically prepends `\xEF\xBB\xBF` Byte Order Marks to raw CSV output streams, guaranteeing correct rendering of Rupee `(₹)` currency symbols in Microsoft Excel across all operating systems.
- **Robust Canonical Metadata Key Normalization:** `MetaManager::getMeta` normalizes leading and trailing slashes (`trim($pageKey, '/')`), ensuring both `about` and `/about` resolve canonical URLs and meta descriptions deterministically.
- **Client-Side Negative Currency Formatting Parity:** `CurrencyFormatter.ts` handles negative numbers with leading negative prefixes (`-₹ 50 Lakh`), maintaining visual parity with backend `CurrencyHelper.php`.
- **Reverse Proxy Client IP Discovery:** `Request::getClientIp()` inspects `CF-Connecting-IP`, `X-Forwarded-For`, and `Client-IP` headers with `FILTER_VALIDATE_IP` validation, preventing rate-limiting collisions on cloud load balancers.
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
- **HTTP 308 Trailing Slash Normalization:** `TrailingSlashRedirectMiddleware` uses RFC 9110 HTTP 308 Permanent Redirect for POST requests, preserving request bodies and HTTP methods.

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
- **Schema markup:** `SoftwareApplication`, `FinancialProduct`, `FAQPage`, `BreadcrumbList`, `Article`, `WebPage` generated dynamically.
- **Combo Keyword Optimization:** Front-loaded titles, metadata, and structured data targeted specifically for combined SIP + SWP calculator search queries ("SIP and SWP Calculator Together").
- **Internal Linking Engine:** Reusable `related-resources.twig` component generating cross-category links between calculators and guides for optimal PageRank flow.
- **Open Graph / Twitter cards** for social sharing.
- **Canonical URLs** and dynamic meta title/description rendering per route.
- **Dynamic XML Sitemap** (`/sitemap.xml`) and **robots.txt**.

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
| **Single Source of Truth** | LTCG tax rates in `calculator_defaults.json`; `InvestmentInputs` is the only clamping layer for both web and PDF |
| **Explicit > Implicit** | `Router` explicitly resolves routes and supports `HEAD` methods for all `GET` routes without magic URI trimming |
| **Rich Markdown Content Delivery** | `ContentManager` and `Parsedown` render curated repository markdown with embedded callouts, tables, and deep linking IDs |
| **JSON-LD Schema Hex Escaping** | `SchemaHelper` and `SchemaFactory` enforce `JSON_HEX_TAG | JSON_HEX_AMP` on all structured data output to prevent script tag injection |
| **DI Container Reflection & Autowiring** | `Container` provides strict reflection autowiring, detecting circular dependencies (`A -> B -> A`), union/intersection types, un-typehinted parameters, and providing `forget()` and `flush()` lifecycle resets |
| **Action Parameter Injection** | `ActionDispatcher` matches controller action parameters by typehint (`Request`), exact route slug name, positional index, and default values |
| **Environment Precedence Hierarchy** | `Env::get()` enforces `$_ENV` > `$_SERVER` > `getenv()` > `$default` with empty string fallthrough |
| **Comprehensive Unit Test Suite** | 200+ unit and integration tests across Container, ActionDispatcher, Env, ServiceProviders, Middleware, Repositories, Security, and Parity |
| **Production Traps Guardrails** | Full encyclopedia of 65 production server, LiteSpeed, CloudLinux, and concurrency traps codified in `.agents/skills/production-traps/SKILL.md` |
| **CQS Compliance** | `SessionManager::generateCsrfToken()`, `App::boot()`, and `DatabaseMigrator::migrate()` return `void` or explicitly separate state mutation from query methods |
| **Environment Security** | Schema migrations execute strictly via CLI (`bin/migrate`) in deployment pipelines; no administrative web migration endpoints |

---

## License

MIT
