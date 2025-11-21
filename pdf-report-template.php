<?php

function get_pdf_html(array $inputs): string {
    $logo_base64 = $inputs['logo_base64'] ?? null;
    $client_name = htmlspecialchars($inputs['client_name'] ?? 'Valued Client');
    $advisor_name = htmlspecialchars($inputs['advisor_name'] ?? 'Your Financial Advisor');
    $chart_base64 = $inputs['chart_base64'];
    $table_html = $inputs['table_html'];
    $custom_disclaimer = htmlspecialchars($inputs['custom_disclaimer'] ?? '');

    $styles = "
        @page { margin: 25px; }
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; color: #333; font-size: 10px; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .header img { max-width: 150px; max-height: 75px; margin-bottom: 10px; }
        .header h1 { font-size: 22px; margin: 0; color: #1a202c; }
        .header p { font-size: 12px; margin: 5px 0 0; color: #718096; }
        .content { margin: 0; }
        h2 { font-size: 16px; color: #2d3748; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 25px; margin-bottom: 15px; }
        .summary-table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .summary-table th, .summary-table td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        .summary-table th { background-color: #f8fafc; font-weight: bold; }
        .chart-container { text-align: center; margin-top: 20px; }
        .chart-container img { width: 100%; height: auto; }
        .results-table-container table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .results-table-container th, .results-table-container td { padding: 5px 8px; text-align: right; border: 1px solid #e2e8f0;}
        .results-table-container thead th { background-color: #f1f5f9; font-weight: bold; }
        .results-table-container tbody tr:nth-child(even) { background-color: #f8fafc; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; font-size: 9px; color: #718096; }
        .disclaimer { margin-top: 20px; font-style: italic; }
    ";

    $html = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Financial Report for {$client_name}</title>
        <style>{$styles}</style>
    </head>
    <body>
        <div class='header'>";
    
    if ($logo_base64) {
        $html .= "<img src='{$logo_base64}' alt='Advisor Logo'>";
    }
    
    $html .= "
            <h1>Financial Projection Report</h1>
            <p>Specially prepared for <strong>{$client_name}</strong> by <strong>{$advisor_name}</strong></p>
        </div>

        <div class='content'>
            <h2>Calculation Inputs</h2>
            <table class='summary-table'>
                <tr>
                    <th>Monthly Investment</th>
                    <td>" . number_format((float)($inputs['sip'] ?? 0)) . "</td>
                    <th>Investment Period</th>
                    <td>" . ($inputs['years'] ?? 0) . " Years</td>
                </tr>
                <tr>
                    <th>Expected Return Rate</th>
                    <td>" . ($inputs['rate'] ?? 0) . "% p.a.</td>
                    <th>Annual Step-up</th>
                    <td>" . ($inputs['stepup'] ?? 0) . "%</td>
                </tr>
                 <tr>
                    <th>Monthly Withdrawal</th>
                    <td>" . number_format((float)($inputs['swp_withdrawal'] ?? 0)) . "</td>
                    <th>Annual Withdrawal Increase</th>
                    <td>" . ($inputs['swp_stepup'] ?? 0) . "%</td>
                </tr>
                <tr>
                    <th>Withdrawal Period</th>
                    <td colspan='3'>" . ($inputs['swp_years'] ?? 0) . " Years</td>
                </tr>
            </table>

            <h2>Projected Investment Journey</h2>
            <div class='chart-container'>
                <img src='{$chart_base64}' alt='Investment Journey Chart'>
            </div>

            <h2>Yearly Breakdown</h2>
            <div class='results-table-container'>
                {$table_html}
            </div>";

    if ($custom_disclaimer) {
        $html .= "<div class='disclaimer'><h2>Disclaimer</h2><p>{$custom_disclaimer}</p></div>";
    }

    $html .= "
        </div>
        <div class='footer'>
            <p>This report was generated on " . date('F j, Y') . " using the Advanced SIP & SWP Calculator. It is for illustrative purposes only and should not be considered as financial advice. Projections are based on the inputs provided and are not guaranteed.</p>
        </div>
    </body>
    </html>";

    return $html;
}