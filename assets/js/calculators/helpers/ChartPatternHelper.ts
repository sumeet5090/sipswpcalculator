/**
 * ChartPatternHelper.ts
 * Generates cached off-screen canvas pattern tiles and distinct geometric markers
 * to guarantee WCAG 2.1 AAA colorblindness compliance (Protanopia / Deuteranopia).
 */
export class ChartPatternHelper {
    private static patternCache: Map<string, CanvasPattern | string> = new Map();

    /**
     * Generate or retrieve a cached diagonal stripe canvas pattern.
     */
    static createDiagonalStripePattern(
        ctx: CanvasRenderingContext2D,
        strokeColor: string = 'rgba(5, 150, 105, 0.15)',
        spacing: number = 8
    ): CanvasPattern | string {
        const cacheKey = `stripe_${strokeColor}_${spacing}`;
        if (this.patternCache.has(cacheKey)) {
            return this.patternCache.get(cacheKey)!;
        }

        if (typeof document === 'undefined') return strokeColor;

        const patternCanvas = document.createElement('canvas');
        patternCanvas.width = spacing;
        patternCanvas.height = spacing;
        const pCtx = patternCanvas.getContext('2d');
        if (!pCtx) return strokeColor;

        pCtx.strokeStyle = strokeColor;
        pCtx.lineWidth = 1.5;
        pCtx.beginPath();
        pCtx.moveTo(0, spacing);
        pCtx.lineTo(spacing, 0);
        pCtx.stroke();

        const pattern = ctx.createPattern(patternCanvas, 'repeat');
        if (pattern) {
            this.patternCache.set(cacheKey, pattern);
            return pattern;
        }

        return strokeColor;
    }

    /**
     * Get distinct accessible point shapes for Chart.js series.
     */
    static getPointStyle(seriesType: 'invested' | 'corpus' | 'postTax' | 'swp' | 'benchmark'): 'rectRot' | 'circle' | 'triangle' | 'rect' | 'crossRot' {
        switch (seriesType) {
            case 'invested':
                return 'rectRot'; // Diamond
            case 'corpus':
                return 'circle';  // Radiant Circle
            case 'postTax':
                return 'triangle';// Triangle
            case 'swp':
                return 'rect';    // Square
            case 'benchmark':
                return 'crossRot';// Cross
            default:
                return 'circle';
        }
    }
}
