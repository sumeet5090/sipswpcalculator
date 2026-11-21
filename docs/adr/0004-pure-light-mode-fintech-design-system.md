# ADR 0004: Pure Light-Mode FinTech Design System with Disciplined Dual-Engine Palette & Authentic Glassmorphism

## Status
Accepted (Refined 2026-11-21)

## Context
Dark-mode financial interfaces and inverted dark cards (`bg-slate-900`, `bg-gray-900`) create visual fragmentation, high eye fatigue under daylight mobile use, poor contrast for complex multi-column ledgers, and an inconsistent brand identity across generated PDF reports and client screens. Furthermore, early design iterations used generic blue and heavy, oversaturated color masses that caused visual clash and lacked institutional fintech authority.

## Decision
The application strictly mandates an **Understated Executive FinTech Theme (Linear & Stripe Aesthetic with Warm Amber & Authentic Glassmorphism)**:

1. **Pure Light-Mode Surfaces:** All surfaces, cards, modals, and interactive widgets must be styled with light backgrounds (`bg-white/85` to `bg-white/95`, `bg-slate-50`), crisp micro-borders (`border-slate-200/90`, `border-slate-300`), and dark high-contrast typography (`text-slate-900`, `text-slate-700`).
2. **Disciplined Dual-Engine Palette:**
   - **Stage 1 (SIP / Wealth Accumulation):** Institutional Emerald (`emerald-600` / `emerald-700`) representing organic wealth generation.
   - **Stage Transition Bridge:** Multi-stop gradient connector (`from-emerald-600 via-teal-600 to-amber-600`) symbolizing capital maturity transition.
   - **Stage 2 (SWP / Retirement Cashflow & Payouts):** Warm Amber & Golden Harvest (`amber-600` / `amber-700` with high-contrast `amber-900` text on light cards), symbolizing retirement distribution, financial freedom, and living payouts. **Generic blue is completely eliminated.**
   - **Sovereign Instruments (Fixed Deposit):** Sovereign Indigo (`indigo-600` / `indigo-700`, `text-indigo-950`).
3. **Authentic Frosted Glassmorphism:** Sliders, input cards, stage panels, and toolbar strips use frosted glass surfaces (`bg-white/85` to `bg-white/95`, `backdrop-blur-md`, and `backdrop-blur-xl` for modal backdrops), precision micro-borders (`border-slate-200/90`), and specular inner highlights (`box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.9)`).
4. **Ambient Colored Shimmers:** Depth is rendered using soft ambient glow spheres (`emerald-500/12` and `amber-500/12`) embedded behind frosted surfaces rather than dark vignette lighting.
5. **Strict Prohibited Tokens:** Heavy dark surface blocks (`bg-slate-900`, `bg-slate-950`), arbitrary black vignettes, and untyped gray classes (`bg-gray-900`, `*-gray-*`) remain strictly prohibited on card, input, and header surfaces; all neutrals must use Cool Slate (`slate-*`).

## Consequences
- **Positive:** Uncompromised WCAG AAA readability, clean visual hierarchy, unified styling between web and print/PDF outputs, authoritative financial tool appearance, and zero visual fatigue.
- **Negative:** Dark mode preferences are intentionally not supported to guarantee optimal daylight readability and ledger legibility.
