/**
 * ThemeTokens.ts
 * Single Source of Truth for frontend JavaScript & Canvas styling constants.
 * Strictly aligned with Tailwind CSS v4 design tokens in input.css and pure light fintech theme.
 */

export const THEME_FONTS = {
    sans: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    heading: '"Plus Jakarta Sans", "Inter", sans-serif',
    mono: '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
} as const;

export const THEME_COLORS = {
    // Brand Primary Emerald Palette
    brand: {
        50: '#ecfdf5',
        100: '#d1fae5',
        200: '#a7f3d0',
        300: '#6ee7b7',
        400: '#34d399',
        500: '#10b981',
        600: '#059669',
        700: '#047857',
        800: '#065f46',
        900: '#064e3b',
    },

    // Secondary Indigo Palette
    indigo: {
        50: '#eef2ff',
        100: '#e0e7ff',
        200: '#c7d2fe',
        500: '#6366f1',
        600: '#4f46e5',
        700: '#4338ca',
        900: '#312e81',
    },

    // Neutral Slate Palette
    slate: {
        50: '#f8fafc',
        100: '#f1f5f9',
        200: '#e2e8f0',
        300: '#cbd5e1',
        400: '#94a3b8',
        500: '#64748b',
        600: '#475569',
        700: '#334155',
        800: '#1e293b',
        900: '#0f172a',
    },

    // Semantic Financial Colors (Calibrated for High Contrast & WCAG AA)
    financial: {
        invested: '#6366f1', // Indigo 500
        growth: '#10b981',   // Emerald 500
        growthDark: '#047857', // Emerald 700
        withdrawal: '#f43f5e', // Rose 500
        withdrawalDark: '#be123c', // Rose 700
        postTax: '#8b5cf6',  // Purple 500
        inflation: '#c2410c', // Orange 700
        tax: '#6d28d9',      // Purple 700
        milestoneGold: '#fbbf24', // Amber 400
        milestoneGoldDark: '#b45309', // Amber 700
    },

    // Chart.js Canvas & HUD Theme Constants
    chart: {
        gridLine: 'rgba(0, 0, 0, 0.05)',
        textMuted: '#64748b',
        textDark: '#0f172a',
        pointBgWhite: '#ffffff',
        tooltipBg: 'rgba(15, 23, 42, 0.95)',
        tooltipTitle: '#f8fafc',
        tooltipBody: '#cbd5e1',
        tooltipBorder: 'rgba(255, 255, 255, 0.12)',
        gradientInvestedTop: 'rgba(99, 102, 241, 0.22)',
        gradientInvestedMid: 'rgba(99, 102, 241, 0.05)',
        gradientInvestedBottom: 'rgba(99, 102, 241, 0.0)',
        gradientCorpusTop: 'rgba(16, 185, 129, 0.35)',
        gradientCorpusMid: 'rgba(16, 185, 129, 0.10)',
        gradientCorpusBottom: 'rgba(16, 185, 129, 0.0)',
        gradientPostTaxTop: 'rgba(139, 92, 246, 0.25)',
        gradientPostTaxMid: 'rgba(139, 92, 246, 0.05)',
        gradientPostTaxBottom: 'rgba(139, 92, 246, 0.0)',
        swpFillBg: 'rgba(244, 63, 94, 0.10)',
        milestoneLineActive: 'rgba(16, 185, 129, 0.45)',
        milestoneLineSubtle: 'rgba(16, 185, 129, 0.30)',
    },

    // Confetti / Celebration Palette
    celebration: [
        '#10b981', // Emerald 500
        '#059669', // Emerald 600
        '#34d399', // Emerald 400
        '#f59e0b', // Amber 500
        '#fbbf24', // Amber 400
        '#6366f1', // Indigo 500
    ],
} as const;
