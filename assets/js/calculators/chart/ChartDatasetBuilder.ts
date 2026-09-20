import { YearResult } from '../../types';
import { THEME_COLORS } from '../constants/ThemeTokens';
import { ChartPatternHelper } from '../helpers/ChartPatternHelper';
import { GradientBundle } from './ChartGradientFactory';
import { Milestone } from './ChartMilestoneCalculator';
import { DOMAdapter } from '../../adapters/DOMAdapter';
import type { ChartDataset } from 'chart.js';

export interface ChartDatasetBuilderConfig {
    dom: DOMAdapter;
    getActiveBenchmark: () => 'none' | 'nifty' | 'gold' | 'fd';
    getShowHistoricalCorridor: () => boolean;
    getShockOverlayData: () => { label: string; data: number[] } | null;
    getShockOverlayCrashIndex: () => number | null;
}

/**
 * ChartDatasetBuilder
 * Encapsulates calculation and assembly of Chart.js line and donut datasets.
 */
export class ChartDatasetBuilder {
    private dom: DOMAdapter;
    private getActiveBenchmark: () => 'none' | 'nifty' | 'gold' | 'fd';
    private getShowHistoricalCorridor: () => boolean;
    private getShockOverlayData: () => { label: string; data: number[] } | null;
    private getShockOverlayCrashIndex: () => number | null;

    constructor(config: ChartDatasetBuilderConfig) {
        this.dom = config.dom;
        this.getActiveBenchmark = config.getActiveBenchmark;
        this.getShowHistoricalCorridor = config.getShowHistoricalCorridor;
        this.getShockOverlayData = config.getShockOverlayData;
        this.getShockOverlayCrashIndex = config.getShockOverlayCrashIndex;
    }

    /**
     * Compute benchmark projection dataset (Nifty 50, Gold, or FD) for the same cashflow sequence.
     */
    public computeBenchmarkCurve(results: YearResult[], benchmarkRate: number): number[] {
        let corpus = 0;
        const curve: number[] = [];
        const monthlyRate = benchmarkRate / 12 / 100;

        for (let i = 0; i < results.length; i++) {
            const row = results[i];
            const monthlySip = row.sip_monthly ?? 0;

            if (i === 0) {
                corpus += (row.begin_balance ?? 0);
            }

            for (let m = 0; m < 12; m++) {
                corpus = (corpus + monthlySip) * (1 + monthlyRate);
            }
            curve.push(Math.round(corpus));
        }

        return curve;
    }

