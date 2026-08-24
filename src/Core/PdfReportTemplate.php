<?php

declare(strict_types=1);

namespace Core;

/**
 * PdfReportTemplate
 * Renders an executive, world-class HTML report template for Dompdf.
 * Refactored into modular section renderers adhering to SRP and OCP.
 */
class PdfReportTemplate implements PdfTemplateInterface
{
    private CurrencyFormatterInterface $currencyFormatter;
    private ?array $milestoneConfig;
    private PdfReportStylesheet $stylesheet;

    public function __construct(
        CurrencyFormatterInterface $currencyFormatter,
        ?array $milestoneConfig = null,
        ?PdfReportStylesheet $stylesheet = null
    ) {
        $this->currencyFormatter = $currencyFormatter;
        $this->milestoneConfig = $milestoneConfig;
        $this->stylesheet = $stylesheet ?? new PdfReportStylesheet();
    }

    /**
     * Render the report HTML template using input parameters.
     *
     * @param array<string, mixed> $inputs
     * @return string
     */
    public function render(array $inputs): string
    {
        $client_name = htmlspecialchars((string) ($inputs['client_name'] ?? 'Valued Client'));
        $advisor_name = htmlspecialchars((string) ($inputs['advisor_name'] ?? 'Your Financial Advisor'));
        $chart_base64 = (string) ($inputs['chart_base64'] ?? '');
        $table_html = (string) ($inputs['table_html'] ?? '');
        $custom_disclaimer = htmlspecialchars((string) ($inputs['custom_disclaimer'] ?? ''));

        $multiplier = self::calculateMultiplier(
            (float) ($inputs['raw_invested'] ?? 0),
            (float) ($inputs['raw_corpus'] ?? 0),
            (float) ($inputs['raw_withdrawn'] ?? 0)
        );

        $proposal_id = 'SWP-' . strtoupper(substr(md5($client_name . date('Y-m-d')), 0, 8));
        $has_swp = ((int) ($inputs['swp_years'] ?? 0) > 0 || (float) ($inputs['swp_withdrawal'] ?? 0) > 0);
        $years_count = max(1, (int) ($inputs['years'] ?? 20));

        $styles = $this->stylesheet->getStyles($years_count);
        $headerHtml = self::renderHeader($client_name, $advisor_name, $inputs['logo_base64'] ?? null);
        $metaRibbonHtml = self::renderMetaRibbon($client_name, $advisor_name, $proposal_id);
        $kpiCardsHtml = self::renderKpiCards($inputs, $multiplier, $has_swp);
        $configCardHtml = self::renderConfigCard($inputs, $has_swp);
        $chartHtml = self::renderChartSection($chart_base64);
        $milestonesHtml = $this->renderMilestoneGrid($inputs);
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

    private static function calculateMultiplier(
        float $rawInvested,
        float $rawCorpus,
        float $rawWithdrawn
    ): string {
        $totalDelivered = $rawCorpus + $rawWithdrawn;
        if ($rawInvested > 0 && $totalDelivered > 0) {
            return number_format($totalDelivered / $rawInvested, 2) . 'x';
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
        $inflation = (float) ($inputs['inflation'] ?? 0);

        if ($hasSwp) {
            $swpWithdrawal = number_format((float) ($inputs['swp_withdrawal'] ?? 0));
            $swpYears = (int) ($inputs['swp_years'] ?? 0);
            $swpStepup = (float) ($inputs['swp_stepup'] ?? 0);
            $swpRate = (float) ($inputs['swp_rate'] ?? 8);

            return "
            <div class='section-heading'>Strategy Parameters & Allocation</div>
            <div class='config-card'>
                <table class='config-table'>
                    <tr>
                        <th colspan='2'><span class='phase-badge sip'>Phase 1: Accumulation (SIP)</span></th>
                        <th colspan='2'><span class='phase-badge swp'>Phase 2: Retirement Income (SWP)</span></th>
                    </tr>
                    <tr>
                        <th>Initial Lumpsum:</th>
                        <td>{$sym}&nbsp;{$lumpsum}</td>
                        <th>Monthly SWP:</th>
                        <td>{$sym}&nbsp;{$swpWithdrawal}</td>
                    </tr>
                    <tr>
                        <th>Monthly SIP:</th>
                        <td>{$sym}&nbsp;{$sip}</td>
                        <th>SWP Period:</th>
                        <td>{$swpYears} Years</td>
                    </tr>
                    <tr>
                        <th>SIP Period:</th>
                        <td>{$years} Years</td>
                        <th>SWP Annual Hike:</th>
                        <td>{$swpStepup}%</td>
                    </tr>
                    <tr>
                        <th>Annual Step-Up:</th>
                        <td>{$stepup}%</td>
                        <th>SWP Return Rate:</th>
                        <td>{$swpRate}% p.a.</td>
                    </tr>
                    <tr>
                        <th>Expected Return:</th>
                        <td>{$rate}% p.a.</td>
                        <th>Expected Inflation:</th>
                        <td>{$inflation}% p.a.</td>
                    </tr>
                </table>
            </div>";
        }

        return "
        <div class='section-heading'>Strategy Parameters & Allocation</div>
        <div class='config-card'>
            <table class='config-table'>
                <tr>
                    <th colspan='4'><span class='phase-badge sip'>Wealth Accumulation (SIP) Parameters</span></th>
                </tr>
                <tr>
                    <th>Initial Lumpsum:</th>
                    <td>{$sym}&nbsp;{$lumpsum}</td>
                    <th>Monthly SIP:</th>
                    <td>{$sym}&nbsp;{$sip}</td>
                </tr>
                <tr>
                    <th>SIP Period:</th>
                    <td>{$years} Years</td>
                    <th>Expected Return:</th>
                    <td>{$rate}% p.a.</td>
                </tr>
                <tr>
                    <th>Annual Step-Up:</th>
                    <td>{$stepup}%</td>
                    <th>Expected Inflation:</th>
                    <td>{$inflation}% p.a.</td>
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

    private function renderMilestoneGrid(array $inputs): string
    {
        $milestones = $this->generateMilestones($inputs);
        if (empty($milestones)) {
            return '';
        }
        $colWidth = floor(100 / max(1, count($milestones))) . '%';
        $cardsHtml = '';
        foreach ($milestones as $m) {
            $cardsHtml .= "
                <td style='width: {$colWidth}; padding: 0 4px;'>
                    <div class='milestone-badge'>{$m['badge']}</div>
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
            Generated securely via SIP & SWP Planner (https://sipswpcalculator.com) | Proposal Ref: {$proposalId}
        </div>";
    }

    private function generateMilestones(array $inputs): array
    {
        $milestoneTargets = [];
        if (is_array($this->milestoneConfig)) {
            foreach ($this->milestoneConfig as $item) {
                if (isset($item['value'], $item['label'])) {
                    $milestoneTargets[(int) $item['value']] = 'First ' . (string) $item['label'];
                }
            }
        }

        if (empty($milestoneTargets)) {
            $milestoneTargets = [
                10000000 => 'First ₹1 Crore',
                50000000 => 'First ₹5 Crores',
                100000000 => 'First ₹10 Crores',
                500000000 => 'First ₹50 Crores',
                1000000000 => 'First ₹100 Crores',
                5000000000 => 'First ₹500 Crores',
            ];
        }

        $found = [];

        // Scan pre-computed yearly results from InvestmentCalculator
        $combinedResults = $inputs['combined_results'] ?? [];
        $showPostTax = (bool) ($inputs['show_post_tax'] ?? false);
        if (is_array($combinedResults)) {
            foreach ($combinedResults as $row) {
                $y = (int) ($row['year'] ?? 0);
                $corpus = $showPostTax
                    ? (float) ($row['post_tax_total'] ?? $row['combined_total'] ?? 0)
                    : (float) ($row['combined_total'] ?? 0);
                foreach ($milestoneTargets as $target => $label) {
                    if (!isset($found[$target]) && $corpus >= $target) {
                        $found[$target] = [
                            'badge' => $label,
                            'target_formatted' => $this->currencyFormatter->format($target),
                            'year' => $y,
                        ];
                    }
                }
            }
        }

        return array_slice(array_values($found), 0, 4);
    }
}
