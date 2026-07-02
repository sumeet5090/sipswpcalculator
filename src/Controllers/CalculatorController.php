<?php

namespace Controllers;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;

class CalculatorController
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;

    public function __construct()
    {
        $this->contentManager = new ContentManager();
        $this->metaManager = new MetaManager();
        $this->schemaHelper = new SchemaHelper();
    }

    public function home()
    {
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

        // Instantiate DTO
        $inputs = \Core\InvestmentInputs::fromRequest($_POST);

        // Instantiate Engine
        $calculator = new \Core\InvestmentCalculator();
        $combined = $calculator->calculate($inputs);

        // Map variables for backward compatibility with modular view components
        $sip = $inputs->getSip();
        $years = $inputs->getYears();
        $rate = $inputs->getRate();
        $stepup = $inputs->getStepup();
        $enable_swp = $inputs->isSwpEnabled();
        $swp_withdrawal = $inputs->getSwpWithdrawal();
        $swp_stepup = $inputs->getSwpStepup();
        $swp_years_input = $inputs->getSwpYears();

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

        require_once __DIR__ . '/../Views/calculators/home.php';
    }

    public function compoundInterestCalculator()
    {
        $content = $this->contentManager->getParsedContent('/calculators/compound-interest-calculator');

        if (!$content) {
            http_response_code(404);
            echo "404 Compound Interest Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('compound-interest-calculator');

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Compound Interest Calculator' => '/compound-interest-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-27',
            '2026-02-27'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/compound-interest-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/compound-interest-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $page_config['scripts'] = ['/assets/js/calculators/compound-interest.js'];

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'compound-interest-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function dollarCostAveragingTool()
    {
        $content = $this->contentManager->getParsedContent('/calculators/dollar-cost-averaging-tool');

        if (!$content) {
            http_response_code(404);
            echo "404 DCA Tool Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('dollar-cost-averaging-tool');

        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Dollar-Cost Averaging Tool' => '/dollar-cost-averaging-tool'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/dollar-cost-averaging-tool">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/dollar-cost-averaging-tool">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'dollar-cost-averaging-tool.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function recurringInvestmentCalculator()
    {
        $content = $this->contentManager->getParsedContent('/calculators/recurring-investment-calculator');

        if (!$content) {
            http_response_code(404);
            echo "404 Recurring Investment Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('recurring-investment-calculator');

        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Recurring Investment Calculator' => '/recurring-investment-calculator'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/recurring-investment-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/recurring-investment-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'recurring-investment-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function retirementDrawdownPlanner()
    {
        $content = $this->contentManager->getParsedContent('/calculators/retirement-drawdown-planner');

        if (!$content) {
            http_response_code(404);
            echo "404 Drawdown Planner Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('retirement-drawdown-planner');

        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Retirement Drawdown Planner' => '/retirement-drawdown-planner'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/retirement-drawdown-planner">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/retirement-drawdown-planner">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'retirement-drawdown-planner.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function sipCalculator()
    {
        $content = $this->contentManager->getParsedContent('/calculators/sip-calculator');

        if (!$content) {
            http_response_code(404);
            echo "404 SIP Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('sip-calculator');

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'SIP Calculator' => '/sip-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/sip-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/sip-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'sip-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function sipStepUpCalculator()
    {
        $content = $this->contentManager->getParsedContent('/calculators/sip-step-up-calculator');

        if (!$content) {
            http_response_code(404);
            echo "404 Step-Up Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('sip-step-up-calculator');

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Step-Up SIP Guide' => '/sip-step-up-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/sip-step-up-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/sip-step-up-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'sip-step-up-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function swpTaxCalculator()
    {
        $content = $this->contentManager->getParsedContent('/calculators/swp-tax-calculator');

        if (!$content) {
            http_response_code(404);
            echo "404 SWP Tax Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('swp-tax-calculator');

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'SWP Tax Calculator' => '/swp-tax-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/swp-tax-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/swp-tax-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'swp-tax-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function generatePdf()
    {
        // ── SECURITY: Only allow POST requests ──
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method Not Allowed');
        }

        // ── SECURITY: CSRF token validation ──
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
        $rate_data = array_filter($rate_data, fn($t) => ($now - $t) < 60);
        if (count($rate_data) >= 10) {
            http_response_code(429);
            die('Too many requests. Please wait a minute before generating another PDF.');
        }
        $rate_data[] = $now;
        file_put_contents($rate_file, json_encode($rate_data));

        try {
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

                // Summary Metrics
                'currency_symbol' => mb_substr(strip_tags($_POST['currency_symbol'] ?? ''), 0, 10),
                'summary_invested' => mb_substr(strip_tags($_POST['summary_invested'] ?? '0'), 0, 50),
                'summary_interest' => mb_substr(strip_tags($_POST['summary_interest'] ?? '0'), 0, 50),
                'summary_withdrawn' => mb_substr(strip_tags($_POST['summary_withdrawn'] ?? '0'), 0, 50),
                'summary_corpus' => mb_substr(strip_tags($_POST['summary_corpus'] ?? '0'), 0, 50),
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
            $inputs['swp_withdrawal'] = max(0, min(10000000, (float) ($_POST['swp_withdrawal'] ?? 0)));
            $inputs['swp_stepup'] = max(0, min(50, (float) ($_POST['swp_stepup'] ?? 0)));
            $inputs['swp_years'] = max(0, min(50, (int) ($_POST['swp_years'] ?? 0)));

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

            // Generate HTML using the OOP template service
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
