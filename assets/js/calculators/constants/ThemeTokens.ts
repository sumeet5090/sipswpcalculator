/**
 * ThemeTokens.ts
 * Single Source of Truth for frontend JavaScript & Canvas styling constants.
 * Derives static defaults directly from content/theme_tokens.json and hydrates runtime overrides
 * from the server-injected #calculator-app-state Data Island.
 */
import themeTokensJson from '../../../../content/theme_tokens.json' with { type: 'json' };

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

const jsonColors = themeTokensJson.colors;

export const DEFAULT_THEME_FONTS: ThemeFontsSchema = {
    sans: themeTokensJson.fonts.sans,
    heading: themeTokensJson.fonts.heading,
    mono: themeTokensJson.fonts.mono,
};

export const DEFAULT_THEME_COLORS: ThemeColorsSchema = {
    brand: { ...jsonColors.brand },
    indigo: { ...jsonColors.indigo },
    slate: { ...jsonColors.slate },
    financial: {
        invested: jsonColors.indigo['500'],
        growth: jsonColors.financial.growth,
        growthDark: jsonColors.financial.growth_dark,
        growthBg: jsonColors.financial.growth_bg,
        growthBorder: jsonColors.financial.growth_border,
        withdrawal: jsonColors.financial.withdrawal,
        withdrawalDark: jsonColors.financial.withdrawal_dark,
        withdrawalBg: jsonColors.financial.withdrawal_bg,
        withdrawalBorder: jsonColors.financial.withdrawal_border,
        principal: jsonColors.financial.principal,
        principalDark: jsonColors.financial.principal_dark,
        principalBg: jsonColors.financial.principal_bg,
        principalBorder: jsonColors.financial.principal_border,
        postTax: '#8b5cf6',
        inflation: jsonColors.financial.inflation,
        inflationBg: jsonColors.financial.inflation_bg,
        inflationBorder: jsonColors.financial.inflation_border,
        tax: jsonColors.financial.tax,
        taxBg: jsonColors.financial.tax_bg,
        taxBorder: jsonColors.financial.tax_border,
        milestoneGold: jsonColors.financial.gold,
        milestoneGoldDark: jsonColors.financial.gold_dark,
        milestoneGoldBg: jsonColors.financial.gold_bg,
        milestoneGoldBorder: jsonColors.financial.gold_border,
    },
    chart: {
        gridLine: jsonColors.chart.grid_line,
        textMuted: jsonColors.chart.text_muted,
        textDark: jsonColors.chart.text_dark,
        pointBgWhite: jsonColors.chart.point_bg_white,
        tooltipBg: jsonColors.chart.tooltip_bg,
        tooltipTitle: jsonColors.chart.tooltip_title,
        tooltipBody: jsonColors.chart.tooltip_body,
        tooltipBorder: jsonColors.chart.tooltip_border,
        gradientInvestedTop: jsonColors.chart.gradient_invested_top,
        gradientInvestedMid: jsonColors.chart.gradient_invested_mid,
        gradientInvestedBottom: jsonColors.chart.gradient_invested_bottom,
        gradientCorpusTop: jsonColors.chart.gradient_corpus_top,
        gradientCorpusMid: jsonColors.chart.gradient_corpus_mid,
        gradientCorpusBottom: jsonColors.chart.gradient_corpus_bottom,
        gradientPostTaxTop: jsonColors.chart.gradient_post_tax_top,
        gradientPostTaxMid: jsonColors.chart.gradient_post_tax_mid,
        gradientPostTaxBottom: jsonColors.chart.gradient_post_tax_bottom,
        swpFillBg: jsonColors.chart.swp_fill_bg,
        milestoneLineActive: jsonColors.chart.milestone_line_active,
        milestoneLineSubtle: jsonColors.chart.milestone_line_subtle,
    },
    celebration: [...jsonColors.celebration],
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

let cachedTokens: ThemeTokensSchema | null = null;

export function getThemeTokens(): ThemeTokensSchema {
    if (!cachedTokens) {
        cachedTokens = getHydratedThemeTokens();
    }
    return cachedTokens;
}

export function resetThemeTokensCache(): void {
    cachedTokens = null;
}

export const THEME_FONTS: ThemeFontsSchema = new Proxy(DEFAULT_THEME_FONTS, {
    get(_target, prop: string) {
        const key = prop as keyof ThemeFontsSchema;
        return getThemeTokens().fonts[key] ?? DEFAULT_THEME_FONTS[key];
    }
});

export const THEME_COLORS: ThemeColorsSchema = new Proxy(DEFAULT_THEME_COLORS, {
    get(_target, prop: string) {
        const key = prop as keyof ThemeColorsSchema;
        return getThemeTokens().colors[key] ?? DEFAULT_THEME_COLORS[key];
    }
});
