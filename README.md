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
│   ├── Core/                 # Framework utilities, ActionDispatcher, Router, and App initialization
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
- **DOMAdapter Cache Clearing:** `DOMAdapter.ts` provides explicit `clearCache()` support for dynamic DOM re-rendering and tab switching.
- **DOMDocument Sitemap Generator (`SitemapController`):** Refactored XML sitemap builder using `DOMDocument` for robust XML node building, formatting, and character escaping (adhering to Postel's Law). Modification dates are resolved cleanly through injected `ViewRenderer` and `BlogRepository` services.
- **Configurable RateLimiter & ViteHelper:** `RateLimiter` accepts configurable base storage directories via constructor, eliminating hardcoded system temp assumptions and silence operator `@` calls. `ViteHelper` accepts configurable dev host and port parameters for flexible dev environments.
- **Modular Data Repositories (`InsightRepository`):** `InsightRepository` aggregates metrics using modular query helper methods (`getOverviewMetrics`, `getVolumeSeries`, `getDistributionMetrics`, `getEngagementMetrics`), adhering strictly to SRP.
- **PdfGeneratorService (`Services\PdfGeneratorService`):** Dedicated service encapsulating Dompdf configuration and PDF binary stream generation, decoupling HTML template compilation from PDF binary rendering and controller action handling (`GeneratePdfAction`).
- **Encapsulated HTTP Request & Body Accessors:** `Core\Http\Request` fully encapsulates HTTP inputs, providing `getRawBody()` and `getJsonBody()` methods to prevent direct `file_get_contents('php://input')` calls across controllers.
- **Command-Query Separation (CQS) & Lazy Repository Loading:** Data repositories (`GlossaryRepository`, `FaqRepository`, `ConfigService`, `ContentManager`) defer disk file reading and parsing out of constructors into lazy-loading methods, ensuring side-effect-free object instantiation.
- **Env Wrapper (`Core\Env`):** Centralizes environment variable resolution, guaranteeing that OS-level CLI/testing environment overrides (`getenv`) take precedence over `.env` defaults. Env reads are **exclusively performed at the DI boundary** inside `App::registerDependencies()` — domain services, strategies, and controllers never call `Env::get()` directly.
- **Strongly-Typed Callable Routing:** Routes in `routes.php` and `App.php` use class-string callable tuples (e.g. `[PageController::class, 'about']`), eliminating brittle string-based route definitions and enabling compile-time / static analysis verification. Both `calculators` and `pages` entries now use a **uniform tuple syntax**.
- **Immutable HTTP Pipeline & Middleware System:** Controllers strictly return `Core\Http\Response` objects (e.g. via `Response::html()`), separating view compilation from response emitting. `Router` supports a pipeline of `Core\Middleware\MiddlewareInterface` middlewares (such as `CsrfHoneypotMiddleware`), handling security checks like CSRF validation and honeypot bot detection globally before reaching controllers.
- **Dedicated Single-Responsibility Actions:** Specific controller actions like CSV export downloads (`DownloadCsvAction`) and admin authentication handlers (`AdminController::login` & `logout`) are extracted into dedicated, single-responsibility methods and routes (`POST /download-csv`, `POST /admin_insights`), adhering strictly to SRP and REST standards.
- **Externalized Metric Bucketing Configuration:** `InsightRepository` dynamically generates SQL bucket queries using `content/dashboard_buckets.json`, decoupling UI metric ranges and labels from raw SQL string statements.
- **Strict Command-Query Separation (CQS):** `SessionManager::getCsrfToken()` is a pure query method returning string values without mutating state. State-changing token initialization is handled via `ensureCsrfToken()` (Command) called during session bootstrap.
- **ViewRenderer (`Core\ViewRenderer`):** Twig rendering is now a proper injectable service. Previously `View::render()` was a static global facade that silently instantiated `SessionManager` internally. `ViewRenderer` receives `SessionManager`, `ViteHelper`, `$env`, and `$appUrl` via constructor and is registered as a singleton in the DI container. All controllers call `$this->viewRenderer->render()` instead of the static method.
- **PSR-11 Container & Strict DI:** `Core\Container` implements `Psr\Container\ContainerInterface` with `has()` and PSR-11 exception primitives (`NotFoundException`, `ContainerException`). Class name resolution strips leading backslashes (`ltrim($id, '\\')`) to guarantee exact key resolution between callable tuple routes and DI container registrations. Static container singletons (`Container::getInstance()`) have been eliminated in favor of instance-based container lifecycle management (`App::boot()`).
- **StrategyFactory (`Core\Strategies\StrategyFactory`):** Resolves calculator strategies via an explicit, type-safe `STRATEGY_MAP` constant mapping URI slugs directly to strategy classes (`SipStrategy`, `SwpStrategy`, `LumpsumStrategy`, `ComboStrategy`, `TargetCorpusStrategy`), eliminating magic substring pattern matching.
- **Strict Constructor Dependency Injection:** Services (`ViewRenderer`, `ContentManager`, `SitemapController`, `ConfigService`, `FaqRepository`, `GlossaryRepository`, `PdfGeneratorService`) require mandatory constructor parameters without implicit default instantiations, enforcing the Pit of Success across the dependency graph.
- **Dedicated Content Repositories:** Data access is strictly delegated to dedicated repositories (`BlogRepository`, `FaqRepository`, `GlossaryRepository`, `InsightRepository`). Controller actions never perform raw JSON file reading or compute disk paths directly.
- **Parameterized Database Queries (`InsightRepository`):** SQL queries in `InsightRepository` strictly use parameterized PDO bindings for all dynamic values, conditions, and filters, eliminating raw string concatenation and enforcing Postel's Law and security standards.
- **Decomposed Report Generator (`PdfReportTemplate` & `HtmlSanitizer`):** Dompdf report HTML generation is decomposed into modular section renderers (`renderHeader`, `renderKpis`, `renderParameters`, `renderMilestones`, `renderTable`, `renderFooter`). Table and text HTML sanitization is extracted into a standalone `Services\HtmlSanitizer` service. Key wealth milestone calculations directly consume pre-computed calculator output vectors to maintain DRY principles.
- **DOM & ARIA Hardening (`CalculatorApp`, `ChartManager`):** Frontend DOM rendering uses structured `document.createElement` node construction for milestone grids, eliminating latent XSS vectors. ARIA accessibility attributes on tab controls strictly manage state transitions.
- **Strict TypeScript & Full Parity:** `MathEngine.ts` precision is fully aligned with `InvestmentCalculator.php`. All event topics, timers, chart configurations, and strategy contracts (`abstract class CalculatorStrategy`) are strongly typed without `any` bypasses.
- **Dependency Injection & Repositories:** Controllers (`RenderHomeAction`, `DownloadCsvAction`, `PageController`, `SitemapController`, `BlogController`, `AdminController`, `GeneratePdfAction`) use constructor injection managed by `Core\Container` via Reflection auto-wiring.
- **Session Lifecycle & Pure Twig Templates:** Session initialization is strictly centralized in `App::run()`, and template rendering is 100% handled via Twig with legacy PHP view rendering eliminated.

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
| **Modular Dependency Injection** | All DI bindings decoupled into SRP-compliant `ServiceProvider` modules (`CoreServiceProvider`, `RepositoryServiceProvider`, `ControllerServiceProvider`) |
| **Strategy Pattern for RateLimiting** | `RateLimiter` delegates persistence to `RateLimitStorageInterface` (`FileRateLimitStorage`), enabling pluggable Redis/Memcached backends |
| **Separation of Concerns (Views)** | `SitemapController` delegates XML rendering to `Views/sitemap.xml.twig` rather than constructing DOM nodes inline |
| **Encapsulated Upload Handling** | `FileUploadService` handles image upload validation & Base64 encoding without error suppression operators |
| **Custom Twig Extensions** | `AppTwigExtension` encapsulates custom Twig filters (`formatInr`, `array_values`) and Vite helper functions |
| **DRY** | `InvestmentInputs::resolveField()` for all field clamping; `ContentManager` centralized for Markdown processing |
| **Single Source of Truth** | LTCG tax rates in `calculator_defaults.json`; `InvestmentInputs` is the only clamping layer for both web and PDF |
| **Explicit > Implicit** | `Router` normalises URIs before redirect and route lookup; no silent null injection for unresolvable action params |
| **CQS & Constructor Safety** | Constructors are free of filesystem side effects (`mkdir`); `ViewRenderer` uses decoupled Twig caching |
| **Fail-Safe Security** | `RateLimiter` throws on storage directory failure rather than silently bypassing rate limits |
| **PSR-11 Compliance** | `Container::has()` verifies class instantiability via reflection before returning `true` |
| **Testability** | `DatabaseMigrator::bootstrap()` separated from `migrate()` for clean test isolation; `ViteHelper` accepts injected manifest path |

---

## License

MIT
