---
name: architecture-nuances
description: Triggers whenever modifying the architecture, creating new pages, handling frontend/backend communication, or working with SEO/schemas in the SIP Calculator project.
---

# Architecture Nuances & Guidelines

When working in this repository, you must be aware of the following hidden nuances to avoid breaking the application's strict requirements.

### 1. The Dual Engine
This repository uses both raw PHP (`generic-post.php`) and Twig (`base.twig`) layouts. 
**Action:** Any changes to the header, footer, or navigation must be mirrored in both `.php` and `.twig` layout files to maintain parity.

### 2. Zero-Latency Calculation (No AJAX)
**Action:** Never propose or implement AJAX/API endpoints for financial math. All math must execute instantly in the browser via `assets/js/MathEngine.js`. 
If you modify formulas, you must verify parity against `tests/parity_check.php`.

### 3. Frontend-Backend Communication
**Action:** Do not inject global JavaScript variables (`window.mode = 'sip'`). The required standard is HTML5 Data Attributes (`<div data-mode="sip">`). The Vanilla JS must read the DOM dataset directly.

### 4. Extreme Componentization & Siloing
**Action:** Do not use CSS to hide massive forms. Build specific backend Twig components (`sip-fields.twig`) and serve only what the user requested. `app.js` must be written defensively to default missing DOM elements to 0 without throwing errors.

### 5. SEO & Structured Data
**Action:** Every calculator page must inject a highly specific `SoftwareApplication` JSON-LD schema (via `SchemaHelper.php`). Do not use generic names; match the schema name precisely to the URL intent (e.g., "SWP Calculator").

> **Further Reading:** For a deep dive into these rules, always read the root `ARCHITECTURE.md` file before proposing major changes.
