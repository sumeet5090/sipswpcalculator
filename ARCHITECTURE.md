# SIP & SWP Calculator — Architecture & Developer Guide

This document outlines the core architectural paradigms, design decisions, and "hidden nuances" of the SIP & SWP Calculator platform. 

Whether you are a human developer or an AI Agent jumping into this codebase, **you must read and adhere to these principles** to ensure you do not break the strict SEO structure or introduce technical debt.

---

## 1. The Dual Rendering Engine (PHP + Twig)
This project uses a hybrid rendering approach. 
- **Raw PHP (`src/Views/layouts/layout.php`)**: Handles the legacy, hyper-optimized markdown wrapper (`generic-post.php`) used for parsing guides and blog content.
- **Twig (`src/Views/layouts/base.twig`)**: Handles modern, highly-componentized views like the Homepage (`home.twig`) and dynamic resource archives (`resources.twig`).

**Rule:** When adding site-wide structural changes (like editing the header, footer, or navigation), you **must** mirror your changes across *both* the `.php` layouts and `.twig` layouts to ensure visual and structural parity across the entire platform.

---

## 2. Zero-Latency Calculation Paradigm
The core value proposition of this product is its instant, zero-latency feedback loop when users adjust sliders.
- **No AJAX for Math:** NEVER replace the web frontend's native `MathEngine.js` computations with AJAX/API calls. 
- **MathEngine.js:** All financial mathematics (future value of annuity due, compound interest, step-up algorithms) must be executed in the browser via `assets/js/MathEngine.js` and `assets/js/app.js`.
- **Parity Checking:** Any changes to the core financial formulas must be verified against `tests/parity_check.php` to ensure the frontend JavaScript logic perfectly matches backend unit-test expectations.

---

## 3. Frontend-Backend Communication (HTML5 Data Attributes)
We do not use Single Page Application (SPA) frameworks (like React/Next.js) because of the overhead. We use Vanilla JavaScript.
- **No Global Variable Injection:** Do not inject loose configuration variables into the `window` object (e.g., `<script>window.mode = 'sip';</script>`).
- **The Standard:** Use **HTML5 Data Attributes** on root container elements. The backend (Twig/PHP) attaches state (e.g., `<div id="calculator-app" data-mode="sip">`), and the Vanilla JavaScript reads this dataset natively upon initialization.

---

## 4. Extreme Componentization & SEO Siloing
To capture high-intent SEO traffic (e.g., "SWP Calculator" vs. "SIP Calculator"), the application uses **Extreme Componentization**.
- **No CSS Hiding:** Do not render a massive, monolithic calculator form and use JavaScript/CSS to hide irrelevant fields. This bloats the DOM and confuses Google's crawlers about the page's true intent.
- **Tailored DOMs:** Break forms into isolated components (e.g., `sip-fields.twig`, `swp-fields.twig`). When serving the `/sip-calculator` route, the backend must assemble and serve *only* the SIP inputs.
- **JS Resilience:** `MathEngine.js` and `app.js` must be written defensively. If a DOM element (like `#swp_withdrawal`) is missing because the backend didn't render it, the JS must gracefully default to `0` and continue executing without throwing null reference errors.

---

## 5. SEO & Structured Data (JSON-LD)
SEO is the lifeblood of this platform. The architecture is heavily optimized for E-E-A-T (Experience, Expertise, Authoritativeness, and Trustworthiness).
- **SchemaHelper.php:** All structured data is generated dynamically via `src/Core/SchemaHelper.php`. 
- **SoftwareApplication Schema:** Any page functioning as a calculator MUST inject a tailored `SoftwareApplication` schema explicitly matching the URL intent (e.g., name: "SWP Calculator").
- **Article Schema:** Markdown guides rendered via `GuideRenderer.php` automatically inject `Article` and `WebPage` schemas.
- **Internal Linking:** Footer and Header navigation structures are designed to heavily distribute link equity to the "money pages" (the calculators). Do not remove these navigational links.

---

## 6. Content Architecture (Markdown)
All educational guides, blog posts, and text-heavy calculator pages are authored in Markdown (`.md`) inside the `content/` directory.
- `ContentManager.php` parses the markdown and extracts YAML frontmatter (for Titles, Subtitles, and Metadata).
- `BlogRepository.php` dynamically queries these files to build the `/resources` archive, utilizing custom tags and read-time calculations.

---

## 7. Build & Testing Requirements
Before committing any changes or finalizing an AI task:
- You must run `composer check-all` to execute PHPStan (static analysis) and PHPUnit (unit tests).
- If the build fails due to linting or test errors, prioritize fixing the issue before submitting the code.
- Frontend assets (Tailwind CSS, JS) must be built via `npm run dev` (development) or `npm run build` (production).
