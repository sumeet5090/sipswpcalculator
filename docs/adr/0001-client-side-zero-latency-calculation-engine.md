# ADR 0001: Client-Side Zero-Latency Calculation Engine

## Status
Accepted

## Context
Financial planning applications that execute calculations via server-side AJAX requests suffer from network latency (100ms–800ms per slider tick), debounce jitter, server CPU saturation during rapid slider dragging, and complete breakage when offline or on unstable mobile networks.

## Decision
All financial math must execute natively within the client browser via TypeScript (`assets/js/calculators/MathEngine.ts` and `assets/js/calculators/engines/*.ts`).
1. Sliders bind directly to in-memory math evaluation loops operating under `requestAnimationFrame`.
2. No network requests (AJAX/Fetch) are permitted during calculator parameter adjustments.
3. Server-side computation (`src/Core/InvestmentCalculator.php` and `src/Core/Math/*.php`) is strictly reserved for initial Server-Side Rendering (SSR), PDF report generation (`GeneratePdfAction`), CSV export streaming (`DownloadCsvAction`), and automated headless parity testing (`tests/parity_check.php`).

## Consequences
- **Positive:** Zero latency (60fps feedback loop), zero network overhead, offline execution capability via Service Worker, and high user retention.
- **Negative:** Dual-runtime maintenance risk (PHP and TypeScript). Mitigated through automated CI cross-runtime parity testing (`SebiAmfiComplianceTest`, `SpecializedEnginesAlignmentTest`, `MathEngineAlignmentTest`).
