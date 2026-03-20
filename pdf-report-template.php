<?php

function get_pdf_html(array $inputs): string
{
    $logo_base64 = $inputs['logo_base64'] ?? null;
    $client_name = htmlspecialchars($inputs['client_name'] ?? 'Valued Client');
    $advisor_name = htmlspecialchars($inputs['advisor_name'] ?? 'Your Financial Advisor');
    $chart_base64 = $inputs['chart_base64'];
    $table_html = $inputs['table_html'];
    $custom_disclaimer = htmlspecialchars($inputs['custom_disclaimer'] ?? '');

    // ── SUMMARY METRICS ──
    $summary_invested = $inputs['summary_invested'] ?? '0';
    $summary_interest = $inputs['summary_interest'] ?? '0';
    $summary_withdrawn = $inputs['summary_withdrawn'] ?? '0';
    $summary_corpus = $inputs['summary_corpus'] ?? '0';
    $currency_sym = $inputs['currency_symbol'] ?? '$';

    // Does the plan include SWP? (Basic heuristic based on value)
    $has_swp = ((int)$inputs['swp_years'] > 0 || (float)$inputs['swp_withdrawal'] > 0);

    $styles = "
        @page { margin: 40px; }
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.5; background-color: #f8fafc; }
        .page { background: #ffffff; padding: 0px; border-radius: 4px; }
        
        /* Typography & Colors */
        .text-indigo { color: #4f46e5; }
        .text-emerald { color: #10b981; }
        .text-slate { color: #64748b; }
        .text-rose { color: #e11d48; }

        /* Header */
        .header { width: 100%; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 60%; }
        .header-right { width: 40%; text-align: right; }
        .header h1 { font-size: 24px; margin: 0; color: #1e293b; font-weight: bold; letter-spacing: -0.5px; }
        .header-logo { max-width: 150px; max-height: 60px; }
        .meta-text { font-size: 10px; color: #64748b; margin-top: 5px; }
        
        /* Summary Boxes (Flexbox/Grid substitute using tables for Dompdf compatibility) */
        .summary-wrap { width: 100%; margin-bottom: 30px; }
        .summary-boxes { width: 100%; border-collapse: separate; border-spacing: 12px 0; }
        .summary-box { background-color: #f1f5f9; padding: 15px; border-radius: 8px; border-left: 4px solid #4f46e5; width: " . ($has_swp ? '25%' : '33.3%') . "; text-align: center; }
        .summary-box.gains { border-left-color: #10b981; }
        .summary-box.swp { border-left-color: #e11d48; }
        .summary-box.wealth { border-left-color: #3b82f6; background-color: #eef2ff; }
        .box-title { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 5px; display: block; }
        .box-value { font-size: 18px; font-weight: bold; color: #0f172a; margin: 0; }
        
        /* Sections */
        h2 { font-size: 14px; color: #4f46e5; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; margin-top: 30px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Input Data Table */
        .input-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 10px; }
        .input-table th, .input-table td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .input-table th { text-align: left; color: #64748b; font-weight: normal; width: 25%; }
        .input-table td { text-align: left; color: #0f172a; font-weight: bold; width: 25%; }
        
        /* Chart */
        .chart-container { text-align: center; margin: 25px 0; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; background: #fff; }
        .chart-container img { max-width: 100%; height: auto; }
        
        /* Results Table */
        .results-table-container { margin-top: 20px; }
        .results-table-container table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .results-table-container th { background-color: #4f46e5; color: #ffffff; padding: 10px; text-align: right; font-weight: bold; }
        .results-table-container th:first-child { text-align: left; border-top-left-radius: 4px; }
        .results-table-container th:last-child { border-top-right-radius: 4px; }
        .results-table-container td { padding: 8px 10px; text-align: right; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .results-table-container tr:nth-child(even) td { background-color: #f8fafc; }
        .results-table-container td:first-child { text-align: left; font-weight: bold; color: #0f172a; }
        
        /* Footer & Disclaimer */
        .disclaimer { margin-top: 30px; padding: 15px; background-color: #fef2f2; border-left: 4px solid #ef4444; font-size: 9px; color: #7f1d1d; border-radius: 0 4px 4px 0; }
        .disclaimer strong { display: block; margin-bottom: 5px; text-transform: uppercase; font-size: 10px; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 8px; }
    ";

    $html = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Wealth Projection - {$client_name}</title>
        <style>{$styles}</style>
    </head>
    <body class='page'>
        
        <div class='header'>
            <table class='header-table'>
                <tr>
                    <td class='header-left'>
                        <h1 class='text-indigo'>Wealth Journey Report</h1>
                        <div class='meta-text'>
                            Prepared for: <strong style='color:#0f172a;'>{$client_name}</strong><br>
                            Prepared by: <strong style='color:#0f172a;'>{$advisor_name}</strong><br>
                            Date: " . date('M j, Y') . "
                        </div>
                    </td>
                    <td class='header-right'>";
    if ($logo_base64) {
        $html .= "<img src='{$logo_base64}' alt='Logo' class='header-logo'>";
    }
    $html .= "      </td>
                </tr>
            </table>
        </div>

        <div class='summary-wrap'>
            <table class='summary-boxes'>
                <tr>
                    <td class='summary-box'>
                        <span class='box-title'>Total Investment</span>
                        <div class='box-value'>{$summary_invested}</div>
                    </td>
                    <td class='summary-box gains'>
                        <span class='box-title'>Est. Gains</span>
                        <div class='box-value text-emerald'>{$summary_interest}</div>
                    </td>";

    if ($has_swp) {
        $html .= "
                    <td class='summary-box swp'>
                        <span class='box-title'>Total Withdrawn</span>
                        <div class='box-value text-rose'>{$summary_withdrawn}</div>
                    </td>";
    }

    $html .= "      <td class='summary-box wealth'>
                        <span class='box-title'>Final Wealth</span>
                        <div class='box-value text-indigo'>{$summary_corpus}</div>
                    </td>
                </tr>
            </table>
        </div>

        <h2>Investment Configuration</h2>
        <table class='input-table'>
            <tr>
                <th>Monthly SIP:</th>
                <td>{$currency_sym}" . number_format((float) ($inputs['sip'] ?? 0)) . "</td>
                <th>SIP Period:</th>
                <td>" . ($inputs['years'] ?? 0) . " Years</td>
            </tr>
            <tr>
                <th>Expected Return:</th>
                <td>" . ($inputs['rate'] ?? 0) . "% p.a.</td>
                <th>Annual Step-up:</th>
                <td>" . ($inputs['stepup'] ?? 0) . "%</td>
            </tr>";

    if ($has_swp) {
        $html .= "
            <tr>
                <th>Monthly SWP:</th>
                <td>{$currency_sym}" . number_format((float) ($inputs['swp_withdrawal'] ?? 0)) . "</td>
                <th>SWP Period:</th>
                <td>" . ($inputs['swp_years'] ?? 0) . " Years</td>
            </tr>
            <tr>
                <th>SWP Annual Hike:</th>
                <td colspan='3'>" . ($inputs['swp_stepup'] ?? 0) . "%</td>
            </tr>";
    }

    $html .= "
        </table>

        <h2>Growth Trajectory</h2>
        <div class='chart-container'>
            <img src='{$chart_base64}' alt='Growth Chart'>
        </div>

        <h2>Yearly Breakdown</h2>
        <div class='results-table-container'>
            {$table_html}
        </div>";

    if ($custom_disclaimer) {
        $html .= "
        <div class='disclaimer'>
            <strong>Important Disclaimer</strong>
            " . nl2br($custom_disclaimer) . "
        </div>";
    }

    $html .= "
        <div class='footer'>
            Generated securely by the Advanced SIP & SWP Calculator (https://sipswpcalculator.com)<br>
            Values are projected estimates based on constant compounding and do not guarantee future performance.
        </div>
    </body>
    </html>";

    return $html;
}
