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
│   ├── Core/                 # Framework utilities and App initialization
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
- **Env Wrapper (`Core\Env`):** Centralizes environment variable resolution, guaranteeing that OS-level CLI/testing environment overrides (`getenv`) take precedence over `.env` defaults. Env reads are **exclusively performed at the DI boundary** inside `App::registerDependencies()` — domain services, strategies, and controllers never call `Env::get()` directly.
- **Strongly-Typed Callable Routing:** Routes in `routes.php` and `App.php` use class-string callable tuples (e.g. `[PageController::class, 'about']`), eliminating brittle string-based route definitions and enabling compile-time / static analysis verification. Both `calculators` and `pages` entries now use a **uniform tuple syntax**.
- **Immutable HTTP Pipeline:** Controllers strictly return `Core\Http\Response` objects (e.g. via `Response::html()`), separating view compilation from response emitting. `index.php` instantiates a single `Request` object top-down.
- **ViewRenderer (`Core\ViewRenderer`):** Twig rendering is now a proper injectable service. Previously `View::render()` was a static global facade that silently instantiated `SessionManager` internally. `ViewRenderer` receives `SessionManager`, `ViteHelper`, `$env`, and `$appUrl` via constructor and is registered as a singleton in the DI container. All controllers call `$this->viewRenderer->render()` instead of the static method.
- **PSR-11 Container & Strict DI:** `Core\Container` implements `Psr\Container\ContainerInterface` with `has()` and PSR-11 exception primitives (`NotFoundException`, `ContainerException`). Class name resolution strips leading backslashes (`ltrim($id, '\\')`) to guarantee exact key resolution between callable tuple routes and DI container registrations. Static container singletons (`Container::getInstance()`) have been eliminated in favor of instance-based container lifecycle management (`App::boot()`).
- **Encapsulated HTTP Request Boundary:** `Core\Http\Request` encapsulates superglobals (`$_GET`, `$_POST`, `$_SERVER`, `$_FILES`). Controller actions access uploaded files via `$request->files('advisorLogo')` instead of direct `$_FILES` access.
- **StrategyFactory (`Core\Strategies\StrategyFactory`):** Resolves calculator strategies via an explicit, type-safe `STRATEGY_MAP` constant mapping URI slugs directly to strategy classes (`SipStrategy`, `SwpStrategy`, `LumpsumStrategy`, `ComboStrategy`, `TargetCorpusStrategy`), eliminating magic substring pattern matching.
- **Strict Constructor Dependency Injection:** Services (`ViewRenderer`, `ContentManager`, `SitemapController`, `ConfigService`, `FaqRepository`, `GlossaryRepository`) require mandatory constructor parameters without implicit default instantiations (`new ViteHelper()`, `new Parsedown()`), enforcing the Pit of Success across the dependency graph.
- **Dedicated Content Repositories:** Data access is strictly delegated to dedicated repositories (`BlogRepository`, `FaqRepository`, `GlossaryRepository`, `InsightRepository`). Controller actions never perform raw JSON file reading or compute disk paths directly.
- **Encapsulated Metadata & Template Resolution:** View template and blog post modification dates are resolved via `ViewRenderer::getTemplateModifiedDate()` and `BlogRepository::getPostModifiedDate()`, preventing Law of Demeter violations inside controller actions.
- **Dependency Injection & Repositories:** Controllers (`RenderHomeAction`, `PageController`, `SitemapController`, `BlogController`, `AdminController`) use constructor injection managed by `Core\Container` via Reflection auto-wiring.
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

## License

MIT
