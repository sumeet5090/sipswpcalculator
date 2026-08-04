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
`content/calculator_defaults.json` is the single source of truth for all calculator bounds, minimum/maximum limits, and default field values. The backend accesses this via `Services\ConfigService` (registered as a singleton in the DI container), avoiding redundant file reads. The JavaScript frontend (`InputValidator.js`) reads this JSON directly.

### Architecture & Service Decoupling
- **ConfigService (`Services\ConfigService`):** Loads and caches JSON configuration defaults across controller/service requests.
- **CsvExportService (`Services\CsvExportService`):** Encapsulates CSV report generation and output delivery, decoupling export logic from `RenderHomeAction`.
- **Dependency Injection & Repositories:** Controllers (`RenderHomeAction`, `PageController`, `SitemapController`) and repositories (`BlogRepository`, `FaqRepository`) use constructor injection managed by `Core\Container` via Reflection auto-wiring.

### PHPUnit Database Isolation
To ensure test runs do not pollute your development database (`database/database.sqlite`), PHPUnit is configured with a dedicated testing database.
- PHPUnit uses `tests/bootstrap.php` which automatically creates and runs migrations on `database/database.test.sqlite` before the suite runs.
- The test database is completely isolated and is automatically deleted upon shutdown when PHPUnit completes execution.

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
