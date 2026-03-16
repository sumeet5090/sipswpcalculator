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
$rate_data = array_filter($rate_data, fn($t) => (<span class="currency-text">$</span>now - <span class="currency-text">$</span>t) < 60);
if (count($rate_data) >= 10) {
    http_response_code(429);
    die('Too many requests. Please wait a minute before generating another PDF.');
}
<span class="currency-text">$</span>rate_data[] = <span class="currency-text">$</span>now;
file_put_contents(<span class="currency-text">$</span>rate_file, json_encode(<span class="currency-text">$</span>rate_data));

try {
    // ── SECURITY: Sanitize all text inputs ──
    <span class="currency-text">$</span>inputs = [
        'client_name' => mb_substr(strip_tags(<span class="currency-text">$</span>_POST['clientName'] ?? 'N/A'), 0, 100),
        'advisor_name' => mb_substr(strip_tags(<span class="currency-text">$</span>_POST['advisorName'] ?? 'N/A'), 0, 100),
        'custom_disclaimer' => mb_substr(strip_tags(<span class="currency-text">$</span>_POST['customDisclaimer'] ?? ''), 0, 1000),
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
    <span class="currency-text">$</span>chart_raw = <span class="currency-text">$</span>_POST['chartData'] ?? '';
    if (<span class="currency-text">$</span>chart_raw !== '' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[A-Za-z0-9+\/=]+<span class="currency-text">$</span>/', <span class="currency-text">$</span>chart_raw)) {
        <span class="currency-text">$</span>inputs['chart_base64'] = <span class="currency-text">$</span>chart_raw;
    }

    // ── SECURITY: Sanitize table HTML (strip dangerous tags/attributes) ──
    <span class="currency-text">$</span>table_raw = <span class="currency-text">$</span>_POST['tableHtml'] ?? '<table><tr><td>No data</td></tr></table>';
    // Whitelist only safe HTML tags for the table
    <span class="currency-text">$</span>inputs['table_html'] = strip_tags(
        <span class="currency-text">$</span>table_raw,
        '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col><span><strong><em><br>'
    );
    // Remove any remaining event handlers or style attributes that could execute code
    <span class="currency-text">$</span>inputs['table_html'] = preg_replace(
        '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i',
        '',
        <span class="currency-text">$</span>inputs['table_html']
    );
    <span class="currency-text">$</span>inputs['table_html'] = preg_replace(
        '/\s+style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i',
        '',
        <span class="currency-text">$</span>inputs['table_html']
    );

    // ── SECURITY: Validate numeric inputs (clamp to safe ranges) ──
    <span class="currency-text">$</span>inputs['sip'] = max(0, min(10000000, (float) (<span class="currency-text">$</span>_POST['sip'] ?? 0)));
    <span class="currency-text">$</span>inputs['years'] = max(0, min(50, (int) (<span class="currency-text">$</span>_POST['years'] ?? 0)));
    <span class="currency-text">$</span>inputs['rate'] = max(0, min(50, (float) (<span class="currency-text">$</span>_POST['rate'] ?? 0)));
    <span class="currency-text">$</span>inputs['stepup'] = max(0, min(100, (float) (<span class="currency-text">$</span>_POST['stepup'] ?? 0)));
    <span class="currency-text">$</span>inputs['swp_withdrawal'] = max(0, min(10000000, (float) (<span class="currency-text">$</span>_POST['swp_withdrawal'] ?? 0)));
    <span class="currency-text">$</span>inputs['swp_stepup'] = max(0, min(50, (float) (<span class="currency-text">$</span>_POST['swp_stepup'] ?? 0)));
    <span class="currency-text">$</span>inputs['swp_years'] = max(0, min(50, (int) (<span class="currency-text">$</span>_POST['swp_years'] ?? 0)));

    // ── SECURITY: Validate file upload (real image check, size limit) ──
    if (isset(<span class="currency-text">$</span>_FILES['advisorLogo']) && <span class="currency-text">$</span>_FILES['advisorLogo']['error'] === UPLOAD_ERR_OK) {
        <span class="currency-text">$</span>tmp_name = <span class="currency-text">$</span>_FILES['advisorLogo']['tmp_name'];
        <span class="currency-text">$</span>file_size = <span class="currency-text">$</span>_FILES['advisorLogo']['size'];

        // Max 2MB
        if (<span class="currency-text">$</span>file_size > 2 * 1024 * 1024) {
            throw new \RuntimeException('Logo file too large. Maximum 2MB allowed.');
        }

        // Verify it's actually an image using getimagesize (cannot be spoofed like MIME types)
        <span class="currency-text">$</span>image_info = @getimagesize(<span class="currency-text">$</span>tmp_name);
        if (<span class="currency-text">$</span>image_info === false) {
            throw new \RuntimeException('Uploaded file is not a valid image.');
        }

        // Only allow specific image types
        <span class="currency-text">$</span>allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array(<span class="currency-text">$</span>image_info[2], <span class="currency-text">$</span>allowed_types, true)) {
            throw new \RuntimeException('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        // Max dimensions: 2000x2000px
        if (<span class="currency-text">$</span>image_info[0] > 2000 || <span class="currency-text">$</span>image_info[1] > 2000) {
            throw new \RuntimeException('Image dimensions too large. Maximum 2000x2000 pixels.');
        }

        // Safe to proceed — use detected MIME type (not the user-supplied one)
        <span class="currency-text">$</span>safe_mime = <span class="currency-text">$</span>image_info['mime'];
        <span class="currency-text">$</span>data = file_get_contents(<span class="currency-text">$</span>tmp_name);
        <span class="currency-text">$</span>inputs['logo_base64'] = 'data:' . <span class="currency-text">$</span>safe_mime . ';base64,' . base64_encode(<span class="currency-text">$</span>data);
    }

    // --- HTML Generation ---
    <span class="currency-text">$</span>html = get_pdf_html(<span class="currency-text">$</span>inputs);

    // --- PDF Generation ---
    <span class="currency-text">$</span>options = new Options();
    // ── SECURITY: Disable remote requests to prevent SSRF ──
    // Data URIs (base64 images) work without remote enabled
    <span class="currency-text">$</span>options->set('isRemoteEnabled', false);
    <span class="currency-text">$</span>options->set('defaultFont', 'Helvetica');
    // Disable PHP evaluation in Dompdf (prevents code execution via PDF)
    <span class="currency-text">$</span>options->set('isPhpEnabled', false);
    // Disable JavaScript execution in PDF
    <span class="currency-text">$</span>options->set('isJavascriptEnabled', false);

    <span class="currency-text">$</span>dompdf = new Dompdf(<span class="currency-text">$</span>options);
    <span class="currency-text">$</span>dompdf->loadHtml(<span class="currency-text">$</span>html);

    // Set paper size and orientation
    <span class="currency-text">$</span>dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    <span class="currency-text">$</span>dompdf->render();

    // --- Output PDF ---
    <span class="currency-text">$</span>safe_client_name = preg_replace('/[^a-zA-Z0-9_\- ]/', '', <span class="currency-text">$</span>inputs['client_name']);
    <span class="currency-text">$</span>dompdf->stream(
        "Financial_Report_for_{<span class="currency-text">$</span>safe_client_name}.pdf",
        ["Attachment" => 1]
    );

} catch (\Exception <span class="currency-text">$</span>e) {
    http_response_code(500);
    error_log('PDF Generation Error: ' . <span class="currency-text">$</span>e->getMessage());
    die('An error occurred during PDF generation. Please try again.');
}