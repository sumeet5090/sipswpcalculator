import { THEME_COLORS } from '../constants/ThemeTokens';

export interface GradientBundle {
    invested: CanvasGradient;
    corpus: CanvasGradient;
    postTax: CanvasGradient;
}

/**
 * ChartGradientFactory
 * Computes dynamic linear canvas gradients with a 30px quantizing bucket cache
 * to eliminate GPU memory thrashing on resize.
 */
export class ChartGradientFactory {
    private cachedGradientBucket: number = -1;
    private cachedGradients: GradientBundle | null = null;

    /**
     * Compute dynamic linear gradients with 30px quantizing bucket cache.
     */
    public createGradients(ctx: CanvasRenderingContext2D, top: number = 0, bottom: number = 400): GradientBundle {
        const safeTop = Math.max(0, top);
        const safeBottom = Math.max(safeTop + 60, bottom);
        const heightSpan = safeBottom - safeTop;
        const bucket = Math.round(heightSpan / 30) * 30;

        if (this.cachedGradients && this.cachedGradientBucket === bucket) {
            return this.cachedGradients;
        }

        const gradientInvested = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
        gradientInvested.addColorStop(0, THEME_COLORS.chart.gradientInvestedTop);
        gradientInvested.addColorStop(0.7, THEME_COLORS.chart.gradientInvestedMid);
        gradientInvested.addColorStop(1, THEME_COLORS.chart.gradientInvestedBottom);

        const gradientCorpus = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
        gradientCorpus.addColorStop(0, THEME_COLORS.chart.gradientCorpusTop);
        gradientCorpus.addColorStop(0.6, THEME_COLORS.chart.gradientCorpusMid);
        gradientCorpus.addColorStop(1, THEME_COLORS.chart.gradientCorpusBottom);

        const gradientPostTax = ctx.createLinearGradient(0, safeTop, 0, safeBottom);
        gradientPostTax.addColorStop(0, THEME_COLORS.chart.gradientPostTaxTop);
        gradientPostTax.addColorStop(0.7, THEME_COLORS.chart.gradientPostTaxMid);
        gradientPostTax.addColorStop(1, THEME_COLORS.chart.gradientPostTaxBottom);

        this.cachedGradients = {
            invested: gradientInvested,
            corpus: gradientCorpus,
            postTax: gradientPostTax,
        };
        this.cachedGradientBucket = bucket;

        return this.cachedGradients;
    }

    public clearCache(): void {
        this.cachedGradients = null;
        this.cachedGradientBucket = -1;
    }
}
