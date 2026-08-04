<?php

declare(strict_types=1);

namespace Controllers;

use Core\InsightRepository;
use Core\AnonymizedInsightLogger;
use Core\InsightPayload;
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
            '24h' => ['label' => '24 Hours',   'interval' => '-1 day',   'unit' => 'hour', 'cte_start' => '-23 hours'],
            '48h' => ['label' => '48 Hours',   'interval' => '-2 days',  'unit' => 'hour', 'cte_start' => '-47 hours'],
            '72h' => ['label' => '72 Hours',   'interval' => '-3 days',  'unit' => 'hour', 'cte_start' => '-71 hours'],
            '1w'  => ['label' => '1 Week',     'interval' => '-7 days',  'unit' => 'day',  'cte_start' => '-6 days'],
            '1m'  => ['label' => '1 Month',    'interval' => '-30 days', 'unit' => 'day',  'cte_start' => '-29 days'],
            '6m'  => ['label' => '6 Months',   'interval' => '-180 days','unit' => 'day',  'cte_start' => '-179 days'],
            '1y'  => ['label' => '1 Year',     'interval' => '-365 days','unit' => 'day',  'cte_start' => '-364 days'],
        ];

        $current_range_key = $_GET['range'] ?? '24h';
        if (!isset($time_ranges[$current_range_key])) {
            $current_range_key = '1m';
        }
        $current_range = $time_ranges[$current_range_key];

        // 5. Gather statistics from the Repository and format for View
        $stats = $this->insightRepository->getDashboardData($current_range);
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

        // Rate limiting check (max 30 requests per minute per IP)
        $rate_limit_dir = sys_get_temp_dir() . '/sipswp_log_limits/';
        if (!is_dir($rate_limit_dir)) {
            @mkdir($rate_limit_dir, 0700, true);
        }
        $ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate_file = $rate_limit_dir . $ip_hash . '.json';
        $fp = @fopen($rate_file, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $rate_data = !empty($content) ? json_decode($content, true) : [];
            if (!is_array($rate_data)) {
                $rate_data = [];
            }
            $now = time();
            $rate_data = array_filter($rate_data, fn($t) => ($now - $t) < 60);
            if (count($rate_data) >= 30) {
                flock($fp, LOCK_UN);
                fclose($fp);
                http_response_code(429);
                die('Rate limit exceeded');
            }
            $rate_data[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode(array_values($rate_data)));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        $inputJSON = file_get_contents('php://input');
        if (strlen($inputJSON) > 65536) { // 64KB limit
            http_response_code(413);
            die('Payload Too Large');
        }

        $data = json_decode($inputJSON, true);

        if (!is_array($data) || !isset($data['calc_type'], $data['amount'], $data['duration'])) {
            http_response_code(400);
            die('Invalid payload');
        }

        $payload = InsightPayload::fromArray($data);
        $this->insightLogger->logCalculation($payload);

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
