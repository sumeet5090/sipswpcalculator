<?php
// Include Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/pdf-report-template.php';

// Reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

try {
    // --- Data Collection ---
    $inputs = [
        'client_name' => $_POST['clientName'] ?? 'N/A',
        'advisor_name' => $_POST['advisorName'] ?? 'N/A',
        'custom_disclaimer' => $_POST['customDisclaimer'] ?? '',
        'chart_base64' => $_POST['chartData'] ?? '',
        'table_html' => $_POST['tableHtml'] ?? '<table><tr><td>No data</td></tr></table>',
        'sip' => $_POST['sip'] ?? 0,
        'years' => $_POST['years'] ?? 0,
        'rate' => $_POST['rate'] ?? 0,
        'stepup' => $_POST['stepup'] ?? 0,
        'swp_withdrawal' => $_POST['swp_withdrawal'] ?? 0,
        'swp_stepup' => $_POST['swp_stepup'] ?? 0,
        'swp_years' => $_POST['swp_years'] ?? 0,
        'logo_base64' => null,
    ];

    // Handle file upload for the logo
    if (isset($_FILES['advisorLogo']) && $_FILES['advisorLogo']['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['advisorLogo']['tmp_name'];
        $type = $_FILES['advisorLogo']['type'];
        $data = file_get_contents($tmp_name);
        $inputs['logo_base64'] = 'data:' . $type . ';base64,' . base64_encode($data);
    }
    
    // --- HTML Generation ---
    $html = get_pdf_html($inputs);
    
    // --- PDF Generation ---
    $options = new Options();
    // Enabling remote requests is required for Dompdf to be able to load images from data URIs
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the HTML as PDF
    $dompdf->render();
    
    // --- Output PDF ---
    // Stream the file to the browser for download.
    $dompdf->stream(
        "Financial_Report_for_{$inputs['client_name']}.pdf", 
        ["Attachment" => 1] // 1 = download, 0 = preview
    );

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    die('An error occurred during PDF generation.');
}