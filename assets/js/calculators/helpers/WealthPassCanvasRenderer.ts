/**
 * WealthPassCanvasRenderer.ts
 * Generates an ultra-premium Apple Wallet / FinTech Pass style "Wealth Horizon Pass"
 * on an offscreen HTML5 Canvas (1200x630 Retina) with web font synchronization,
 * dynamic milestone badges, and Discreet Privacy Mode masking.
 * Strictly adheres to SOLID, DRY, and pure light-mode institutional aesthetics.
 */

import type { InvestmentInputs } from '../../types';
import { THEME_FONTS } from '../constants/ThemeTokens';

export interface WealthPassOptions {
    inputs: InvestmentInputs;
    finalCorpus: number;
    totalInvested: number;
    totalGains: number;
    isDiscreetMode?: boolean;
    goalTitle?: string;
    score?: number;
}

export class WealthPassCanvasRenderer {
    /**
     * Renders the Wealth Horizon Pass to a PNG Blob ready for Web Share API.
     */
    static async generatePassBlob(options: WealthPassOptions): Promise<Blob | null> {
        // Wait for web fonts to load
        if (typeof document !== 'undefined' && document.fonts && document.fonts.ready) {
            try {
                await Promise.race([
                    document.fonts.ready,
                    new Promise(resolve => setTimeout(resolve, 350)) // Fallback after 350ms
                ]);
            } catch {}
        }

        const width = 1200;
        const height = 630;
        const dpr = typeof window !== 'undefined' ? Math.min(window.devicePixelRatio || 2, 2) : 2;

        const canvas = document.createElement('canvas');
        canvas.width = width * dpr;
        canvas.height = height * dpr;

        const ctx = canvas.getContext('2d');
        if (!ctx) return null;

        ctx.scale(dpr, dpr);

        const {
            inputs,
            finalCorpus,
            totalInvested,
            totalGains,
            isDiscreetMode = false,
            goalTitle = 'Destination: Financial Freedom',
            score = 94
        } = options;

        // 1. Pure Crisp Light Base Canvas
        ctx.fillStyle = '#f8fafc'; // Slate-50
        ctx.fillRect(0, 0, width, height);

        // Soft Pastel Aurora Gradients (Pure Light-Mode)
        const auroraLeft = ctx.createRadialGradient(200, 150, 40, 300, 200, 450);
        auroraLeft.addColorStop(0, 'rgba(16, 185, 129, 0.12)'); // Emerald
        auroraLeft.addColorStop(0.6, 'rgba(20, 184, 166, 0.06)'); // Teal
        auroraLeft.addColorStop(1, 'rgba(248, 250, 252, 0)');
        ctx.fillStyle = auroraLeft;
        ctx.fillRect(0, 0, width, height);

        const auroraRight = ctx.createRadialGradient(1000, 450, 30, 900, 400, 400);
        auroraRight.addColorStop(0, 'rgba(99, 102, 241, 0.08)'); // Indigo
        auroraRight.addColorStop(1, 'rgba(248, 250, 252, 0)');
        ctx.fillStyle = auroraRight;
        ctx.fillRect(0, 0, width, height);

        // Card Container Boundary with Crisp Border
        const cardX = 40;
        const cardY = 30;
        const cardW = width - 80;
        const cardH = height - 60;

        ctx.fillStyle = 'rgba(255, 255, 255, 0.96)';
        ctx.beginPath();
        ctx.roundRect(cardX, cardY, cardW, cardH, 24);
        ctx.fill();
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // 2. Pass Header Strip
        const pad = cardX + 44;
        let y = cardY + 54;

        // Brand Pill
        ctx.fillStyle = '#ecfdf5';
        ctx.beginPath();
        ctx.roundRect(pad, y - 22, 220, 32, 16);
        ctx.fill();
        ctx.strokeStyle = '#a7f3d0';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.fillStyle = '#065f46';
        ctx.font = `800 11.5px ${THEME_FONTS.heading}`;
        ctx.fillText('✦ WEALTH HORIZON PASS ✦', pad + 18, y - 2);

        // Freedom Score Pill on Right
        const scoreX = cardX + cardW - 240;
        ctx.fillStyle = '#eef2ff';
        ctx.beginPath();
        ctx.roundRect(scoreX, y - 22, 196, 32, 16);
        ctx.fill();
        ctx.strokeStyle = '#c7d2fe';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.fillStyle = '#3730a3';
        ctx.font = `800 11.5px ${THEME_FONTS.heading}`;
        ctx.fillText(`RESILIENCE: ${score}/100 🛡️`, scoreX + 22, y - 2);

        y += 52;

        // Title
        ctx.fillStyle = '#0f172a';
        ctx.font = `800 32px ${THEME_FONTS.heading}`;
        ctx.fillText(goalTitle, pad, y);

        y += 24;
        ctx.fillStyle = '#64748b';
        ctx.font = `600 14px ${THEME_FONTS.sans}`;
        ctx.fillText(`Horizon: ${inputs.years} Years Systematic Plan • ${inputs.rate}% Target CAGR`, pad, y);

        y += 40;

        // 3. Central Hero Corpus Display
        const heroBoxW = cardW - 88;
        const heroBoxH = 170;
        const heroBoxX = pad;
        const heroBoxY = y;

        ctx.fillStyle = '#f8fafc';
        ctx.beginPath();
        ctx.roundRect(heroBoxX, heroBoxY, heroBoxW, heroBoxH, 18);
        ctx.fill();
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        ctx.fillStyle = '#64748b';
        ctx.font = `700 13px ${THEME_FONTS.heading}`;
        ctx.fillText('ESTIMATED WEALTH ACCUMULATION', heroBoxX + 32, heroBoxY + 38);

        // Value formatting (with Discreet Mode Masking)
        ctx.fillStyle = '#047857';
        ctx.font = `900 52px ${THEME_FONTS.mono || THEME_FONTS.heading}`;

        let displayCorpus: string;
        if (isDiscreetMode) {
            displayCorpus = finalCorpus >= 10000000
                ? '₹ **,**,*** [ Crore Club Verified 🏅 ]'
                : '₹ **,**,*** [ 100% On-Track 🎯 ]';
        } else {
            displayCorpus = '₹ ' + Math.round(finalCorpus).toLocaleString('en-IN');
        }
        ctx.fillText(displayCorpus, heroBoxX + 32, heroBoxY + 104);

        // Subtext inside hero box
        const multiplier = totalInvested > 0 ? (finalCorpus / totalInvested).toFixed(1) : '1.0';
        ctx.fillStyle = '#059669';
        ctx.font = `700 14px ${THEME_FONTS.heading}`;
        ctx.fillText(`▲ ${multiplier}× Wealth Multiplier on Principal Invested`, heroBoxX + 32, heroBoxY + 142);

        y += heroBoxH + 34;

        // 4. Three-Column Metric Pills
        const colW = (heroBoxW - 32) / 3;
        const metrics = [
            {
                label: 'MONTHLY SIP',
                val: `₹${inputs.sip.toLocaleString('en-IN')}/mo`
            },
            {
                label: 'ANNUAL STEP-UP',
                val: inputs.stepup > 0 ? `+${inputs.stepup}% / Year` : 'Fixed (0%)'
            },
            {
                label: 'ESTIMATED GAINS',
                val: isDiscreetMode ? '₹ **,**,***' : `+₹${Math.round(totalGains).toLocaleString('en-IN')}`
            }
        ];

        metrics.forEach((m, idx) => {
            const bx = heroBoxX + idx * (colW + 16);
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.roundRect(bx, y, colW, 68, 14);
            ctx.fill();
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.fillStyle = '#94a3b8';
            ctx.font = `700 11px ${THEME_FONTS.heading}`;
            ctx.fillText(m.label, bx + 18, y + 24);

            ctx.fillStyle = '#0f172a';
            ctx.font = `800 18px ${THEME_FONTS.heading}`;
            ctx.fillText(m.val, bx + 18, y + 50);
        });

        // 5. Footer Watermark & Security Hash
        const footY = cardY + cardH - 24;
        ctx.fillStyle = '#64748b';
        ctx.font = `600 13px ${THEME_FONTS.sans}`;
        ctx.fillText('🔗 Verify scenario live at sipswpcalculator.com', pad, footY);

        ctx.textAlign = 'right';
        ctx.fillStyle = '#94a3b8';
        ctx.font = `500 12px ${THEME_FONTS.sans}`;
        ctx.fillText('Pure Client-Side Simulation • SEBI Educational Standard', cardX + cardW - 44, footY);

        // Convert canvas to Blob
        return new Promise<Blob | null>(resolve => {
            canvas.toBlob(blob => {
                // Cleanup canvas buffer memory
                canvas.width = 0;
                canvas.height = 0;
                resolve(blob);
            }, 'image/png');
        });
    }
}
