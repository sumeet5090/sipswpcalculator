/**
 * ThemeTokens.ts
 * Single Source of Truth for frontend JavaScript & Canvas styling constants.
 * Dynamically hydrates from the server-injected #calculator-app-state Data Island with static defaults.
 */

export interface ThemeColorsSchema {
    brand: Record<string, string>;
    indigo: Record<string, string>;
    slate: Record<string, string>;
    financial: {
        invested: string;
        growth: string;
        growthDark: string;
        growthBg?: string;
        growthBorder?: string;
        withdrawal: string;
        withdrawalDark: string;
        withdrawalBg?: string;
        withdrawalBorder?: string;
        principal: string;
        principalDark?: string;
        principalBg?: string;
        principalBorder?: string;
        postTax: string;
        inflation: string;
        inflationBg?: string;
        inflationBorder?: string;
        tax: string;
        taxBg?: string;
        taxBorder?: string;
        milestoneGold: string;
        milestoneGoldDark: string;
        milestoneGoldBg?: string;
        milestoneGoldBorder?: string;
    };
    chart: {
        gridLine: string;
        textMuted: string;
        textDark: string;
        pointBgWhite: string;
        tooltipBg: string;
        tooltipTitle: string;
        tooltipBody: string;
        tooltipBorder: string;
        gradientInvestedTop: string;
        gradientInvestedMid: string;
        gradientInvestedBottom: string;
        gradientCorpusTop: string;
        gradientCorpusMid: string;
        gradientCorpusBottom: string;
        gradientPostTaxTop: string;
        gradientPostTaxMid: string;
        gradientPostTaxBottom: string;
        swpFillBg: string;
        milestoneLineActive: string;
        milestoneLineSubtle: string;
    };
    celebration: string[];
}

export interface ThemeFontsSchema {
    sans: string;
    heading: string;
    mono: string;
}

export interface ThemeTokensSchema {
    fonts: ThemeFontsSchema;
    colors: ThemeColorsSchema;
}

export const DEFAULT_THEME_FONTS: ThemeFontsSchema = {
    sans: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    heading: '"Plus Jakarta Sans", "Inter", sans-serif',
    mono: '"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
};

export const DEFAULT_THEME_COLORS: ThemeColorsSchema = {
    brand: {
        '50': '#ecfdf5',
        '100': '#d1fae5',
        '200': '#a7f3d0',
        '300': '#6ee7b7',
        '400': '#34d399',
        '500': '#10b981',
        '600': '#059669',
        '700': '#047857',
        '800': '#065f46',
        '900': '#064e3b',
    },
    indigo: {
        '50': '#eef2ff',
        '100': '#e0e7ff',
        '200': '#c7d2fe',
        '500': '#6366f1',
        '600': '#4f46e5',
        '700': '#4338ca',
        '900': '#312e81',
    },
    slate: {
        '50': '#f8fafc',
        '100': '#f1f5f9',
        '200': '#e2e8f0',
        '300': '#cbd5e1',
        '400': '#94a3b8',
        '500': '#64748b',
        '600': '#475569',
        '700': '#334155',
        '800': '#1e293b',
        '900': '#0f172a',
    },
    financial: {
        invested: '#6366f1',
        growth: '#10b981',
        growthDark: '#047857',
        growthBg: '#ecfdf5',
        growthBorder: '#a7f3d0',
        withdrawal: '#f43f5e',
        withdrawalDark: '#be123c',
        withdrawalBg: '#fff1f2',
        withdrawalBorder: '#fecdd3',
        principal: '#6366f1',
        principalDark: '#334155',
        principalBg: '#f8fafc',
        principalBorder: '#e2e8f0',
        postTax: '#8b5cf6',
        inflation: '#c2410c',
        inflationBg: '#fff7ed',
        inflationBorder: '#fed7aa',
        tax: '#6d28d9',
        taxBg: '#f5f3ff',
        taxBorder: '#ddd6fe',
        milestoneGold: '#fbbf24',
        milestoneGoldDark: '#b45309',
        milestoneGoldBg: '#fef3c7',
        milestoneGoldBorder: '#fde68a',
    },
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
    celebration: [
        '#10b981',
        '#059669',
        '#34d399',
        '#f59e0b',
        '#fbbf24',
        '#6366f1',
    ],
};

/**
 * Hydrates theme tokens dynamically from the server-injected #calculator-app-state Data Island.
 */
