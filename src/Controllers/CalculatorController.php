<?php

declare(strict_types=1);

namespace Controllers;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\InvestmentInputs;
use Core\InvestmentCalculator;
use Core\View;
use Services\GuideRenderer;

/**
 * CalculatorController
 * Handles financial compounding simulations, guide renderings, and PDF generator dispatches.
 */
class CalculatorController
{
    private MetaManager $metaManager;
    private GuideRenderer $guideRenderer;

    public function __construct(
        MetaManager $metaManager,
        GuideRenderer $guideRenderer
    ) {
        $this->metaManager = $metaManager;
        $this->guideRenderer = $guideRenderer;
    }

    public function home(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // CSRF & Honeypot Checks
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['website_url'])) {
                http_response_code(403);
                die('Forbidden: Automated request detected.');
            }
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                die('Forbidden: Invalid security token. Please reload the page and try again.');
            }
        }

        // Instantiate Input DTO
        $inputs = InvestmentInputs::fromRequest($_POST);

        // Instantiate and run calculation engine
        $calculator = new InvestmentCalculator();
        $combined = $calculator->calculate($inputs);

        // Map variables for view scope
        $sip = $inputs->getSip();
        $years = $inputs->getYears();
        $rate = $inputs->getRate();
        $stepup = $inputs->getStepup();
        $lumpsum = $inputs->getLumpsum();
        $enable_swp = $inputs->isSwpEnabled();
        $swp_withdrawal = $inputs->getSwpWithdrawal();
        $swp_stepup = $inputs->getSwpStepup();
        $swp_years_input = $inputs->getSwpYears();
        $swp_rate = $inputs->getSwpRate();

        // Extract list parameters for chart canvas injection
        $years_data = array_column($combined, 'year');
        $cumulative_numbers = array_column($combined, 'cumulative_invested');
        $combined_numbers = array_column($combined, 'combined_total');
        $swp_numbers = array_map(function ($val) {
            return $val ?? 0.0;
        }, array_column($combined, 'annual_withdrawal'));

        // Handle CSV export action
        $action = $_POST['action'] ?? '';
        if ($action === 'download_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=SIP_SWP_Yearly_Report.csv');
            $output = fopen('php://output', 'w');

            $headers = ['Year', 'Begin Balance (₹)', 'Monthly SIP (₹)', 'Annual Contribution (₹)', 'Cumulative Invested (₹)'];
            if ($enable_swp) {
                $headers[] = 'Monthly SWP (₹)';
                $headers[] = 'Annual Withdrawal (₹)';
                $headers[] = 'Cumulative Withdrawals (₹)';
            }
            $headers[] = 'Interest Earned (₹)';
            $headers[] = 'End Balance (₹)';

            fputcsv($output, $headers);

            foreach ($combined as $row) {
                $csvRow = [
                    $row['year'],
                    $row['begin_balance'],
                    $row['sip_monthly'] !== null ? $row['sip_monthly'] : 0,
                    $row['annual_contribution'],
                    $row['cumulative_invested']
                ];
                if ($enable_swp) {
                    $csvRow[] = $row['swp_monthly'] !== null ? $row['swp_monthly'] : 0;
                    $csvRow[] = $row['annual_withdrawal'] !== null ? $row['annual_withdrawal'] : 0;
                    $csvRow[] = $row['cumulative_withdrawals'];
                }
                $csvRow[] = $row['interest'];
                $csvRow[] = $row['combined_total'];
                fputcsv($output, $csvRow);
            }
            fclose($output);
            exit();
        }

        $page_config = $this->metaManager->getMeta('home');

        View::render('calculators/home', [
            'active_page'         => 'index.php',
            'sip'                 => $sip,
            'years'               => $years,
            'rate'                => $rate,
            'stepup'              => $stepup,
            'lumpsum'             => $lumpsum,
            'enable_swp'          => $enable_swp,
            'swp_withdrawal'      => $swp_withdrawal,
            'swp_stepup'          => $swp_stepup,
            'swp_years_input'     => $swp_years_input,
            'swp_rate'            => $swp_rate,
            'years_data'          => $years_data,
            'cumulative_numbers'  => $cumulative_numbers,
            'combined_numbers'    => $combined_numbers,
            'swp_numbers'         => $swp_numbers,
            'combined'            => $combined,
            'page_config'         => $page_config,
        ]);
    }

    /**
     * Single dynamic action replacing 7 duplicate endpoints via Strategy Pattern.
     */
    public function renderGuide(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slug = ltrim($uri, '/');

        // Look up categories and config
        $routesConfig = require __DIR__ . '/../Core/Config/routes.php';
        $calcConfig = $routesConfig['calculators']['/' . $slug] ?? null;

        if (!$calcConfig) {
            http_response_code(404);
            echo "404 Calculator Route Not Found";
            return;
        }

        $this->guideRenderer->render($slug, $calcConfig['category'], $calcConfig['date']);
    }

    public function generatePdf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method Not Allowed');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Forbidden: Invalid security token. Please reload the page and try again.');
        }

        // Rate limiting checks
        $rate_limit_dir = sys_get_temp_dir() . '/sipswp_rate_limits/';
        if (!is_dir($rate_limit_dir)) {
            @mkdir($rate_limit_dir, 0700, true);
        }
        $ip_hash = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate_file = $rate_limit_dir . $ip_hash . '.json';
        $rate_data = file_exists($rate_file) ? json_decode(file_get_contents($rate_file), true) : [];
        $now = time();
        $rate_data = array_filter($rate_data, fn($t) => ($now - $t) < 60);
        if (count($rate_data) >= 10) {
            http_response_code(429);
            die('Too many requests. Please wait a minute before generating another PDF.');
        }
        $rate_data[] = $now;
        file_put_contents($rate_file, json_encode($rate_data));

        try {
            $inputs = [
                'client_name'       => mb_substr(strip_tags($_POST['clientName'] ?? 'N/A'), 0, 100),
                'advisor_name'      => mb_substr(strip_tags($_POST['advisorName'] ?? 'N/A'), 0, 100),
                'custom_disclaimer' => mb_substr(strip_tags($_POST['customDisclaimer'] ?? ''), 0, 1000),
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
                'currency_symbol'   => mb_substr(strip_tags($_POST['currency_symbol'] ?? ''), 0, 10),
                'summary_invested'  => mb_substr(strip_tags($_POST['summary_invested'] ?? '0'), 0, 50),
                'summary_interest'  => mb_substr(strip_tags($_POST['summary_interest'] ?? '0'), 0, 50),
                'summary_withdrawn' => mb_substr(strip_tags($_POST['summary_withdrawn'] ?? '0'), 0, 50),
                'summary_corpus'    => mb_substr(strip_tags($_POST['summary_corpus'] ?? '0'), 0, 50),
            ];

            $chart_raw = $_POST['chartData'] ?? '';
            if ($chart_raw !== '' && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,[A-Za-z0-9+\/=]+$/', $chart_raw)) {
                $inputs['chart_base64'] = $chart_raw;
            }

            $table_raw = $_POST['tableHtml'] ?? '<table><tr><td>No data</td></tr></table>';
            $inputs['table_html'] = strip_tags(
                $table_raw,
                '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col><span><strong><em><br>'
            );
            $inputs['table_html'] = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $inputs['table_html']);
            $inputs['table_html'] = preg_replace('/\s+style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i', '', $inputs['table_html']);

            $inputs['sip'] = max(0, min(10000000, (float) ($_POST['sip'] ?? 0)));
            $inputs['years'] = max(0, min(50, (int) ($_POST['years'] ?? 0)));
            $inputs['rate'] = max(0, min(50, (float) ($_POST['rate'] ?? 0)));
            $inputs['stepup'] = max(0, min(100, (float) ($_POST['stepup'] ?? 0)));
            $inputs['lumpsum'] = max(0, min(10000000, (float) ($_POST['lumpsum'] ?? 0)));
            $inputs['swp_withdrawal'] = max(0, min(10000000, (float) ($_POST['swp_withdrawal'] ?? 0)));
            $inputs['swp_stepup'] = max(0, min(50, (float) ($_POST['swp_stepup'] ?? 0)));
            $inputs['swp_years'] = max(0, min(50, (int) ($_POST['swp_years'] ?? 0)));
            $inputs['swp_rate'] = max(0.1, min(30, (float) ($_POST['swp_rate'] ?? 8)));

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

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

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
    }
}
