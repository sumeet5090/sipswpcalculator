<?php

declare(strict_types=1);

namespace Core;

/**
 * PdfReportTemplate
 * Renders an executive, world-class HTML report template for Dompdf.
 * Refactored into modular section renderers adhering to SRP and OCP.
 */
class PdfReportTemplate
{
    /**
     * Render the report HTML template using input parameters.
     *
     * @param array<string, mixed> $inputs
     * @return string
     */
    public static function render(array $inputs): string
    {
        $client_name = htmlspecialchars((string) ($inputs['client_name'] ?? 'Valued Client'));
        $advisor_name = htmlspecialchars((string) ($inputs['advisor_name'] ?? 'Your Financial Advisor'));
        $chart_base64 = (string) ($inputs['chart_base64'] ?? '');
        $table_html = (string) ($inputs['table_html'] ?? '');
        $custom_disclaimer = htmlspecialchars((string) ($inputs['custom_disclaimer'] ?? ''));

        $summary_corpus = (string) ($inputs['summary_corpus'] ?? '0');
        $summary_invested = (string) ($inputs['summary_invested'] ?? '0');
        $multiplier = self::calculateMultiplier((float) ($inputs['raw_invested'] ?? 0), (float) ($inputs['raw_corpus'] ?? 0), $summary_invested, $summary_corpus);

        $proposal_id = 'SWP-' . strtoupper(substr(md5($client_name . date('Y-m-d')), 0, 8));
        $has_swp = ((int) ($inputs['swp_years'] ?? 0) > 0 || (float) ($inputs['swp_withdrawal'] ?? 0) > 0);
        $years_count = max(1, (int) ($inputs['years'] ?? 20));

        $styles = self::getStyles($years_count);
        $headerHtml = self::renderHeader($client_name, $advisor_name, $inputs['logo_base64'] ?? null);
        $metaRibbonHtml = self::renderMetaRibbon($client_name, $advisor_name, $proposal_id);
        $kpiCardsHtml = self::renderKpiCards($inputs, $multiplier, $has_swp);
        $configCardHtml = self::renderConfigCard($inputs, $has_swp);
        $chartHtml = self::renderChartSection($chart_base64);
        $milestonesHtml = self::renderMilestoneGrid($inputs);
        $calloutsHtml = self::renderCalloutsAndFooter((string) ($inputs['currency_symbol'] ?? '₹'), $custom_disclaimer, $proposal_id);

        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Investment Plan - {$client_name}</title>
            <style>{$styles}</style>
        </head>
        <body>
            <div class='top-accent'></div>
            {$headerHtml}
            {$metaRibbonHtml}
            {$kpiCardsHtml}
            {$configCardHtml}
            {$chartHtml}
            {$milestonesHtml}
            <div style='page-break-before: always;'></div>
            <div class='section-heading' style='margin-top: 0;'>Year-by-Year Breakdown</div>
            <div class='results-table-container'>
                {$table_html}
            </div>
            {$calloutsHtml}
        </body>
        </html>";
    }

    private static function calculateMultiplier(float $rawInvested, float $rawCorpus, string $summaryInvested, string $summaryCorpus): string
    {
        if ($rawInvested > 0 && $rawCorpus > 0) {
            return number_format($rawCorpus / $rawInvested, 2) . 'x';
        }
        $cleanCorpus = (float) preg_replace('/[^\d.]/', '', $summaryCorpus);
        $cleanInvested = (float) preg_replace('/[^\d.]/', '', $summaryInvested);
        if ($cleanInvested > 0 && $cleanCorpus > 0) {
            return number_format($cleanCorpus / $cleanInvested, 2) . 'x';
        }
        return '1.00x';
    }

    private static function renderHeader(string $clientName, string $advisorName, ?string $logoBase64): string
    {
        $rightCol = $logoBase64
            ? "<img src='{$logoBase64}' alt='Logo' style='max-width: 140px; max-height: 48px;'>"
            : "<div class='advisor-badge'><div class='advisor-label'>Prepared By Advisor</div><div class='advisor-name'>{$advisorName}</div></div>";

        return "
        <table class='header-table'>
            <tr>
                <td>
                    <div class='doc-title'>Mutual Fund Investment Plan</div>
                    <div class='doc-subtitle'>Personalized Wealth Accumulation & Retirement Strategy</div>
                </td>
                <td style='text-align: right;'>{$rightCol}</td>
            </tr>
        </table>";
    }

