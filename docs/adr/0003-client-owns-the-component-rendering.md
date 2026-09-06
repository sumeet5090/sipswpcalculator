# ADR 0003: "Client Owns the Component" Rendering Strategy

## Status
Accepted

## Context
When building hybrid SSR and interactive SPAs, duplicate templating logic ("The Two Masters Problem") frequently emerges: PHP renders a table row via Twig loops, and JavaScript re-implements an identical template string to append new rows on user input. When formatting or columns change, developers update one runtime and forget the other, corrupting UI consistency.

## Decision
This application implements the **"Client Owns the Component"** paradigm:
1. PHP renders the semantic page shell, SEO meta tags, structured data, and *empty skeleton containers* (e.g. `<tbody id="yearly-breakdown-tbody"></tbody>`, empty summary card containers).
2. TypeScript is solely responsible for rendering, formatting, and updating dynamic output nodes via `ResultsController.ts` and `SummaryMetricsController.ts`.
3. Twig never loops over calculation output vectors to render dynamic table rows.

## Consequences
- **Positive:** Eliminates duplicated template code, prevents layout shift during hydration, guarantees 100% synchronization between sliders and displayed ledgers.
- **Negative:** Raw HTML source does not contain pre-rendered table rows. Search engines index editorial content and JSON-LD structured data while users receive instant client-side rendering.
