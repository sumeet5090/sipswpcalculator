<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightRepository;
use Core\ViewRenderer;

/**
 * ShowAdminDashboardAction
 * Single Responsibility action dedicated strictly to formatting and displaying the admin insights dashboard.
 */
class ShowAdminDashboardAction
{
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
    private AdminAuthService $authService;
    private AdminDashboardPresenter $presenter;
    private ViewRenderer $viewRenderer;

    public function __construct(
        InsightRepository $insightRepository,
        AdminAuthService $authService,
        AdminDashboardPresenter $presenter,
        ViewRenderer $viewRenderer
    ) {
        $this->insightRepository = $insightRepository;
        $this->authService = $authService;
        $this->presenter = $presenter;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->authService->isAuthenticated()) {
            return Response::html($this->viewRenderer->render('admin/login', [
                'error' => ''
            ]));
        }

        $time_ranges = self::TIME_RANGES;

        $current_range_key = (string) $request->get('range', '24h');
        if (!isset($time_ranges[$current_range_key])) {
            $current_range_key = '1m';
        }
        $current_range = $time_ranges[$current_range_key];

        $stats = $this->insightRepository->getDashboardData($current_range);
        $viewModels = $this->presenter->formatForView($stats);

        $payload = array_merge([
            'current_range_key' => $current_range_key,
            'time_ranges'       => $time_ranges,
            'current_range'     => $current_range,
        ], $viewModels);

        return Response::html($this->viewRenderer->render('admin/dashboard', $payload));
    }
}
