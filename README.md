# SIP & SWP Calculator

A production-grade, server-rendered financial calculator for Systematic Investment Plans (SIP) and Systematic Withdrawal Plans (SWP). Built with PHP, Tailwind CSS v4, and Chart.js.

**Live:** [sipswpcalculator.com](https://sipswpcalculator.com)

---

## Architecture Overview

```
sipswpcalculator/
├── index.php                 # Main calculator (SIP + SWP forms, charts, tables)
├── sip-calculator.php        # SIP educational guide page
├── navbar.php                # Global navigation bar (included via PHP)
├── footer.php                # Global footer (included via PHP)
├── functions.php             # Shared PHP helpers (Indian number formatting)
├── generate-pdf.php          # PDF generation endpoint (AJAX)
├── pdf-report-template.php   # HTML template for branded PDF reports
│
├── script.js                 # Client-side JS (Chart.js init, SWP toggle, CSV/PDF)
├── styles.css                # Custom CSS (glassmorphism, sliders, animations)
│
├── src/
│   └── input.css             # Tailwind v4 source file (@import, @source, @theme)
├── dist/
│   └── tailwind.min.css      # Compiled & minified Tailwind output (committed)
│
├── .htaccess                 # Apache config (HTTPS redirect, clean URLs, caching)
├── sitemap.xml               # XML sitemap for search engines
├── robots.txt                # Crawl directives
├── manifest.json             # PWA web app manifest
│
├── package.json              # Node dependencies + CSS build scripts
├── composer.json             # PHP dependencies (dompdf/dompdf)
├── tailwind.exe              # Standalone Tailwind v4 CLI (Windows)
└── assets/
    └── og-image-main.jpg     # Open Graph social sharing image
```

### How It Works

```
Browser Request
      │
      ▼
  .htaccess ──► Strips .php extensions, enforces HTTPS, sets caching headers
      │
      ▼
  index.php ──► PHP processes form input (or uses defaults)
      │         Runs SIP/SWP compound-interest calculations
      │         Generates yearly breakdown arrays
      │         Renders HTML with embedded chart data (JSON)
      │
      ▼
  Browser ──► styles.css + tailwind.min.css load (layout & design)
      │       Chart.js + script.js load (deferred, non-blocking)
      │       Chart renders from window.chartData
      │       Interactive features: SWP toggle, CSV export, PDF modal
```

### Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| **Server** | PHP 8.x on Apache (XAMPP) | Form processing, calculations, server-side rendering |
| **Styling** | Tailwind CSS v4.2 + custom CSS | Utility classes (Tailwind) + glassmorphism/slider styles (custom) |
| **Charts** | Chart.js 3.7 | Interactive line/area charts for wealth projection |
| **PDF** | DomPDF 2.x | Branded PDF report generation |
| **Routing** | Apache mod_rewrite (.htaccess) | Clean URLs (no `.php` extensions), HTTPS enforcement |

---

## Prerequisites

| Requirement | Version | Check Command |
|---|---|---|
| **PHP** | 8.0+ | `php -v` |
| **Apache** | 2.4+ (with mod_rewrite) | Bundled with XAMPP |
| **Node.js** | 18+ (for CSS builds only) | `node -v` |
| **Composer** | 2.x (for PDF dependency) | `composer -V` |

---

## Local Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/sumeet5090/sipswpcalculator.git
cd sipswpcalculator
```

### 2. Install Dependencies

```bash
# PHP dependencies (DomPDF for PDF generation)
composer install

# Node dependencies (Tailwind CSS)
npm install
```

### 3. Place in Web Server Root

Copy or symlink the project into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\sipswpcalculator\
```

### 4. Start Apache

Open **XAMPP Control Panel** and start **Apache**. The site is now available at:

```
http://localhost/sipswpcalculator/
```

### 5. Start CSS Watch Mode (Development)

While developing, run Tailwind in watch mode so CSS rebuilds automatically when you save any `.php` file:

```bash
npm run css:watch
```

This watches all `.php` files for Tailwind class changes and instantly rebuilds `dist/tailwind.min.css`.

> **Tip:** Keep this terminal open while you code. Every time you add or remove a Tailwind class, the CSS updates within ~100ms.

---

## Build System

### Configuration Compiler
To prevent configuration drift, calculator validation bounds and default values are defined in `src/Core/Config/calculator_defaults.php`. During the build process, a compiler script automatically converts this PHP configuration to `assets/js/calculators/calculator_defaults.json`. This JSON is loaded directly by the ES6 JavaScript files at runtime/bundling.

### Build Commands

| Command | Purpose | Output |
|---|---|---|
| `npm run compile-config` | Compile PHP configuration to JSON | `assets/js/calculators/calculator_defaults.json` |
| `npm run dev` | Run Vite development server | Serves assets locally |
| `npm run build` | Compile configuration and bundle assets | `dist/*` |

### When to Rebuild

You **must** rebuild or run the compilation when you:
- Modify bounds or defaults in `src/Core/Config/calculator_defaults.php`
- Add new frontend dependencies or modify asset layouts
- Make changes to theme variables or classes scanned by Tailwind

### Cache Busting

All CSS and JS files use `filemtime()` for cache-busting:

```php
<link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
```

This means browsers only re-download files when the actual file content changes — not on every page load.

---

## Production Deployment

### Step-by-Step

```bash
# 1. Build production CSS
npm run css:build

# 2. Install PHP dependencies (if not already)
composer install --no-dev --optimize-autoloader

# 3. Upload all files to your web server
#    Make sure dist/tailwind.min.css is included!

# 4. Verify .htaccess is active (Apache mod_rewrite must be enabled)
```

### Production Checklist

- [ ] `npm run css:build` — CSS is freshly compiled and minified
- [ ] `dist/tailwind.min.css` is committed and deployed
- [ ] No `cdn.tailwindcss.com` references in any PHP file
- [ ] `composer install --no-dev` — only production PHP deps
- [ ] `.htaccess` HTTPS redirect is active
- [ ] `sitemap.xml` has no 404 URLs
- [ ] Apache `mod_rewrite`, `mod_deflate`, and `mod_expires` are enabled
- [ ] Database directory permissions (if using SQLite) are writable by the web server

### Environment Variables

The application supports loading configuration via a `.env` file at the root directory.

| Variable | Description | Default |
|---|---|---|
| `ENVIRONMENT` | Application mode (e.g. `development`, `production`). | `development` |
| `DB_PATH` | Absolute path to the SQLite database file. | `__DIR__ / database/database.sqlite` |
| `ADMIN_INSIGHTS_PASSWORD` | Password to access the `/admin_insights` dashboard. | `sipswp_admin_2026!` |

> **Important:** The directory and file defined by `DB_PATH` must be writable by the web server user (e.g., `www-data` or `nobody`) in production to allow recording calculations and user comments.


### Performance Notes

| Asset | Loading Strategy |
|---|---|
| `styles.css` | Standard `<link>` in `<head>` |
| `dist/tailwind.min.css` | Standard `<link>` in `<head>` |
| Chart.js CDN | `<script defer>` — non-render-blocking |
| `script.js` | `<script defer>` — non-render-blocking |
| Google Fonts | Loaded with `display=swap` for fast text rendering |

---

## Key Files Explained

### `index.php` (Main Calculator)
The core of the application. Handles:
- **Standalone Converged Calculator:** Combined SIP, SWP, Lumpsum, and Step-Up calculation logic via a powerful `MathEngine.js` for instant feedback.
- **Standalone SEO Guide Pages:** Dedicated Markdown guides for `/sip-calculator`, `/swp-calculator`, and more, targeting high-volume keywords.
- **Dynamic Meta & Schema Generation:** `GuideRenderer.php` automatically injects `BreadcrumbList`, `Article` (with publisher and OG Image), and `WebPage` (with speakable) JSON-LD structured data.
- **PDF Export Engine:** Server-side PDF snapshotting for user reports.

### `script.js` (Client-Side Logic)
- Bootstraps the modular object-oriented controller classes (`CalculatorApp.js`, `ChartManager.js`, `SliderManager.js`, etc.)
- Dynamic Milestones: Calculates and highlights key retail milestones (e.g. ₹50 Lakhs, ₹1 Crore, ₹5 Crores, and SWP Security cover) directly on the Chart.js timeline and displays interactive badge grids.
- Post-Tax Toggle: Estimates and overlays Indian Mutual Fund LTCG tax rules (12.5% tax on capital gains exceeding ₹1.25 Lakhs) directly into the chart projection and summary metrics.
- SWP toggle: shows/hides SWP form fields, chart lines, and table columns
- CSV export with dynamic filename
- PDF modal: collects branding info, sends AJAX to `generate-pdf.php`
- Currency selector: switches between ₹, $, €

### `styles.css` (Custom Styles)
Hand-written CSS for effects that Tailwind doesn't cover:
- Glassmorphism card effects (`backdrop-filter`, CSS variables)
- Custom range slider thumbs and tracks (emerald for SIP, rose for SWP)
- Gradient text effects
- Float/fade animations
- Mobile-specific overrides

### `.htaccess` (Server Config)
- Forces HTTPS via 301 redirect
- Canonicalizes www → non-www
- Strips `.php` extensions from URLs
- Enables gzip compression (`mod_deflate`)
- Sets browser caching headers (`mod_expires`)
- Security headers (X-Content-Type, X-Frame-Options)

---

## Routing

URLs are clean (no `.php` extensions). The `.htaccess` handles the rewriting:

| URL | File |
|---|---|
| `/` | `index.php` |
| `/sip-calculator` | `sip-calculator.php` |
| `/generate-pdf` | `generate-pdf.php` |

---

## SEO & Structured Data

The site includes several SEO optimizations:

- **Schema markup:** `SoftwareApplication`, `FAQPage` (JSON-LD)
- **Open Graph / Twitter cards** for social sharing
- **Canonical URLs** on every page
- **XML Sitemap** at `/sitemap.xml`
- **robots.txt** allowing all crawlers
- **HTTPS enforcement** via `.htaccess`
- **Author attribution** and `dateModified` in schema

---

## Contributing

1. Fork the repo
2. Create a feature branch: `git checkout -b feature/my-change`
3. Start CSS watch mode: `npm run css:watch`
4. Make your changes
5. Build production CSS: `npm run css:build`
6. Commit both your source files and `dist/tailwind.min.css`
7. Open a Pull Request

---

## License

MIT