    /**
     * Builds line datasets with proper visual layering, mutual exclusivity, and gradients.
     */
    public buildLineDatasets(
        results: YearResult[],
        gradients: GradientBundle,
        enableSwp: boolean,
        showPostTax: boolean,
        showWealthMap: boolean,
        mode: string,
        milestones: Milestone[]
    ): ChartDataset<'line'>[] {
        const cumulative = results.map(r => r.cumulative_invested);
        const corpus = results.map(r => r.combined_total);
        const postTaxCorpus = results.map(r => r.post_tax_total ?? r.combined_total);
        const swp = results.map(r => r.annual_withdrawal ?? 0);

        const milestoneIndices = milestones.map(m => m.index);
        const isSinglePoint = results.length === 1;

        const pointRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 6 : (isSinglePoint ? 4 : 0));
        const pointHoverRadii = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 10 : (isSinglePoint ? 8 : 6));
        const pointBgColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? THEME_COLORS.financial.milestoneGold : THEME_COLORS.financial.growth);
        const pointBorderColors = corpus.map((_, idx) => milestoneIndices.includes(idx) ? THEME_COLORS.chart.pointBgWhite : THEME_COLORS.financial.growth);
        const pointBorderWidths = corpus.map((_, idx) => milestoneIndices.includes(idx) ? 3 : 2);

        const interestOnly = corpus.map((c, i) => Math.max(0, c - cumulative[i]));

        const datasets: ChartDataset<'line'>[] = [];

        // 1. Total Invested Capital
        if (!showWealthMap) {
            datasets.push({
                label: 'Total Invested',
                data: cumulative,
                borderColor: THEME_COLORS.financial.invested,
                backgroundColor: gradients.invested,
                borderWidth: 2,
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 'origin',
                clip: false,
                pointStyle: ChartPatternHelper.getPointStyle('invested'),
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.invested,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                order: 3,
            });
        }

        // 2. Projected Nominal Corpus (Base Curve)
        datasets.push({
            label: showWealthMap ? 'Interest Earned' : 'Pre-Tax Corpus',
            data: showWealthMap ? interestOnly : corpus,
            borderColor: THEME_COLORS.financial.growth,
            backgroundColor: gradients.corpus,
            borderWidth: 3,
            tension: 0.4,
            cubicInterpolationMode: 'monotone' as const,
            fill: showWealthMap ? 'origin' : (showPostTax ? '+1' : 0),
            clip: false,
            pointStyle: ChartPatternHelper.getPointStyle('corpus'),
            pointBackgroundColor: pointBgColors,
            pointBorderColor: pointBorderColors,
            pointBorderWidth: pointBorderWidths,
            pointRadius: pointRadii,
            pointHoverRadius: pointHoverRadii,
            pointHoverBorderWidth: 3,
            order: 1,
        });

        // 3. Post-Tax Realized Value Layer
        if (showPostTax && !showWealthMap) {
            datasets.push({
                label: 'Post-Tax Corpus (§112A Net)',
                data: postTaxCorpus,
                borderColor: THEME_COLORS.financial.postTax,
                backgroundColor: 'rgba(139, 92, 246, 0.09)',
                borderWidth: 2,
                borderDash: [4, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 1,
                pointStyle: ChartPatternHelper.getPointStyle('postTax'),
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.postTax,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                order: 2,
            });
        }

        // 4. Real Purchasing Power Phantom Spline (when inflation > 0)
        const inflationInput = this.dom.getElement<HTMLInputElement>('inflation');
        const inflationRate = inflationInput ? parseFloat(inflationInput.value) : 0;
        if (inflationRate > 0 && !showWealthMap && results.length > 1) {
            const realValues = results.map(r => {
                const discountFactor = Math.pow(1 + (inflationRate / 100), r.year);
                return Math.round(r.combined_total / discountFactor);
            });

            datasets.push({
                label: `Real Value (${inflationRate}% Inflation Adj)`,
                data: realValues,
                borderColor: '#0284c7', // Sky-600
                backgroundColor: 'rgba(2, 132, 199, 0.04)',
                borderWidth: 2,
                borderDash: [5, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: 1,
                pointRadius: 0,
                pointHoverRadius: 5,
                order: 2
            });
        }

        // Historical Volatility Corridor
        if (this.getShowHistoricalCorridor() && !showWealthMap && !showPostTax && results.length > 1) {
            const lowerCorridor = this.computeBenchmarkCurve(results, 10.2);
            const upperCorridor = this.computeBenchmarkCurve(results, 15.8);

            datasets.push({
                label: 'Historical 10th Percentile (10.2% CAGR)',
                data: lowerCorridor,
                borderColor: 'rgba(5, 150, 105, 0.4)',
                backgroundColor: 'rgba(5, 150, 105, 0.06)',
                borderWidth: 1.5,
                borderDash: [2, 2],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: '+1',
                pointRadius: 0,
                pointHoverRadius: 4,
                order: 4,
            });

            datasets.push({
                label: 'Historical 90th Percentile (15.8% CAGR)',
                data: upperCorridor,
                borderColor: 'rgba(5, 150, 105, 0.4)',
                backgroundColor: 'transparent',
                borderWidth: 1.5,
                borderDash: [2, 2],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointRadius: 0,
                pointHoverRadius: 4,
                order: 4,
            });
        }

        // Flat SIP Baseline (0% Step-up)
        const hasStepUp = !enableSwp && results.length > 1 && ((results[1].annual_contribution ?? 0) > (results[0].annual_contribution ?? 0));
        if (hasStepUp && !showWealthMap) {
            const yr1 = results[0];
            const baseMonthlySip = yr1.sip_monthly ?? (yr1.annual_contribution ? (yr1.annual_contribution / 12) : 0);
            if (baseMonthlySip > 0 || (yr1.begin_balance ?? 0) > 0) {
                const rateInput = this.dom.getElement<HTMLInputElement>('rate');
                const userRate = rateInput ? (parseFloat(rateInput.value) || 12) : 12;
                const rm = userRate / 100 / 12;
                const initialLumpsum = yr1.begin_balance ?? 0;

                let flatBalance = initialLumpsum;
                const flatData: number[] = [];

                for (let i = 0; i < results.length; i++) {
                    for (let m = 0; m < 12; m++) {
                        flatBalance = (flatBalance + baseMonthlySip) * (1 + rm);
                    }
                    flatData.push(Math.round(flatBalance));
                }

                datasets.push({
                    label: 'Flat SIP Baseline (0% Step-Up)',
                    data: flatData,
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148, 163, 184, 0.04)',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    tension: 0.4,
                    cubicInterpolationMode: 'monotone' as const,
                    fill: false,
                    pointRadius: isSinglePoint ? 4 : 0,
                    pointHoverRadius: 5,
                    pointHoverBorderColor: '#94a3b8',
                    pointHoverBackgroundColor: '#ffffff',
                    order: 3,
                });
            }
        }

        // Annual Withdrawal Curve
        if (mode !== 'sip' || enableSwp) {
            datasets.push({
                label: 'Annual Withdrawal',
                data: swp,
                borderColor: THEME_COLORS.financial.withdrawal,
                backgroundColor: THEME_COLORS.chart.swpFillBg,
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: THEME_COLORS.chart.pointBgWhite,
                pointBorderColor: THEME_COLORS.financial.withdrawal,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 6,
                hidden: !enableSwp,
                order: 1,
            });
        }

        // Benchmark Curve Overlays (Nifty, Gold, FD)
        const benchmark = this.getActiveBenchmark();
        if (benchmark !== 'none' && !showWealthMap) {
            let bmRate = 12.0;
            let bmLabel = 'Nifty 50 TRI (12.0%)';
            let bmColor = '#6366f1'; // Indigo-500
            let bmBg = 'rgba(99, 102, 241, 0.05)';

            if (benchmark === 'gold') {
                bmRate = 10.0;
                bmLabel = 'Domestic Gold (10.0%)';
                bmColor = '#d97706'; // Amber-600
                bmBg = 'rgba(217, 119, 6, 0.05)';
            } else if (benchmark === 'fd') {
                bmRate = 6.5;
                bmLabel = 'Bank Fixed Deposit (6.5%)';
                bmColor = '#64748b'; // Slate-500
                bmBg = 'rgba(100, 116, 139, 0.05)';
            }

            const bmCurve = this.computeBenchmarkCurve(results, bmRate);

            datasets.push({
                label: bmLabel,
                data: bmCurve,
                borderColor: bmColor,
                backgroundColor: bmBg,
                borderWidth: 2,
                borderDash: [6, 4],
                tension: 0.4,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointRadius: isSinglePoint ? 4 : 0,
                pointHoverRadius: 5,
                pointHoverBorderColor: bmColor,
                pointHoverBackgroundColor: '#ffffff',
                order: 3,
            });
        }

        // Market Shock Scenario Overlay
        const shockData = this.getShockOverlayData();
        if (shockData && !showWealthMap) {
            const crashIdx = this.getShockOverlayCrashIndex();
            datasets.push({
                label: shockData.label,
                data: shockData.data,
                borderColor: '#e11d48', // Rose-600
                backgroundColor: 'rgba(225, 29, 72, 0.06)',
                borderWidth: 2.5,
                borderDash: [5, 4],
                tension: 0.35,
                cubicInterpolationMode: 'monotone' as const,
                fill: false,
                pointBackgroundColor: '#be123c',
                pointBorderColor: THEME_COLORS.chart.pointBgWhite,
                pointRadius: results.map((_, idx) => (crashIdx !== null && idx === crashIdx) ? 6 : (isSinglePoint ? 4 : 0)),
                pointHoverRadius: 7,
                order: 0,
            });
        }

        return datasets;
    }
}
