---
name: architecture-nuances
description: Triggers whenever modifying the architecture, creating new pages, handling frontend/backend communication, or working with SEO/schemas in the SIP Calculator project.
---

# Architecture Nuances & Guidelines

When working in this repository, you must be aware of the following hidden nuances to avoid breaking the application's strict requirements.

### 1. The Rendering Strategy (Client Owns the Component)
**Action:** Do not use PHP/Twig (`{% for %}`) to iterate and build HTML for calculator output arrays (like tables or charts). PHP must only serve empty `<tbody id="... ">` skeletons. TypeScript is solely responsible for rendering and updating dynamic DOM components via `CalculatorApp.ts`.

### 2. Zero-Latency Calculation (No AJAX)
**Action:** Never propose or implement AJAX/API endpoints for financial math. All math must execute instantly in the browser via `assets/js/calculators/MathEngine.ts`. 
If you modify formulas, you must verify parity against `tests/MathEngineAlignmentTest.php`.

### 3. Frontend-Backend Communication (Data Island Pattern)
**Action:** Do not inject global JavaScript variables (`window.mode = 'sip'`) or scatter state across `data-*` attributes on HTML tags. The required standard is the **Data Island Pattern**: PHP injects a single `<script type="application/json" id="calculator-app-state">` tag, which TypeScript parses on initialization.

### 4. Extreme Componentization & Siloing
**Action:** Do not use CSS to hide massive forms. Build specific backend Twig components (`sip-fields.twig`) and serve only what the user requested. `CalculatorApp.ts` must be written defensively to default missing DOM elements without throwing errors.

### 5. SEO & Structured Data
**Action:** Every calculator page must inject a highly specific `SoftwareApplication` JSON-LD schema (via `SchemaHelper.php`). Do not use generic names; match the schema name precisely to the URL intent (e.g., "SWP Calculator").

### 6. Asset Bundling, Tailwind CSS v4 & Vite Delivery
**Action:** 
- **Tailwind CSS v4 Configuration:** Tailwind v4 is strictly configured via CSS directives in `resources/css/input.css` (`@theme`, `@source`). Never create `tailwind.config.js` or `postcss.config.js`.
- **Twig Asset Loading Contract:** Every full page layout must declare:
  1. `{{ vite_client() }}` in `<head>` for dev HMR.
  2. `{{ vite_css('resources/js/app.ts') }}` in `<head>` for compiled CSS `<link>` tags.
  3. `<script type="module" src="{{ vite_asset('resources/js/app.ts') }}"></script>` in footer for client JS execution.
- **Dompdf Style Isolation:** Dompdf cannot parse modern Tailwind/CSS variables; PDF styles must strictly reside in `PdfReportStylesheet.php`.

> **Further Reading:** For a deep dive into these rules, always read the root `README.md` file before proposing major changes.

