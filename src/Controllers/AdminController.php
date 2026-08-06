<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\AnonymizedInsightLogger;
use Core\DatabaseMigrator;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightPayload;
use Core\InsightRepository;
use Core\ViewRenderer;
use Services\RateLimiter;

/**
 * AdminController
 * Handles administrator session authentication, dashboard rendering, and log collection.
 */
class AdminController
{
    public const MAX_PAYLOAD_SIZE_BYTES = 65536;

    public const TIME_RANGES = [
        '24h' => ['label' => '24 Hours',   'interval' => '-1 day',   'unit' => 'hour', 'cte_start' => '-23 hours'],
        '48h' => ['label' => '48 Hours',   'interval' => '-2 days',  'unit' => 'hour', 'cte_start' => '-47 hours'],
        '72h' => ['label' => '72 Hours',   'interval' => '-3 days',  'unit' => 'hour', 'cte_start' => '-71 hours'],
        '1w'  => ['label' => '1 Week',     'interval' => '-7 days',  'unit' => 'day',  'cte_start' => '-6 days'],
        '1m'  => ['label' => '1 Month',    'interval' => '-30 days', 'unit' => 'day',  'cte_start' => '-29 days'],
        '6m'  => ['label' => '6 Months',   'interval' => '-180 days','unit' => 'day',  'cte_start' => '-179 days'],
        '1y'  => ['label' => '1 Year',     'interval' => '-365 days','unit' => 'day',  'cte_start' => '-364 days'],
    ];

    private InsightRepository $insightRepository;
    private AnonymizedInsightLogger $insightLogger;
    private AdminAuthService $authService;
    private AdminDashboardPresenter $presenter;
    private RateLimiter $rateLimiter;
    private DatabaseMigrator $migrator;
    private ViewRenderer $viewRenderer;

    public function __construct(
        InsightRepository $insightRepository,
        AnonymizedInsightLogger $insightLogger,
        AdminAuthService $authService,
        AdminDashboardPresenter $presenter,
        DatabaseMigrator $migrator,
        RateLimiter $rateLimiter,
        ViewRenderer $viewRenderer
    ) {
        $this->insightRepository = $insightRepository;
        $this->insightLogger = $insightLogger;
        $this->authService = $authService;
        $this->presenter = $presenter;
        $this->migrator = $migrator;
        $this->rateLimiter = $rateLimiter;
        $this->viewRenderer = $viewRenderer;
    }

    public function insights(Request $request): Response
    {
        // 1. Handle Logout
        if ($request->get('logout') !== null) {
            $this->authService->logout();
            return Response::redirect('/admin_insights');
        }

        // 2. Handle Login Attempt
        $loginError = '';
        if ($request->isPost()) {
            $password = $request->post('password');
            if (is_string($password)) {
                try {
                    $this->authService->login($password);
                    return Response::redirect('/admin_insights');
                } catch (\Core\Exceptions\AuthenticationException $e) {
                    $loginError = 'Incorrect password. Access denied.';
                }
            } else {
                $loginError = 'Incorrect password. Access denied.';
            }
        }

        // 3. Authenticate Check
        if (!$this->authService->isAuthenticated()) {
            return Response::html($this->viewRenderer->render('admin/login', [
                'error' => $loginError
            ]));
        }

        // 4. Time Range Filter Config
        $time_ranges = self::TIME_RANGES;

        $current_range_key = (string) $request->get('range', '24h');
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

        return Response::html($this->viewRenderer->render('admin/dashboard', $payload));
    }

    public function logInsight(Request $request): Response
    {
        if (!$request->isPost()) {
            return new Response('Method Not Allowed', 405);
        }

        // Rate limiting check (max 30 requests per minute per IP)
        try {
            $ip = (string) $request->server('REMOTE_ADDR', 'unknown');
            $this->rateLimiter->checkLimit($ip, 'sipswp_log_limits', 30, 60);
        } catch (\Core\Exceptions\RateLimitExceededException $e) {
            return new Response('Rate limit exceeded', 429);
        }

        $rawBody = $request->getRawBody();
        if (strlen($rawBody) > self::MAX_PAYLOAD_SIZE_BYTES) { // 64KB limit
            return new Response('Payload Too Large', 413);
        }

        $data = $request->getJsonBody();

        if ($data === null || !isset($data['calc_type'], $data['amount'], $data['duration'])) {
            return new Response('Invalid payload', 400);
        }

        $payload = InsightPayload::fromArray($data);
        $this->insightLogger->logCalculation($payload, $request);

        return new Response('', 204);
    }

    /**
     * Explicitly run migrations (admin authentication required).
     */
    public function runMigrations(): Response
    {
        if (!$this->authService->isAuthenticated()) {
            return Response::json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        try {
            $executed = $this->migrator->migrate();
            $msg = count($executed) > 0
                ? 'Migrated successfully: ' . implode(', ', $executed)
                : 'Nothing to migrate.';

            return Response::json(['status' => 'success', 'message' => $msg]);
        } catch (\Throwable $e) {
            return Response::json(['status' => 'error', 'message' => 'Migration failed: ' . $e->getMessage()], 500);
        }
    }
}