    private static function renderMetaRibbon(string $clientName, string $advisorName, string $proposalId): string
    {
        $dateStr = date('d M Y');
        return "
        <div class='meta-ribbon'>
            <table class='meta-table'>
                <tr>
                    <td style='width: 30%;'>Investor: <strong>{$clientName}</strong></td>
                    <td style='width: 35%; text-align: center;'>Advisor: <strong>{$advisorName}</strong></td>
                    <td style='width: 35%; text-align: right;'>Date: <strong>{$dateStr}</strong> | Ref: <strong>{$proposalId}</strong></td>
                </tr>
            </table>
        </div>";
    }

    private static function renderKpiCards(array $inputs, string $multiplier, bool $hasSwp): string
    {
        $invested = (string) ($inputs['summary_invested'] ?? '0');
        $returns = (string) ($inputs['summary_interest'] ?? '0');
        $withdrawn = (string) ($inputs['summary_withdrawn'] ?? '0');
        $corpus = (string) ($inputs['summary_corpus'] ?? '0');

        $swpCol = $hasSwp
            ? "<td class='kpi-card swp' style='width: 25%;'><span class='kpi-label'>Total Withdrawn</span><div class='kpi-val' style='color: #e11d48;'>{$withdrawn}</div></td>"
            : "";

        return "
        <div class='kpi-container'>
            <table class='kpi-table'>
                <tr>
                    <td class='kpi-card invested' style='width: 25%;'>
                        <span class='kpi-label'>Total Invested</span>
                        <div class='kpi-val'>{$invested}</div>
                    </td>
                    <td class='kpi-card returns' style='width: 25%;'>
                        <span class='kpi-label'>Est. Returns</span>
                        <div class='kpi-val' style='color: #059669;'>{$returns}</div>
                    </td>
                    {$swpCol}
                    <td class='kpi-card corpus' style='width: 25%;'>
                        <span class='kpi-label'>Final Corpus</span>
                        <div class='kpi-val' style='color: #0284c7;'>{$corpus}</div>
                    </td>
                    <td class='kpi-card multiplier' style='width: 25%;'>
                        <span class='kpi-label'>Wealth Multiplier</span>
                        <div class='kpi-val' style='color: #4f46e5;'>{$multiplier}</div>
                    </td>
                </tr>
            </table>
        </div>";
    }

    private static function renderConfigCard(array $inputs, bool $hasSwp): string
    {
        $sym = (string) ($inputs['currency_symbol'] ?? '₹');
        $lumpsum = number_format((float) ($inputs['lumpsum'] ?? 0));
        $sip = number_format((float) ($inputs['sip'] ?? 0));
        $years = (int) ($inputs['years'] ?? 0);
        $stepup = (float) ($inputs['stepup'] ?? 0);
        $rate = (float) ($inputs['rate'] ?? 0);

        $phase2Header = $hasSwp ? "<th colspan='2'><span class='phase-badge swp'>Phase 2: Retirement Income (SWP)</span></th>" : "<th colspan='2'></th>";
        $swpCol1 = $hasSwp
            ? "<th>Monthly SWP:</th><td>{$sym}&nbsp;" . number_format((float) ($inputs['swp_withdrawal'] ?? 0)) . "</td>"
            : "<th>Expected Return:</th><td>{$rate}% p.a.</td>";
        $swpCol2 = $hasSwp
            ? "<th>SWP Period:</th><td>" . ($inputs['swp_years'] ?? 0) . " Years</td>"
            : "<th>Annual Step-Up:</th><td>{$stepup}%</td>";
        $swpCol3 = $hasSwp
            ? "<th>SWP Annual Hike:</th><td>" . ($inputs['swp_stepup'] ?? 0) . "%</td>"
            : "<th></th><td></td>";
        $swpCol4 = $hasSwp
            ? "<th>SWP Return Rate:</th><td>" . ($inputs['swp_rate'] ?? 8) . "% p.a.</td>"
            : "<th></th><td></td>";

        return "
        <div class='section-heading'>Strategy Parameters & Allocation</div>
        <div class='config-card'>
            <table class='config-table'>
                <tr>
                    <th colspan='2'><span class='phase-badge sip'>Phase 1: Accumulation (SIP)</span></th>
                    {$phase2Header}
                </tr>
                <tr>
                    <th>Initial Lumpsum:</th>
                    <td>{$sym}&nbsp;{$lumpsum}</td>
                    {$swpCol1}
                </tr>
                <tr>
                    <th>Monthly SIP:</th>
                    <td>{$sym}&nbsp;{$sip}</td>
                    {$swpCol2}
                </tr>
                <tr>
                    <th>SIP Period:</th>
                    <td>{$years} Years</td>
                    {$swpCol3}
                </tr>
                <tr>
                    <th>Annual Step-Up:</th>
                    <td>{$stepup}%</td>
                    {$swpCol4}
                </tr>
            </table>
        </div>";
    }

