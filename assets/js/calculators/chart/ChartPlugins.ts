import { THEME_COLORS, THEME_FONTS } from '../constants/ThemeTokens';
import { YearResult } from '../../types';
import { CurrencyFormatter } from '../CurrencyHelper';
import { Milestone } from './ChartMilestoneCalculator';

export interface ChartPluginsContext {
    getActiveBenchmark: () => 'none' | 'nifty' | 'gold' | 'fd';
    getLastResults: () => YearResult[];
    getActiveDonutScrubYear: () => number | null;
    getCurrentMilestones: () => Milestone[];
    computeBenchmarkCurve: (results: YearResult[], benchmarkRate: number) => number[];
    formatter: CurrencyFormatter;
}

/**
 * Creates the isolated custom Chart.js plugins.
 */
export function createChartPlugins(context: ChartPluginsContext) {
    const {
        getActiveBenchmark,
        getLastResults,
        getActiveDonutScrubYear,
        getCurrentMilestones,
        computeBenchmarkCurve,
        formatter,
    } = context;

    /**
     * Crosshair line plugin for responsive hover guidelines.
     */
    const crosshairPlugin = {
        id: 'crosshairLine',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.scales?.x || !chart.scales?.y) return;

            if (chart.tooltip?.getActiveElements()?.length) {
                const activePoint = chart.tooltip.getActiveElements()[0];
                const ctx = chart.ctx;
                const x = activePoint.element.x;
                const y = activePoint.element.y;
                const leftX = chart.scales.x.left;
                const topY = chart.scales.y.top;
                const bottomY = chart.scales.y.bottom;

                ctx.save();
                try {
                    ctx.beginPath();
                    ctx.setLineDash([4, 4]);
                    ctx.moveTo(x, topY);
                    ctx.lineTo(x, bottomY);
                    ctx.lineWidth = 1.5;
                    ctx.strokeStyle = THEME_COLORS.chart.milestoneLineActive;
                    ctx.stroke();

                    // Horizontal guide line to Y axis
                    ctx.beginPath();
                    ctx.moveTo(leftX, y);
                    ctx.lineTo(x, y);
                    ctx.strokeStyle = THEME_COLORS.chart.milestoneLineSubtle;
                    ctx.stroke();
                } finally {
                    ctx.restore();
                }
            }
        },
    };

    /**
     * Clip Guard Plugin: Prevents Chart.js getDatasetClipArea runtime exception
     * when filler plugin resolves cross-dataset bounds during drawing passes.
     */
    const clipGuardPlugin = {
        id: 'clipGuard',
        beforeDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line') return;
            const datasets = chart.data?.datasets || [];
            for (let i = 0; i < datasets.length; i++) {
                const meta = chart.getDatasetMeta(i);
                if (meta && !meta._clip) {
                    meta._clip = { top: 0, right: 0, bottom: 0, left: 0, disabled: true };
                }
            }
        },
        beforeDatasetDraw: (chart: any, args: any) => {
            if (args?.meta && !args.meta._clip) {
                args.meta._clip = { top: 0, right: 0, bottom: 0, left: 0, disabled: true };
            }
            if (args?.meta?.$filler?.index !== undefined) {
                const fillerTarget = chart.getDatasetMeta(args.meta.$filler.index);
                if (fillerTarget && !fillerTarget._clip) {
                    fillerTarget._clip = { top: 0, right: 0, bottom: 0, left: 0, disabled: true };
                }
            }
        },
    };

    /**
     * Compounding Ignition Zone Plugin: Illuminates the inflection zone where annual interest surpasses annual SIP contributions.
     */
    const compoundingIgnitionPlugin = {
        id: 'compoundingIgnitionZone',
        beforeDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.chartArea) return;
            const meta = chart.getDatasetMeta(1);
            if (!meta || !meta.data || meta.data.length === 0) return;

            const results = getLastResults();
            if (results.length < 2) return;

            const crossoverIdx = results.findIndex((r, idx) => {
                if (idx === 0) return false;
                const annualInterest = r.interest || 0;
                const annualContribution = r.annual_contribution || 0;
                return annualInterest >= annualContribution && annualContribution > 0;
            });

            if (crossoverIdx === -1 || !meta.data[crossoverIdx]) return;

            const ctx = chart.ctx;
            const xPos = meta.data[crossoverIdx].x;
            const { top, bottom, right } = chart.chartArea;

            ctx.save();
            try {
                // Ambient soft light aurora ignition glow
                const gradient = ctx.createLinearGradient(xPos, 0, right, 0);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.08)');
                gradient.addColorStop(0.35, 'rgba(20, 184, 166, 0.04)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

                ctx.fillStyle = gradient;
                ctx.fillRect(xPos, top, right - xPos, bottom - top);

                // Demarcation dotted hairline
                ctx.beginPath();
                ctx.setLineDash([3, 3]);
                ctx.moveTo(xPos, top);
                ctx.lineTo(xPos, bottom);
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(5, 150, 105, 0.4)';
                ctx.stroke();

                // Pure Light Ignition Beacon annotation (pinned to top edge)
                const tagText = '⚡ Compounding Ignition';
                ctx.font = '700 9px "Plus Jakarta Sans", "Inter", sans-serif';
                const textWidth = ctx.measureText(tagText).width;
                const pillWidth = textWidth + 14;
                const pillX = Math.min(xPos + 4, right - pillWidth - 4);

                ctx.setLineDash([]);
                ctx.fillStyle = '#ecfdf5';
                ctx.strokeStyle = '#a7f3d0';
                ctx.lineWidth = 1;
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                    ctx.roundRect(pillX, top + 4, pillWidth, 18, 4);
                } else {
                    ctx.rect(pillX, top + 4, pillWidth, 18);
                }
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#047857';
                ctx.textBaseline = 'middle';
                ctx.fillText(tagText, pillX + 7, top + 13);
            } finally {
                ctx.restore();
            }
        },
    };

    /**
     * ₹1 Crore Golden Milestone Guideline Plugin.
     * Renders a clean right-anchored pin badge without slicing text through curves.
     */
    const croreMilestoneLinePlugin = {
        id: 'croreMilestoneLine',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'line' || !chart.scales?.y || !chart.chartArea) return;
            const yScale = chart.scales.y;
            const targetVal = 10000000; // 1 Crore
            if (yScale.max < targetVal) return;

            const yPos = yScale.getPixelForValue(targetVal);
            const { left, right } = chart.chartArea;
            const ctx = chart.ctx;

            ctx.save();
            try {
                // Milestone badge geometry
                const badgeText = '👑 ₹1 Crore Target';
                ctx.font = '700 9.5px "Plus Jakarta Sans", "Inter", sans-serif';
                const textWidth = ctx.measureText(badgeText).width;
                const badgeW = textWidth + 16;
                const badgeH = 18;
                const badgeX = right - badgeW - 4;
                const badgeY = yPos - (badgeH / 2);

                // Subtle guideline stopping before the badge
                ctx.beginPath();
                ctx.setLineDash([4, 6]);
                ctx.moveTo(left, yPos);
                ctx.lineTo(badgeX - 4, yPos);
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(217, 119, 6, 0.35)'; // Delicate amber tint
                ctx.stroke();

                // Crisp light-mode amber milestone pill
                ctx.setLineDash([]);
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                    ctx.roundRect(badgeX, badgeY, badgeW, badgeH, 4);
                } else {
                    ctx.rect(badgeX, badgeY, badgeW, badgeH);
                }
                ctx.fillStyle = '#fffbeb'; // amber-50
                ctx.strokeStyle = '#fde68a'; // amber-200
                ctx.lineWidth = 1;
                ctx.fill();
                ctx.stroke();

                ctx.fillStyle = '#b45309'; // amber-700
                ctx.textBaseline = 'middle';
                ctx.fillText(badgeText, badgeX + 8, yPos);
            } finally {
                ctx.restore();
            }
        },
    };

    /**
     * Bank FD Alpha Delta Terminal Bracket Plugin with right-edge clamping.
     */
    const fdAlphaDeltaPlugin = {
        id: 'fdAlphaDelta',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'line' || getActiveBenchmark() !== 'fd' || !chart.chartArea) return;
            const results = getLastResults();
            if (results.length < 2) return;

            const sipCorpus = results[results.length - 1].combined_total;
            const fdCurve = computeBenchmarkCurve(results, 6.5);
            const fdCorpus = fdCurve[fdCurve.length - 1];
            const delta = sipCorpus - fdCorpus;
            if (delta <= 0) return;

            const metaSip = chart.getDatasetMeta(1);
            if (!metaSip || !metaSip.data || metaSip.data.length === 0) return;
            const finalPoint = metaSip.data[metaSip.data.length - 1];

            const ctx = chart.ctx;
            ctx.save();
            try {
                const badgeText = `+${formatter.format(delta)} FD Alpha`;
                ctx.font = '700 10px "Plus Jakarta Sans", "Inter", sans-serif';
                const width = ctx.measureText(badgeText).width + 12;

                const clampedX = Math.min(finalPoint.x - width, chart.chartArea.right - width - 2);
                const clampedY = Math.max(chart.chartArea.top + 4, finalPoint.y - 24);

                ctx.setLineDash([]);
                ctx.fillStyle = '#065f46';
                ctx.beginPath();
                if (typeof ctx.roundRect === 'function') {
                    ctx.roundRect(clampedX, clampedY, width, 18, 4);
                } else {
                    ctx.rect(clampedX, clampedY, width, 18);
                }
                ctx.fill();

                ctx.fillStyle = '#ffffff';
                ctx.textBaseline = 'middle';
                ctx.fillText(badgeText, clampedX + 6, clampedY + 9);
            } finally {
                ctx.restore();
            }
        },
    };

    /**
     * Donut Center Metric Text Plugin with Multiplier & ROI Label.
     */
    const donutCenterTextPlugin = {
        id: 'donutCenterText',
        afterDraw: (chart: any) => {
            if (chart.config.type !== 'doughnut' || !chart.chartArea) return;
            const { ctx, chartArea } = chart;
            const datasets = chart.data.datasets;
            if (!datasets || datasets.length === 0) return;

            const results = getLastResults();
            const activeYear = getActiveDonutScrubYear() || results.length;
            const currentRow = results.find(r => r.year === activeYear) || results[results.length - 1];

            const isDepleted = currentRow && (currentRow.combined_total <= 0) && (currentRow.annual_withdrawal ?? 0) > 0;

            const centerX = (chartArea.left + chartArea.right) / 2;
            const centerY = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            try {
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                if (isDepleted) {
                    ctx.font = `800 18px ${THEME_FONTS.heading}`;
                    ctx.fillStyle = '#be123c'; // Rose-700
                    ctx.fillText('DEPLETED', centerX, centerY - 6);

                    ctx.font = `700 9px ${THEME_FONTS.mono}`;
                    ctx.fillStyle = '#9f1239';
                    ctx.fillText(`AT YEAR ${currentRow.year}`, centerX, centerY + 12);
                } else {
                    const data = datasets[0].data as number[];
                    const totalInvested = data[0] || 0;
                    const totalGains = data[1] || 0;
                    const totalWithdrawals = (data.length > 2 ? data[2] : 0) || 0;
                    const finalValue = totalGains + totalInvested + totalWithdrawals;
                    const multiplier = totalInvested > 0 ? (finalValue / totalInvested).toFixed(1) : '1.0';

                    ctx.font = `800 24px ${THEME_FONTS.mono}`;
                    ctx.fillStyle = '#047857'; // Emerald-700
                    ctx.fillText(`${multiplier}×`, centerX, centerY - 6);

                    ctx.font = `700 9px ${THEME_FONTS.heading}`;
                    ctx.fillStyle = '#64748b';
                    const yearLabel = getActiveDonutScrubYear() ? `YR ${getActiveDonutScrubYear()} ROI` : 'ROI MULTIPLIER';
                    ctx.fillText(yearLabel, centerX, centerY + 13);
                }
            } finally {
                ctx.restore();
            }
        },
    };

    /**
     * Spline Milestones Golden Beacons Plugin.
     */
    const splineMilestonesPlugin = {
        id: 'splineMilestones',
        afterDatasetsDraw: (chart: any) => {
            if (chart.config.type !== 'line') return;
            const meta = chart.getDatasetMeta(1);
            if (!meta || !meta.data) return;

            const ctx = chart.ctx;
            const milestones = getCurrentMilestones() || [];

            milestones.forEach(m => {
                if (m.index === undefined || !meta.data[m.index]) return;
                const point = meta.data[m.index];

                ctx.save();
                try {
                    ctx.setLineDash([]);
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 11, 0, Math.PI * 2);
                    ctx.fillStyle = m.type === 'security' ? 'rgba(245, 158, 11, 0.22)' : 'rgba(16, 185, 129, 0.22)';
                    ctx.fill();

                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 5.5, 0, Math.PI * 2);
                    ctx.fillStyle = m.type === 'security' ? '#d97706' : '#10b981';
                    ctx.fill();
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = '#ffffff';
                    ctx.stroke();
                } finally {
                    ctx.restore();
                }
            });
        },
    };

    return {
        crosshairPlugin,
        clipGuardPlugin,
        compoundingIgnitionPlugin,
        croreMilestoneLinePlugin,
        fdAlphaDeltaPlugin,
        donutCenterTextPlugin,
        splineMilestonesPlugin,
    };
}
