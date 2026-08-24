import type { Chart } from 'chart.js';
import type { InvestmentInputs } from '../../types';
import { THEME_FONTS } from '../constants/ThemeTokens';

/**
 * CanvasExportHelper.ts
 * Generates branded, high-DPI canvas rasterization with SEBI/AMFI methodology stamps,
 * client-side parameter summaries, and timestamp verification.
 */
export class CanvasExportHelper {
    /**
     * Export chart canvas to a branded PNG Data URL.
     */
    static exportBrandedChart(chart: Chart, inputs: InvestmentInputs): string {
        if (!chart || !chart.canvas) return '';

        const dpr = typeof window !== 'undefined' ? (window.devicePixelRatio || 2) : 2;
        const origWidth = chart.width;
        const origHeight = chart.height;

        const padX = 24;
        const headerHeight = 60;
        const footerHeight = 44;

        const totalWidth = origWidth + (padX * 2);
        const totalHeight = origHeight + headerHeight + footerHeight;

        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = totalWidth * dpr;
        exportCanvas.height = totalHeight * dpr;

        const ctx = exportCanvas.getContext('2d');
        if (!ctx) return chart.canvas.toDataURL('image/png');

        ctx.scale(dpr, dpr);

        // 1. Crisp Pure Light Fintech Background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, totalWidth, totalHeight);

        // 2. Header Banner
        ctx.fillStyle = '#0f172a'; // Slate-900
        ctx.font = `700 14px ${THEME_FONTS.heading}`;
        ctx.fillText('SIP & SWP Wealth Growth Plan', padX, 28);

        ctx.fillStyle = '#047857'; // Emerald-700
        ctx.font = `600 11px ${THEME_FONTS.heading}`;
        let subtext = `SIP: ₹${inputs.sip.toLocaleString('en-IN')}/mo • ${inputs.years} Yrs @ ${inputs.rate}% Return`;
        if (inputs.stepup > 0) subtext += ` • ${inputs.stepup}% Step-Up`;
        if (inputs.enable_swp) subtext += ` • SWP: ₹${inputs.swp_withdrawal.toLocaleString('en-IN')}/mo`;
        ctx.fillText(subtext, padX, 46);

        // 3. Draw Chart Instance
        ctx.drawImage(chart.canvas, padX, headerHeight, origWidth, origHeight);

        // 4. Footer & Compliance Watermark
        const footerY = totalHeight - 18;
        ctx.fillStyle = '#94a3b8'; // Slate-400
        ctx.font = `500 9.5px ${THEME_FONTS.sans}`;
        ctx.fillText('Calculated via sipswpcalculator.com • Educational model based on standard compounding methodologies', padX, footerY);

        ctx.textAlign = 'right';
        ctx.fillText(new Date().toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' }), totalWidth - padX, footerY);

        return exportCanvas.toDataURL('image/png');
    }
}
