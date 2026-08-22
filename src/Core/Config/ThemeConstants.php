<?php

declare(strict_types=1);

namespace Core\Config;

/**
 * ThemeConstants
 * Single Source of Truth for PHP backend styling, PDF reports, and server-side visual tokens.
 * Kept in 100% parity with Tailwind CSS v4 input.css and TypeScript ThemeTokens.ts.
 */
final class ThemeConstants
{
    // Typography
    public const FONT_SANS = "'DejaVu Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    public const FONT_HEADING = "'Plus Jakarta Sans', 'Inter', sans-serif";
    public const FONT_MONO = "'JetBrains Mono', monospace";

    // Brand Palette
    public const COLOR_BRAND_500 = '#10b981';
    public const COLOR_BRAND_600 = '#059669';
    public const COLOR_BRAND_700 = '#047857';
    public const COLOR_BRAND_800 = '#065f46';

    // Secondary Indigo
    public const COLOR_INDIGO_500 = '#6366f1';
    public const COLOR_INDIGO_600 = '#4f46e5';

    // Neutrals
    public const COLOR_TEXT_PRIMARY = '#0f172a';
    public const COLOR_TEXT_SECONDARY = '#64748b';
    public const COLOR_TEXT_MUTED = '#94a3b8';
    public const COLOR_BORDER = '#e2e8f0';
    public const COLOR_SURFACE = '#ffffff';
    public const COLOR_SURFACE_MUTED = '#f8fafc';

    // Semantic Financial Colors
    public const COLOR_FINANCIAL_GROWTH = '#047857';
    public const COLOR_FINANCIAL_GROWTH_BG = '#ecfdf5';
    public const COLOR_FINANCIAL_GROWTH_BORDER = '#a7f3d0';

    public const COLOR_FINANCIAL_WITHDRAWAL = '#be123c';
    public const COLOR_FINANCIAL_WITHDRAWAL_BG = '#fff1f2';
    public const COLOR_FINANCIAL_WITHDRAWAL_BORDER = '#fecdd3';

    public const COLOR_FINANCIAL_PRINCIPAL = '#334155';
    public const COLOR_FINANCIAL_PRINCIPAL_BG = '#f8fafc';
    public const COLOR_FINANCIAL_PRINCIPAL_BORDER = '#e2e8f0';

    public const COLOR_FINANCIAL_TAX = '#6d28d9';
    public const COLOR_FINANCIAL_TAX_BG = '#f5f3ff';
    public const COLOR_FINANCIAL_TAX_BORDER = '#ddd6fe';

    public const COLOR_FINANCIAL_INFLATION = '#c2410c';
    public const COLOR_FINANCIAL_INFLATION_BG = '#fff7ed';
    public const COLOR_FINANCIAL_INFLATION_BORDER = '#fed7aa';

    public const COLOR_FINANCIAL_GOLD = '#b45309';
    public const COLOR_FINANCIAL_GOLD_BG = '#fef3c7';
    public const COLOR_FINANCIAL_GOLD_BORDER = '#fde68a';
}