export function getHydratedThemeTokens(): ThemeTokensSchema {
    if (typeof document === 'undefined') {
        return { fonts: DEFAULT_THEME_FONTS, colors: DEFAULT_THEME_COLORS };
    }

    try {
        const stateEl = document.getElementById('calculator-app-state');
        if (stateEl && stateEl.textContent) {
            const parsed = JSON.parse(stateEl.textContent);
            if (parsed && typeof parsed === 'object' && parsed.theme) {
                const t = parsed.theme;
                return {
                    fonts: {
                        sans: t.fonts?.sans || DEFAULT_THEME_FONTS.sans,
                        heading: t.fonts?.heading || DEFAULT_THEME_FONTS.heading,
                        mono: t.fonts?.mono || DEFAULT_THEME_FONTS.mono,
                    },
                    colors: {
                        brand: { ...DEFAULT_THEME_COLORS.brand, ...(t.colors?.brand || {}) },
                        indigo: { ...DEFAULT_THEME_COLORS.indigo, ...(t.colors?.indigo || {}) },
                        slate: { ...DEFAULT_THEME_COLORS.slate, ...(t.colors?.slate || {}) },
                        financial: {
                            ...DEFAULT_THEME_COLORS.financial,
                            ...(t.colors?.financial ? {
                                growth: t.colors.financial.growth || DEFAULT_THEME_COLORS.financial.growth,
                                growthDark: t.colors.financial.growth_dark || DEFAULT_THEME_COLORS.financial.growthDark,
                                withdrawal: t.colors.financial.withdrawal || DEFAULT_THEME_COLORS.financial.withdrawal,
                                withdrawalDark: t.colors.financial.withdrawal_dark || DEFAULT_THEME_COLORS.financial.withdrawalDark,
                                principal: t.colors.financial.principal || DEFAULT_THEME_COLORS.financial.principal,
                                tax: t.colors.financial.tax || DEFAULT_THEME_COLORS.financial.tax,
                                inflation: t.colors.financial.inflation || DEFAULT_THEME_COLORS.financial.inflation,
                                milestoneGold: t.colors.financial.gold || DEFAULT_THEME_COLORS.financial.milestoneGold,
                                milestoneGoldDark: t.colors.financial.gold_dark || DEFAULT_THEME_COLORS.financial.milestoneGoldDark,
                            } : {}),
                        },
                        chart: {
                            ...DEFAULT_THEME_COLORS.chart,
                            ...(t.colors?.chart ? {
                                gridLine: t.colors.chart.grid_line || DEFAULT_THEME_COLORS.chart.gridLine,
                                textMuted: t.colors.chart.text_muted || DEFAULT_THEME_COLORS.chart.textMuted,
                                textDark: t.colors.chart.text_dark || DEFAULT_THEME_COLORS.chart.textDark,
                                pointBgWhite: t.colors.chart.point_bg_white || DEFAULT_THEME_COLORS.chart.pointBgWhite,
                                tooltipBg: t.colors.chart.tooltip_bg || DEFAULT_THEME_COLORS.chart.tooltipBg,
                                tooltipTitle: t.colors.chart.tooltip_title || DEFAULT_THEME_COLORS.chart.tooltipTitle,
                                tooltipBody: t.colors.chart.tooltip_body || DEFAULT_THEME_COLORS.chart.tooltipBody,
                                tooltipBorder: t.colors.chart.tooltip_border || DEFAULT_THEME_COLORS.chart.tooltipBorder,
                                gradientInvestedTop: t.colors.chart.gradient_invested_top || DEFAULT_THEME_COLORS.chart.gradientInvestedTop,
                                gradientInvestedMid: t.colors.chart.gradient_invested_mid || DEFAULT_THEME_COLORS.chart.gradientInvestedMid,
                                gradientInvestedBottom: t.colors.chart.gradient_invested_bottom || DEFAULT_THEME_COLORS.chart.gradientInvestedBottom,
                                gradientCorpusTop: t.colors.chart.gradient_corpus_top || DEFAULT_THEME_COLORS.chart.gradientCorpusTop,
                                gradientCorpusMid: t.colors.chart.gradient_corpus_mid || DEFAULT_THEME_COLORS.chart.gradientCorpusMid,
                                gradientCorpusBottom: t.colors.chart.gradient_corpus_bottom || DEFAULT_THEME_COLORS.chart.gradientCorpusBottom,
                                gradientPostTaxTop: t.colors.chart.gradient_post_tax_top || DEFAULT_THEME_COLORS.chart.gradientPostTaxTop,
                                gradientPostTaxMid: t.colors.chart.gradient_post_tax_mid || DEFAULT_THEME_COLORS.chart.gradientPostTaxMid,
                                gradientPostTaxBottom: t.colors.chart.gradient_post_tax_bottom || DEFAULT_THEME_COLORS.chart.gradientPostTaxBottom,
                                swpFillBg: t.colors.chart.swp_fill_bg || DEFAULT_THEME_COLORS.chart.swpFillBg,
                                milestoneLineActive: t.colors.chart.milestone_line_active || DEFAULT_THEME_COLORS.chart.milestoneLineActive,
                                milestoneLineSubtle: t.colors.chart.milestone_line_subtle || DEFAULT_THEME_COLORS.chart.milestoneLineSubtle,
                            } : {}),
                        },
                        celebration: t.colors?.celebration || DEFAULT_THEME_COLORS.celebration,
                    },
                };
            }
        }
    } catch {
        // Safe fallback on parse error
    }

    return { fonts: DEFAULT_THEME_FONTS, colors: DEFAULT_THEME_COLORS };
}

export const THEME_FONTS: ThemeFontsSchema = DEFAULT_THEME_FONTS;
export const THEME_COLORS: ThemeColorsSchema = DEFAULT_THEME_COLORS;
