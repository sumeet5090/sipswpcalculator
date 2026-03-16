<?php
// ============================================================
// SECURITY: generate-pdf.php
// Hardened: CSRF, rate limiting, file upload validation,
//           HTML sanitization, SSRF prevention
// ============================================================
declare(strict_types=1);

// Include Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/pdf-report-template.php';

// Reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// ── SECURITY: Only allow POST requests ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// ── SECURITY: CSRF token validation ──
session_start();
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Forbidden: Invalid security token. Please reload the page and try again.');
}

// ── SECURITY: Rate limiting (max 10 PDFs per minute per IP) ──
$rate_limit_dir = sys_get_temp_dir() . '/sipswp_rate_limits/';
if (!is_dir($rate_limit_dir)) {
    @mkdir($rate_limit_dir, 0700, true);
}
$ip_hash = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rate_file = $rate_limit_dir . $ip_hash . '.json';
$rate_data = file_exists($rate_file) ? json_decode(file_get_contents($rate_file), true) : [];
$now = time();
// Clean entries older than 60 seconds
$rate_data = array_filter($rate_data, fn($t) => ($now - $t) < 60);
if (count($rate_data) >= 10) {
    http_response_code(429);
    die('Too many requests. Please wait a minute before generating another PDF.');
}
$rate_data[] = $now;
file_put_contents($rate_file, json_encode($rate_data));

try {
    // ── SECURITY: Sanitize all text inputs ──
    $inputs = [
        'client_name' => mb_substr(strip_tags($_POST['clientName'] ?? 'N/A'), 0, 100),
        'advisor_name' => mb_substr(strip_tags($_POST['advisorName'] ?? 'N/A'), 0, 100),
        'custom_disclaimer' => mb_substr(strip_tags($_POST['customDisclaimer'] ?? ''), 0, 1000),
        'chart_base64' => '',
        'table_html' => '',
        'sip' => 0,
        'years' => 0,
        'rate' => 0,
        'stepup' => 0,
        'swp_withdrawal' => 0,
        'swp_stepup' => 0,
        'swp_years' => 0,
        'logo_base64' => null,
    ];

    // ── SECURITY: Validate chart data is a valid base64 data URI ──
    $chart_raw = $_POST['chartData'] ?? '';
    if ($chart_raw !== '' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[A-Za-z0-9+\/=]+$/', $chart_raw)) {
        $inputs['chart_base64'] = $chart_raw;
    }

    // ── SECURITY: Sanitize table HTML (strip dangerous tags/attributes) ──
    $table_raw = $_POST['tableHtml'] ?? '<table><tr><td>No data</td></tr></table>';
    // Whitelist only safe HTML tags for the table
    $inputs['table_html'] = strip_tags(
        $table_raw,
        '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col><span><strong><em><br>'
    );
    // Remove any remaining event handlers or style attributes that could execute code
    $inputs['table_html'] = preg_replace(
        '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i',
        '',
        $inputs['table_html']
    );
    $inputs['table_html'] = preg_replace(
        '/\s+style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i',
        '',
        $inputs['table_html']
    );

    // ── SECURITY: Validate numeric inputs (clamp to safe ranges) ──
    $inputs['sip'] = max(0, min(10000000, (float) ($_POST['sip'] ?? 0)));
    $inputs['years'] = max(0, min(50, (int) ($_POST['years'] ?? 0)));
    $inputs['rate'] = max(0, min(50, (float) ($_POST['rate'] ?? 0)));
    $inputs['stepup'] = max(0, min(100, (float) ($_POST['stepup'] ?? 0)));
    $inputs['swp_withdrawal'] = max(0, min(10000000, (float) ($_POST['swp_withdrawal'] ?? 0)));
    $inputs['swp_stepup'] = max(0, min(50, (float) ($_POST['swp_stepup'] ?? 0)));
    $inputs['swp_years'] = max(0, min(50, (int) ($_POST['swp_years'] ?? 0)));

    // ── SECURITY: Validate file upload (real image check, size limit) ──
    if (isset($_FILES['advisorLogo']) && $_FILES['advisorLogo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['advisorLogo']['tmp_name'];
        $file_size = $_FILES['advisorLogo']['size'];

        // Max 2MB
        if ($file_size > 2 * 1024 * 1024) {
            throw new \RuntimeException('Logo file too large. Maximum 2MB allowed.');
        }

        // Verify it's actually an image using getimagesize (cannot be spoofed like MIME types)
        $image_info = @getimagesize($tmp_name);
        if ($image_info === false) {
            throw new \RuntimeException('Uploaded file is not a valid image.');
        }

        // Only allow specific image types
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($image_info[2], $allowed_types, true)) {
            throw new \RuntimeException('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        // Max dimensions: 2000x2000px
        if ($image_info[0] > 2000 || $image_info[1] > 2000) {
            throw new \RuntimeException('Image dimensions too large. Maximum 2000x2000 pixels.');
        }

        // Safe to proceed — use detected MIME type (not the user-supplied one)
        $safe_mime = $image_info['mime'];
        $data = file_get_contents($tmp_name);
        $inputs['logo_base64'] = 'data:' . $safe_mime . ';base64,' . base64_encode($data);
    }

    // --- HTML Generation ---
    $html = get_pdf_html($inputs);

    // --- PDF Generation ---
    $options = new Options();
    // ── SECURITY: Disable remote requests to prevent SSRF ──
    // Data URIs (base64 images) work without remote enabled
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Helvetica');
    // Disable PHP evaluation in Dompdf (prevents code execution via PDF)
    $options->set('isPhpEnabled', false);
    // Disable JavaScript execution in PDF
    $options->set('isJavascriptEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // --- Output PDF ---
    $safe_client_name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $inputs['client_name']);
    $dompdf->stream(
        "Financial_Report_for_{$safe_client_name}.pdf",
        ["Attachment" => 1]
    );

} catch (\Exception $e) {
    http_response_code(500);
    error_log('PDF Generation Error: ' . $e->getMessage());
    die('An error occurred during PDF generation. Please try again.');
}