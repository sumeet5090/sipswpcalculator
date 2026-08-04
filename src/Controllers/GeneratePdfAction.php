<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

class GeneratePdfAction
{
    public function __invoke(Request $request): void
    {
        if ($request->getMethod() !== 'POST') {
            http_response_code(405);
            die('Method Not Allowed');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $post = $request->getParsedBody();
        $token = $post['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Forbidden: Invalid security token. Please reload the page and try again.');
        }

        // Rate limiting checks
        $rate_limit_dir = sys_get_temp_dir() . '/sipswp_rate_limits/';
        if (!is_dir($rate_limit_dir)) {
            @mkdir($rate_limit_dir, 0700, true);
        }
        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate_file = $rate_limit_dir . $ip_hash . '.json';
        $fp = @fopen($rate_file, 'c+');
        $rate_data = [];
        if ($fp && flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $rate_data = !empty($content) ? json_decode($content, true) : [];
            if (!is_array($rate_data)) {
                $rate_data = [];
            }
            $now = time();
            $rate_data = array_filter($rate_data, fn($t) => ($now - $t) < 60);
            if (count($rate_data) >= 10) {
                flock($fp, LOCK_UN);
                fclose($fp);
                http_response_code(429);
                die('Too many requests. Please wait a minute before generating another PDF.');
            }
            $rate_data[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode(array_values($rate_data)));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // Turn off error display during PDF generation so vendor deprecation warnings never corrupt binary stream
        $orig_display_errors = ini_get('display_errors');
        @ini_set('display_errors', '0');

        try {
            $inputs = [
                'client_name'       => mb_substr(strip_tags($post['clientName'] ?? 'N/A'), 0, 100),
                'advisor_name'      => mb_substr(strip_tags($post['advisorName'] ?? 'N/A'), 0, 100),
                'custom_disclaimer' => mb_substr(strip_tags($post['customDisclaimer'] ?? ''), 0, 1000),
                'chart_base64'      => '',
                'table_html'        => '',
                'sip'               => 0,
                'years'             => 0,
                'rate'              => 0,
                'stepup'            => 0,
                'lumpsum'           => 0,
                'swp_withdrawal'    => 0,
                'swp_stepup'        => 0,
                'swp_years'         => 0,
                'swp_rate'          => 8,
                'logo_base64'       => null,

                // Summary Metrics
                'currency_symbol'   => mb_substr(strip_tags($post['currency_symbol'] ?? ''), 0, 10),
                'summary_invested'  => mb_substr(strip_tags($post['summary_invested'] ?? '0'), 0, 50),
                'summary_interest'  => mb_substr(strip_tags($post['summary_interest'] ?? '0'), 0, 50),
                'summary_withdrawn' => mb_substr(strip_tags($post['summary_withdrawn'] ?? '0'), 0, 50),
                'summary_corpus'    => mb_substr(strip_tags($post['summary_corpus'] ?? '0'), 0, 50),
                'raw_invested'      => (float) ($post['raw_invested'] ?? 0),
                'raw_corpus'        => (float) ($post['raw_corpus'] ?? 0),
            ];

            $chart_raw = trim($post['chartData'] ?? '');
            if ($chart_raw !== '' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/i', $chart_raw)) {
                $inputs['chart_base64'] = $chart_raw;
            }

            $table_raw = $post['tableHtml'] ?? '<table><tr><td>No data</td></tr></table>';
            $inputs['table_html'] = strip_tags(
                $table_raw,
                '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col><span><strong><em><br>'
            );
            $inputs['table_html'] = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $inputs['table_html']);
            $inputs['table_html'] = preg_replace('/\s+style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i', '', $inputs['table_html']);

            $inputs['sip'] = max(0, min(10000000, (float) ($post['sip'] ?? 0)));
            $inputs['years'] = max(0, min(50, (int) ($post['years'] ?? 0)));
            $inputs['rate'] = max(0, min(50, (float) ($post['rate'] ?? 0)));
            $inputs['stepup'] = max(0, min(100, (float) ($post['stepup'] ?? 0)));
            $inputs['lumpsum'] = max(0, min(10000000, (float) ($post['lumpsum'] ?? 0)));
            $inputs['swp_withdrawal'] = max(0, min(10000000, (float) ($post['swp_withdrawal'] ?? 0)));
            $inputs['swp_stepup'] = max(0, min(50, (float) ($post['swp_stepup'] ?? 0)));
            $inputs['swp_years'] = max(0, min(50, (int) ($post['swp_years'] ?? 0)));
            $inputs['swp_rate'] = max(0.1, min(30, (float) ($post['swp_rate'] ?? 8)));

            if (isset($_FILES['advisorLogo']) && $_FILES['advisorLogo']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['advisorLogo']['tmp_name'];
                $file_size = $_FILES['advisorLogo']['size'];

                if ($file_size > 2 * 1024 * 1024) {
                    throw new \RuntimeException('Logo file too large. Maximum 2MB allowed.');
                }

                $image_info = @getimagesize($tmp_name);
                if ($image_info === false) {
                    throw new \RuntimeException('Uploaded file is not a valid image.');
                }

                $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
                if (!in_array($image_info[2], $allowed_types, true)) {
                    throw new \RuntimeException('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
                }

                if ($image_info[0] > 2000 || $image_info[1] > 2000) {
                    throw new \RuntimeException('Image dimensions too large. Maximum 2000x2000 pixels.');
                }

                $safe_mime = $image_info['mime'];
                $data = file_get_contents($tmp_name);
                $inputs['logo_base64'] = 'data:' . $safe_mime . ';base64,' . base64_encode($data);
            }

            // Generate HTML using PDF template service
            $html = \Core\PdfReportTemplate::render($inputs);

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'Helvetica');
            $options->set('isPhpEnabled', false);
            $options->set('isJavascriptEnabled', false);

            ob_start();
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf_binary = $dompdf->output();

            $raw_name = trim($inputs['client_name']);
            $clean_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $raw_name) ?: 'Client';
            $clean_name = preg_replace('/_+/', '_', $clean_name);
            $filename = "Financial_Report_for_{$clean_name}.pdf";

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf_binary));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdf_binary;
            @ini_set('display_errors', (string) $orig_display_errors);
            exit();
        } catch (\Exception $e) {
            @ini_set('display_errors', (string) $orig_display_errors);
            http_response_code(500);
            error_log('PDF Generation Error: ' . $e->getMessage());
            die('An error occurred during PDF generation. Please try again.');
        }
    }
}
