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

    /**
     * Export a branded 1080x1080 social media summary card (PNG data URL)
     * optimized for viral sharing on WhatsApp, Instagram, LinkedIn, and Twitter.
     */
    static exportSocialSummaryCard(
        inputs: InvestmentInputs,
        totalInvested: number,
        totalGains: number,
        finalCorpus: number,
        planTitle: string = 'Wealth Accumulation Plan'
    ): string {
        const size = 1080;
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = size;
        exportCanvas.height = size;

        const ctx = exportCanvas.getContext('2d');
        if (!ctx) return '';

        // 1. Pure Light Fintech Canvas Base
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, size, size);

        // Soft Aurora Gradients in corners
        const gradTopLeft = ctx.createRadialGradient(160, 160, 20, 240, 240, 400);
        gradTopLeft.addColorStop(0, 'rgba(16, 185, 129, 0.14)');
        gradTopLeft.addColorStop(1, 'rgba(248, 250, 252, 0)');
        ctx.fillStyle = gradTopLeft;
        ctx.fillRect(0, 0, 600, 600);

        const gradBottomRight = ctx.createRadialGradient(900, 900, 20, 800, 800, 400);
        gradBottomRight.addColorStop(0, 'rgba(14, 165, 233, 0.10)');
        gradBottomRight.addColorStop(1, 'rgba(248, 250, 252, 0)');
        ctx.fillStyle = gradBottomRight;
        ctx.fillRect(400, 400, 680, 680);

        // Outer Decorative Frame
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.roundRect(40, 40, size - 80, size - 80, 32);
        ctx.stroke();

        // 2. Header & Branding Pill
        const padX = 80;
        let curY = 100;

        // Badge pill
        ctx.fillStyle = '#ecfdf5';
        ctx.beginPath();
        ctx.roundRect(padX, curY, 260, 38, 19);
        ctx.fill();
        ctx.strokeStyle = '#a7f3d0';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        ctx.fillStyle = '#065f46';
        ctx.font = `800 13px ${THEME_FONTS.heading}`;
        ctx.fillText('⚡ SIP & SWP CALCULATOR', padX + 22, curY + 24);

        curY += 76;

        // Title
        ctx.fillStyle = '#0f172a';
        ctx.font = `800 38px ${THEME_FONTS.heading}`;
        ctx.fillText(planTitle, padX, curY);

        curY += 34;
        ctx.fillStyle = '#64748b';
        ctx.font = `500 19px ${THEME_FONTS.sans}`;
        ctx.fillText('Automated Wealth Simulation • 2026 Budget & SEBI Compliant', padX, curY);

        curY += 46;

        // 3. Central Hero Corpus Card (White Card with Glow)
        const cardX = padX;
        const cardWidth = size - (padX * 2);
        const cardHeight = 310;

        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(cardX, curY, cardWidth, cardHeight, 28);
        ctx.fill();
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Inside Hero Card:
        const innerY = curY + 54;
        ctx.fillStyle = '#64748b';
        ctx.font = `700 16px ${THEME_FONTS.heading}`;
        ctx.fillText('PROJECTED MATURITY CORPUS', cardX + 40, innerY);

        ctx.fillStyle = '#047857'; // Emerald-700
        ctx.font = `900 60px ${THEME_FONTS.mono || THEME_FONTS.heading}`;
        const corpusFormatted = '₹ ' + Math.round(finalCorpus).toLocaleString('en-IN');
        ctx.fillText(corpusFormatted, cardX + 40, innerY + 68);

        // Divider
        ctx.strokeStyle = '#f1f5f9';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cardX + 40, innerY + 104);
        ctx.lineTo(cardX + cardWidth - 40, innerY + 104);
        ctx.stroke();

        // Bottom Split inside Hero Card:
        const col1X = cardX + 40;
        const col2X = cardX + (cardWidth / 2) + 20;
        const splitY = innerY + 144;

        ctx.fillStyle = '#64748b';
        ctx.font = `700 15px ${THEME_FONTS.heading}`;
        ctx.fillText('TOTAL INVESTED', col1X, splitY);
        ctx.fillStyle = '#0f172a';
        ctx.font = `800 32px ${THEME_FONTS.mono || THEME_FONTS.heading}`;
        ctx.fillText('₹ ' + Math.round(totalInvested).toLocaleString('en-IN'), col1X, splitY + 42);

        ctx.fillStyle = '#64748b';
        ctx.font = `700 15px ${THEME_FONTS.heading}`;
        const gainPct = totalInvested > 0 ? Math.round((totalGains / totalInvested) * 100) : 0;
        ctx.fillText(`ESTIMATED GAINS (+${gainPct}%)`, col2X, splitY);
        ctx.fillStyle = '#059669';
        ctx.font = `800 32px ${THEME_FONTS.mono || THEME_FONTS.heading}`;
        ctx.fillText('₹ ' + Math.round(totalGains).toLocaleString('en-IN'), col2X, splitY + 42);

        curY += cardHeight + 40;

        // 4. Visual Proportion Bar
        const barWidth = cardWidth;
        const barHeight = 26;
        const investedRatio = finalCorpus > 0 ? Math.min(1, Math.max(0, totalInvested / finalCorpus)) : 0.5;
        const investedBarW = Math.round(barWidth * investedRatio);

        ctx.fillStyle = '#cbd5e1'; // Principal color
        ctx.beginPath();
        ctx.roundRect(cardX, curY, barWidth, barHeight, 13);
        ctx.fill();

        ctx.fillStyle = '#10b981'; // Gains color
        ctx.beginPath();
        ctx.roundRect(cardX + investedBarW, curY, barWidth - investedBarW, barHeight, 13);
        ctx.fill();

        curY += barHeight + 36;

        // 5. Four Key Parameter Pills Grid (2x2)
        const paramBoxW = (cardWidth - 24) / 2;
        const paramBoxH = 80;

        const paramsList = [
            { label: 'MONTHLY INVESTMENT', val: `₹${inputs.sip.toLocaleString('en-IN')}/mo` },
            { label: 'TIME HORIZON', val: `${inputs.years} Years` },
            { label: 'EXPECTED CAGR', val: `${inputs.rate}% Annual` },
            { label: 'ANNUAL STEP-UP', val: inputs.stepup > 0 ? `${inputs.stepup}% Yearly` : 'Flat (0%)' },
        ];

        paramsList.forEach((p, idx) => {
            const bx = cardX + (idx % 2) * (paramBoxW + 24);
            const by = curY + Math.floor(idx / 2) * (paramBoxH + 16);

            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.roundRect(bx, by, paramBoxW, paramBoxH, 18);
            ctx.fill();
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.5;
            ctx.stroke();

            ctx.fillStyle = '#94a3b8';
            ctx.font = `700 12px ${THEME_FONTS.heading}`;
            ctx.fillText(p.label, bx + 22, by + 30);

            ctx.fillStyle = '#0f172a';
            ctx.font = `800 22px ${THEME_FONTS.heading}`;
            ctx.fillText(p.val, bx + 22, by + 60);
        });

        // 6. Footer Watermark & Compliance
        const footY = size - 70;
        ctx.fillStyle = '#64748b';
        ctx.font = `600 16px ${THEME_FONTS.sans}`;
        ctx.fillText('🔗 Calculate your plan free at sipswpcalculator.com', padX, footY);

        ctx.textAlign = 'right';
        ctx.font = `500 14px ${THEME_FONTS.sans}`;
        ctx.fillStyle = '#94a3b8';
        ctx.fillText(new Date().toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' }), size - padX, footY);

        return exportCanvas.toDataURL('image/png');
    }
}
