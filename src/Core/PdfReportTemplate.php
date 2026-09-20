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
    private PdfReportTableBuilder $tableBuilder;
    private ?ViewRenderer $viewRenderer;

    public function __construct(
        CurrencyFormatterInterface $currencyFormatter,
        ?array $milestoneConfig = null,
        ?PdfReportStylesheet $stylesheet = null,
        ?PdfReportTableBuilder $tableBuilder = null,
        ?ViewRenderer $viewRenderer = null
    ) {
        $this->currencyFormatter = $currencyFormatter;
        $this->milestoneConfig = $milestoneConfig;
        $this->stylesheet = $stylesheet ?? new PdfReportStylesheet();
        $this->tableBuilder = $tableBuilder ?? new PdfReportTableBuilder($this->currencyFormatter);
        $this->viewRenderer = $viewRenderer;
    }

    private function getViewRenderer(): ViewRenderer
    {
        if ($this->viewRenderer === null) {
            $manifestPath = dirname(__DIR__, 2) . '/dist/.vite/manifest.json';
            $viewsPath = dirname(__DIR__) . '/Views';
            $this->viewRenderer = new ViewRenderer(
                new ViteHelper('production', '127.0.0.1', 5173, $manifestPath),
                'testing',
                'https://sipswpcalculator.com',
                $viewsPath,
                null,
                $this->currencyFormatter
            );
        }
        return $this->viewRenderer;
    }

    /**
     * Render the report HTML template using input parameters.
     *
     * @param array<string, mixed> $inputs
     * @return string
     */
    public function render(array $inputs): string
    {
        $client_name = (string) ($inputs['client_name'] ?? 'Valued Client');
        $advisor_name = (string) ($inputs['advisor_name'] ?? 'Your Financial Advisor');
        $chart_base64 = (string) ($inputs['chart_base64'] ?? '');
        $custom_disclaimer = (string) ($inputs['custom_disclaimer'] ?? '');

        $multiplier = self::calculateMultiplier(
            (float) ($inputs['raw_invested'] ?? 0),
            (float) ($inputs['raw_corpus'] ?? 0),
            (float) ($inputs['raw_withdrawn'] ?? 0)
        );

        $proposal_id = 'SWP-' . strtoupper(substr(md5($client_name . date('Y-m-d')), 0, 8));
        $has_swp = ((int) ($inputs['swp_years'] ?? 0) > 0 || (float) ($inputs['swp_withdrawal'] ?? 0) > 0);
        $sym = (string) ($inputs['currency_symbol'] ?? '₹');

        if (!empty($inputs['combined_results']) && is_array($inputs['combined_results'])) {
            $table_html = $this->tableBuilder->build($inputs['combined_results'], $sym, $has_swp);
        } else {
            $table_html = (string) ($inputs['table_html'] ?? '');
        }

        $years_count = max(1, (int) ($inputs['years'] ?? 20));
        $styles = $this->stylesheet->getStyles($years_count);

        $milestones = $this->generateMilestones($inputs);
        $milestoneColWidth = !empty($milestones)
            ? floor(100 / max(1, count($milestones))) . '%'
            : '25%';

        $disclaimerText = $custom_disclaimer ?: "Mutual fund investments are subject to market risks. Read all scheme-related documents carefully before investing. Past performance is not an indicator of future returns. Projections generated in this report are for illustrative financial planning purposes only.";
        $hasInflationCallout = ($sym === '₹' || $sym === 'INR');

        $configData = [
            'sym' => $sym,
            'lumpsum' => number_format((float) ($inputs['lumpsum'] ?? 0)),
            'sip' => number_format((float) ($inputs['sip'] ?? 0)),
            'years' => (int) ($inputs['years'] ?? 0),
            'stepup' => (float) ($inputs['stepup'] ?? 0),
            'rate' => (float) ($inputs['rate'] ?? 0),
            'inflation' => (float) ($inputs['inflation'] ?? 0),
            'swp_withdrawal' => number_format((float) ($inputs['swp_withdrawal'] ?? 0)),
            'swp_years' => (int) ($inputs['swp_years'] ?? 0),
            'swp_stepup' => (float) ($inputs['swp_stepup'] ?? 0),
            'swp_rate' => (float) ($inputs['swp_rate'] ?? 8),
        ];

        $kpis = [
            'invested' => (string) ($inputs['summary_invested'] ?? '0'),
            'returns' => (string) ($inputs['summary_interest'] ?? '0'),
            'withdrawn' => (string) ($inputs['summary_withdrawn'] ?? '0'),
            'corpus' => (string) ($inputs['summary_corpus'] ?? '0'),
        ];

        $viewData = [
            'client_name' => $client_name,
            'advisor_name' => $advisor_name,
            'date_str' => date('d M Y'),
            'proposal_id' => $proposal_id,
            'logo_base64' => $inputs['logo_base64'] ?? null,
            'chart_base64' => $chart_base64,
            'multiplier' => $multiplier,
            'has_swp' => $has_swp,
            'styles' => $styles,
            'table_html' => $table_html,
            'kpis' => $kpis,
            'config' => $configData,
            'milestones' => $milestones,
            'milestone_col_width' => $milestoneColWidth,
            'has_inflation_callout' => $hasInflationCallout,
            'disclaimer_text' => $disclaimerText,
        ];

        return $this->getViewRenderer()->render('pdf/report', $viewData);
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
