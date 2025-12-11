<?php

function get_pdf_html(array $inputs): string
{
    $logo_base64 = $inputs['logo_base64'] ?? null;
    $client_name = htmlspecialchars($inputs['client_name'] ?? 'Valued Client');
    $advisor_name = htmlspecialchars($inputs['advisor_name'] ?? 'Your Financial Advisor');
    $chart_base64 = $inputs['chart_base64'];
    $table_html = $inputs['table_html'];
    $custom_disclaimer = htmlspecialchars($inputs['custom_disclaimer'] ?? '');

    $styles = "
        @page { margin: 30px; }
        body { font-family: 'Helvetica', 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 10px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #e0e7ff; padding-bottom: 20px; margin-bottom: 25px; }
        .header img { max-width: 160px; max-height: 80px; margin-bottom: 15px; }
        .header h1 { font-size: 24px; margin: 0; color: #4338ca; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .header p { font-size: 11px; margin: 8px 0 0; color: #6b7280; }
        .content { margin: 0; }
        h2 { font-size: 15px; color: #4f46e5; border-bottom: 1px solid #e0e7ff; padding-bottom: 8px; margin-top: 30px; margin-bottom: 15px; font-weight: bold; }
        .summary-table { border-collapse: separate; border-spacing: 0; width: 100%; margin-bottom: 25px; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
        .summary-table th, .summary-table td { border-bottom: 1px solid #e5e7eb; padding: 10px 12px; text-align: left; }
        .summary-table th { background-color: #f5f7ff; color: #3730a3; font-weight: bold; border-right: 1px solid #e5e7eb; width: 30%; }
        .summary-table td { color: #111827; }
        .summary-table tr:last-child th, .summary-table tr:last-child td { border-bottom: none; }
        .chart-container { text-align: center; margin-top: 25px; background: #fff; padding: 10px; border: 1px solid #f3f4f6; border-radius: 8px; }
        .chart-container img { width: 100%; height: auto; }
        .results-table-container table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 10px; }
        .results-table-container th, .results-table-container td { padding: 8px; text-align: right; border: 1px solid #e5e7eb; }
        .results-table-container thead th { background-color: #4f46e5; color: #ffffff; font-weight: bold; border-color: #4338ca; }
        .results-table-container tbody tr:nth-child(even) { background-color: #f9fafb; }
        .results-table-container tbody tr:hover { background-color: #f5f7ff; }
        .footer { text-align: center; margin-top: 40px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; }
        .disclaimer { margin-top: 30px; padding: 15px; background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; font-style: italic; color: #92400e; }
        .disclaimer h2 { margin-top: 0; border-bottom: none; color: #92400e; font-size: 11px; margin-bottom: 5px; }
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
                    <td>" . number_format((float) ($inputs['sip'] ?? 0)) . "</td>
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
                    <td>" . number_format((float) ($inputs['swp_withdrawal'] ?? 0)) . "</td>
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
