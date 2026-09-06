# ADR 0002: Data Island Pattern for State Hydration

## Status
Accepted

## Context
Passing server configuration, default bounds, and calculation state into client-side JavaScript often leads to global namespace pollution (`window.appConfig = ...`), XSS vulnerabilities via unescaped string injection, or fragmented DOM inspection (`data-*` attributes scattered across arbitrary tags).

## Decision
The application strictly enforces the **JSON Data Island Pattern** for all server-to-client state transmission:
1. PHP serializes template state into a single `<script type="application/json" id="calculator-app-state">` tag.
2. Twig filters the payload through `json_island` (`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`), neutralizing XSS vectors.
3. `CalculatorApp.ts` parses this element once during initialization and dispatches state to decoupled UI controllers.
4. Scattering state across `data-*` attributes or exposing mutable global variables is strictly prohibited.

## Consequences
- **Positive:** Immune to HTML script injection, clean separation of concerns, side-effect-free object initialization, single point of serialization verification in unit tests.
- **Negative:** Client must defensively validate JSON structure on parse. Managed via `InputValidator.ts`.