    private static function renderChartSection(string $chartBase64): string
    {
        if (empty($chartBase64)) {
            return '';
        }
        return "
        <div class='section-heading'>Wealth Projection Trajectory</div>
        <div class='chart-box'>
            <img src='{$chartBase64}' alt='Growth Chart'>
        </div>";
    }

    private static function renderMilestoneGrid(array $inputs): string
    {
        $milestones = self::generateMilestones($inputs);
        if (empty($milestones)) {
            return '';
        }
        $colWidth = floor(100 / max(1, count($milestones))) . '%';
        $cardsHtml = '';
        foreach ($milestones as $m) {
            $cardsHtml .= "
                <td class='milestone-card' style='width: {$colWidth};'>
                    <span class='milestone-badge'>{$m['badge']}</span>
                    <div class='milestone-val'>{$m['target_formatted']}</div>
                    <div class='milestone-sub'>Achieved in <strong>Year {$m['year']}</strong></div>
                </td>";
        }
        return "
        <div class='section-heading'>Key Wealth Milestones</div>
        <div class='milestones-container'>
            <table class='milestones-table'>
                <tr>{$cardsHtml}</tr>
            </table>
        </div>";
    }

    private static function renderCalloutsAndFooter(string $currencySym, string $customDisclaimer, string $proposalId): string
    {
        $inflationCallout = ($currencySym === '₹' || $currencySym === 'INR')
            ? "<div class='purchasing-power'><strong>Cost of Living & Inflation Protection</strong>In India, education and healthcare costs historically rise at <strong>~8-10% annually</strong>. Utilizing an annual SIP step-up strategy helps your capital outpace inflation, protecting your family's real purchasing power.</div>"
            : "";

        $disclaimerText = $customDisclaimer ?: "Mutual fund investments are subject to market risks. Read all scheme-related documents carefully before investing. Past performance is not an indicator of future returns. Projections generated in this report are for illustrative financial planning purposes only.";

        return "
        {$inflationCallout}
        <div class='disclaimer'>
            <strong>Important Disclaimer & Risk Disclosure</strong>
            " . nl2br($disclaimerText) . "
        </div>
        <div class='doc-footer'>
            Generated securely via SIP & SWP Planner (https://sipswpcalculator.com) \| Proposal Ref: {$proposalId}
        </div>";
    }

    private static function generateMilestones(array $inputs): array
    {
        $milestoneTargets = [
            10000000 => 'First ₹1 Crore',
            50000000 => 'First ₹5 Crores',
            100000000 => 'First ₹10 Crores',
            500000000 => 'First ₹50 Crores',
            1000000000 => 'First ₹100 Crores',
            5000000000 => 'First ₹500 Crores',
            10000000000 => 'First ₹1,000 Crores',
            100000000000 => 'First ₹10,000 Crores',
        ];

        $found = [];

        // Scan pre-computed yearly results from InvestmentCalculator
        $combinedResults = $inputs['combined_results'] ?? [];
        if (is_array($combinedResults)) {
            foreach ($combinedResults as $row) {
                $y = (int) ($row['year'] ?? 0);
                $corpus = (float) ($row['combined_total'] ?? 0);
                foreach ($milestoneTargets as $target => $label) {
                    if (!isset($found[$target]) && $corpus >= $target) {
                        $found[$target] = [
                            'badge' => $label,
                            'target_formatted' => CurrencyHelper::formatInr($target),
                            'year' => $y,
                        ];
                    }
                }
            }
        }

        return array_slice(array_values($found), 0, 4);
    }

    private static function getStyles(int $yearsCount): string
    {
        $tablePadding = ($yearsCount > 25) ? '3px 6px' : '5px 8px';
        $thPadding = ($yearsCount > 25) ? '5px 6px' : '7px 8px';
        $tableFontSize = ($yearsCount > 25) ? '7.5px' : '8.5px';
        $boxMargin = ($yearsCount > 25) ? '10px' : '16px';

        return "
            @page { margin: 24px 32px 28px 32px; }
            body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 9.5px; line-height: 1.45; background-color: #ffffff; margin: 0; padding: 0; }
            .top-accent { height: 4px; background: #059669; margin-bottom: 16px; border-radius: 2px; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
            .header-table td { vertical-align: middle; }
            .doc-title { font-size: 20px; font-weight: bold; color: #0f172a; letter-spacing: -0.3px; margin: 0 0 2px 0; }
            .doc-subtitle { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
            .advisor-badge { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 6px; text-align: right; display: inline-block; }
            .advisor-label { font-size: 8px; color: #059669; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; }
            .advisor-name { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 1px; }
            .meta-ribbon { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 14px; margin-bottom: 16px; }
            .meta-table { width: 100%; border-collapse: collapse; font-size: 9px; }
            .meta-table td { color: #64748b; }
            .meta-table td strong { color: #0f172a; font-weight: bold; }
            .kpi-container { width: 100%; margin-bottom: 16px; }
            .kpi-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
            .kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-top: 3px solid #64748b; padding: 10px 6px; border-radius: 6px; text-align: center; }
            .kpi-card.invested { border-top-color: #0f172a; background: #f8fafc; }
            .kpi-card.returns { border-top-color: #059669; background: #f0fdf4; }
            .kpi-card.swp { border-top-color: #e11d48; background: #fff1f2; }
            .kpi-card.corpus { border-top-color: #0284c7; background: #f0f9ff; }
            .kpi-card.multiplier { border-top-color: #6366f1; background: #f5f3ff; }
            .kpi-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; margin-bottom: 3px; display: block; font-weight: bold; }
            .kpi-val { font-size: 13.5px; font-weight: bold; color: #0f172a; margin: 0; white-space: nowrap; }
            .section-heading { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.6px; padding-bottom: 4px; border-bottom: 2px solid #059669; margin-top: 16px; margin-bottom: 10px; page-break-after: avoid; }
            .config-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; page-break-inside: avoid; }
            .config-table { width: 100%; border-collapse: collapse; font-size: 9px; }
            .config-table th { text-align: left; color: #64748b; font-weight: bold; width: 25%; padding: 4px 0; }
            .config-table td { text-align: left; color: #0f172a; font-weight: bold; width: 25%; padding: 4px 0; white-space: nowrap; }
            .phase-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
            .phase-badge.sip { background: #dcfce7; color: #166534; }
            .phase-badge.swp { background: #ffe4e6; color: #9f1239; }
            .chart-box { text-align: center; margin: 6px 0 8px 0; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px; background: #ffffff; page-break-inside: avoid; width: 100%; box-sizing: border-box; }
            .chart-box img { width: 100%; height: auto; max-height: 475px; display: block; margin: 0 auto; object-fit: contain; }
            .milestones-container { margin: 8px 0 0 0; page-break-inside: avoid; }
            .milestones-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; table-layout: fixed; }
            .milestone-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #059669; padding: 6px 6px; border-radius: 6px; text-align: center; vertical-align: top; }
            .milestone-badge { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #15803d; margin-bottom: 2px; display: block; white-space: nowrap; }
            .milestone-val { font-size: 11px; font-weight: bold; color: #0f172a; margin: 0; white-space: nowrap; }
            .milestone-sub { font-size: 7.5px; color: #475569; margin-top: 2px; white-space: nowrap; }
            .results-table-container { margin-top: 10px; }
            .results-table-container table { width: 100%; border-collapse: collapse; font-size: {$tableFontSize}; }
            .results-table-container table thead { display: table-header-group; }
            .results-table-container tr { page-break-inside: avoid; }
            .results-table-container th { background-color: #0f172a; color: #ffffff; padding: {$thPadding}; text-align: right; font-weight: bold; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; }
            .results-table-container th:first-child { text-align: left; border-top-left-radius: 4px; }
            .results-table-container th:last-child { border-top-right-radius: 4px; }
            .results-table-container td { padding: {$tablePadding}; text-align: right; border-bottom: 1px solid #e2e8f0; color: #334155; white-space: nowrap; }
            .results-table-container tr:nth-child(even) td { background-color: #f8fafc; }
            .results-table-container td:first-child { text-align: left; font-weight: bold; color: #0f172a; }
            .purchasing-power { margin-top: {$boxMargin}; padding: 10px 14px; background-color: #fffbeb; border-left: 4px solid #f59e0b; font-size: 8.5px; color: #92400e; border-radius: 0 6px 6px 0; page-break-inside: avoid; }
            .purchasing-power strong { display: block; margin-bottom: 2px; font-size: 9px; text-transform: uppercase; color: #b45309; font-weight: bold; }
            .disclaimer { margin-top: {$boxMargin}; padding: 10px 14px; background-color: #fef2f2; border-left: 4px solid #e11d48; font-size: 8px; color: #991b1b; border-radius: 0 6px 6px 0; page-break-inside: avoid; }
            .disclaimer strong { display: block; margin-bottom: 2px; text-transform: uppercase; font-size: 8.5px; font-weight: bold; }
            .doc-footer { margin-top: 18px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 7.5px; }
        ";
    }
}
