<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentInputs;
use Core\InvestmentCalculator;
use Core\MetaManager;
use Core\View;

class RenderHomeAction
{
    private MetaManager $metaManager;

    public function __construct(MetaManager $metaManager)
    {
        $this->metaManager = $metaManager;
    }

    public function __invoke(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // CSRF & Honeypot Checks for POST requests (if form was submitted via POST without ajax)
        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            if (!empty($post['website_url'])) {
                http_response_code(403);
                die('Forbidden: Automated request detected.');
            }
            $token = $post['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                die('Forbidden: Invalid security token. Please reload the page and try again.');
            }
        }

        // Instantiate Input DTO
        $inputs = InvestmentInputs::fromRequest($request->getParsedBody());

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
        $inflation = $inputs->getInflation();

        // Extract list parameters for chart canvas injection
        $years_data = array_column($combined, 'year');
        $cumulative_numbers = array_column($combined, 'cumulative_invested');
        $combined_numbers = array_column($combined, 'combined_total');
        $swp_numbers = array_map(function ($val) {
            return $val ?? 0.0;
        }, array_column($combined, 'annual_withdrawal'));

        // Handle CSV export action
        $post = $request->getParsedBody();
        $action = $post['action'] ?? '';
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
        $faqRepository = new \Core\FaqRepository();
        $homeFaqs = $faqRepository->getByTag('home');

        // Load central config and pass to view for dynamic field bounds/defaults.
        $calcConfig = require __DIR__ . '/../Core/Config/calculator_defaults.php';

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
            'inflation'           => $inflation,
            'years_data'          => $years_data,
            'cumulative_numbers'  => $cumulative_numbers,
            'combined_numbers'    => $combined_numbers,
            'swp_numbers'         => $swp_numbers,
            'combined'            => $combined,
            'page_config'         => $page_config,
            'homeFaqs'            => $homeFaqs,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => true,
        ]);
    }
}
