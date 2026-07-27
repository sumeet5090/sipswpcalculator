<?php

declare(strict_types=1);

namespace Controllers;

use Core\InsightRepository;
use Core\AnonymizedInsightLogger;
use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\DatabaseManager;
use Core\DatabaseMigrator;
use Core\View;

/**
 * AdminController
 * Handles administrator session authentication, dashboard rendering, and log collection.
 */
class AdminController
{
    private InsightRepository $insightRepository;
    private AnonymizedInsightLogger $insightLogger;
    private AdminAuthService $authService;
    private AdminDashboardPresenter $presenter;

    public function __construct(
        InsightRepository $insightRepository,
        AnonymizedInsightLogger $insightLogger,
        AdminAuthService $authService,
        AdminDashboardPresenter $presenter
    ) {
        $this->insightRepository = $insightRepository;
        $this->insightLogger = $insightLogger;
        $this->authService = $authService;
        $this->presenter = $presenter;
    }

    public function insights(): void
    {
        // 1. Handle Logout
        if (isset($_GET['logout'])) {
            $this->authService->logout();
            header('Location: /admin_insights');
            exit;
        }

        // 2. Handle Login Attempt
        $loginError = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
            if ($this->authService->login($_POST['password'])) {
                header('Location: /admin_insights');
                exit;
            } else {
                $loginError = 'Incorrect password. Access denied.';
            }
        }

        // 3. Authenticate Check
        if (!$this->authService->isAuthenticated()) {
            View::render('admin/login', [
                'error' => $loginError
            ]);
            return;
        }

        // 4. Time Range Filter Config
        $time_ranges = [
            '24h' => ['label' => '24 Hours',   'interval' => '-1 day',   'chart_days' => 1],
            '48h' => ['label' => '48 Hours',   'interval' => '-2 days',  'chart_days' => 2],
            '72h' => ['label' => '72 Hours',   'interval' => '-3 days',  'chart_days' => 3],
            '1w'  => ['label' => '1 Week',     'interval' => '-7 days',  'chart_days' => 7],
            '1m'  => ['label' => '1 Month',    'interval' => '-30 days', 'chart_days' => 30],
            '6m'  => ['label' => '6 Months',   'interval' => '-180 days','chart_days' => 180],
            '1y'  => ['label' => '1 Year',     'interval' => '-365 days','chart_days' => 365],
        ];

        $current_range_key = $_GET['range'] ?? '24h';
        if (!isset($time_ranges[$current_range_key])) {
            $current_range_key = '1m';
        }
        $current_range = $time_ranges[$current_range_key];

        // 5. Gather statistics from the Repository and format for View
        $stats = $this->insightRepository->getDashboardData($current_range['interval']);
        $viewModels = $this->presenter->formatForView($stats);

        // Merge view scope payload
        $payload = array_merge([
            'current_range_key' => $current_range_key,
            'time_ranges'       => $time_ranges,
            'current_range'     => $current_range,
        ], $viewModels);

        View::render('admin/dashboard', $payload);
    }

    public function logInsight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method Not Allowed');
        }

        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        if (!isset($data['calc_type'], $data['amount'], $data['duration'])) {
            http_response_code(400);
            die('Invalid payload');
        }

        $calcType      = $data['calc_type'];
        $amount        = (float) $data['amount'];
        $duration      = (int) $data['duration'];
        $stepUpPct     = (float) ($data['step_up_pct'] ?? 0.0);
        $currency      = $data['currency'] ?? 'INR';
        $pdfDownloaded = !empty($data['pdf_downloaded']);

        $interestRate     = isset($data['interest_rate'])     ? (float) $data['interest_rate']     : null;
        $sipAmount        = isset($data['sip_amount'])        ? (float) $data['sip_amount']        : null;
        $sipDuration      = isset($data['sip_duration'])      ? (int) $data['sip_duration']        : null;
        $sipStepUp        = isset($data['sip_step_up'])       ? (float) $data['sip_step_up']       : null;
        $swpEnabled       = !empty($data['swp_enabled'])      ? 1 : 0;
        $swpWithdrawal    = isset($data['swp_withdrawal'])    ? (float) $data['swp_withdrawal']    : null;
        $swpDuration      = isset($data['swp_duration'])      ? (int) $data['swp_duration']        : null;
        $swpStepUp        = isset($data['swp_step_up'])       ? (float) $data['swp_step_up']       : null;
        $finalCorpus      = isset($data['final_corpus'])      ? (float) $data['final_corpus']      : null;
        $totalInvested    = isset($data['total_invested'])    ? (float) $data['total_invested']    : null;
        $wealthMultiplier = isset($data['wealth_multiplier']) ? (float) $data['wealth_multiplier'] : null;
        $goalMode         = isset($data['goal_mode'])         ? (string) $data['goal_mode']        : null;
        $deviceType       = isset($data['device_type'])       ? (string) $data['device_type']      : null;
        $tableViewed      = !empty($data['table_viewed'])     ? 1 : 0;

        $this->insightLogger->logCalculation(
            $calcType,
            $amount,
            $duration,
            $stepUpPct,
            $currency,
            $pdfDownloaded,
            $interestRate,
            $sipAmount,
            $sipDuration,
            $sipStepUp,
            $swpEnabled,
            $swpWithdrawal,
            $swpDuration,
            $swpStepUp,
            $finalCorpus,
            $totalInvested,
            $wealthMultiplier,
            $goalMode,
            $deviceType,
            $tableViewed
        );

        http_response_code(204);
        exit;
    }

    /**
     * Explicitly run migrations (admin authentication required).
     */
    public function runMigrations(): void
    {
        if (!$this->authService->isAuthenticated()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $pdo = DatabaseManager::getConnection();
            $migrator = new DatabaseMigrator($pdo);
            $migrator->migrate(true); // Silent mode

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Database migrations completed successfully.']);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Migration failed: ' . $e->getMessage()]);
            exit;
        }
    }
}
