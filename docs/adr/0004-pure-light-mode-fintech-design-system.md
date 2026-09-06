# ADR 0004: Pure Light-Mode FinTech Design System

## Status
Accepted

## Context
Dark-mode financial interfaces and inverted dark cards (`bg-slate-900`, `bg-gray-900`) create visual fragmentation, high eye fatigue under daylight mobile use, poor contrast for complex multi-column ledgers, and an inconsistent brand identity across generated PDF reports and client screens.

## Decision
The application strictly mandates a **Pure Light-Mode FinTech Aesthetic**:
1. All surfaces, cards, modals, and interactive widgets must be styled with light backgrounds (`bg-white/95`, `bg-slate-50`), crisp borders (`border-slate-200`), and dark high-contrast typography (`text-slate-900`, `text-slate-700`).
2. Ambient depth must be rendered using soft, luminous pastel aurora gradients (`emerald-400/12`, `teal-300/8`, `indigo-300/6`, `cyan-200/5`) rather than dark vignette lighting.
3. No dark widgets, inverted cards, or dark theme toggles are permitted in any component or view.

## Consequences
- **Positive:** Uncompromised WCAG AAA readability, clean visual hierarchy, unified styling between web and print/PDF outputs, authoritative financial tool appearance.
- **Negative:** Dark mode preferences are not supported.
