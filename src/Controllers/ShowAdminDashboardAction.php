<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminDashboardPresenter;
use Core\DashboardConfig;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightRepository;
use Core\ViewRenderer;
use Services\SessionManagerInterface;

/**
 * ShowAdminDashboardAction
 * Single Responsibility action dedicated strictly to formatting and displaying the admin insights dashboard.
 * Authentication is enforced upstream via AdminAuthMiddleware.
 */
class ShowAdminDashboardAction
{
    private InsightRepository $insightRepository;
    private AdminDashboardPresenter $presenter;
    private ViewRenderer $viewRenderer;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        InsightRepository $insightRepository,
        AdminDashboardPresenter $presenter,
        ViewRenderer $viewRenderer,
        SessionManagerInterface $sessionManager
    ) {
        $this->insightRepository = $insightRepository;
        $this->presenter = $presenter;
        $this->viewRenderer = $viewRenderer;
        $this->sessionManager = $sessionManager;
    }

    public function __invoke(Request $request): Response
    {
        $time_ranges = DashboardConfig::TIME_RANGES;

        $current_range_key = (string) $request->get('range', '24h');
        if (!isset($time_ranges[$current_range_key])) {
            $current_range_key = '1m';
        }
        $current_range = $time_ranges[$current_range_key];

        $stats = $this->insightRepository->getDashboardData($current_range);
        $viewModels = $this->presenter->formatForView($stats);

        $payload = array_merge([
            'is_authenticated'  => true,
            'current_range_key' => $current_range_key,
            'time_ranges'       => $time_ranges,
            'current_range'     => $current_range,
            'csrf_token'        => $this->sessionManager->ensureCsrfToken(),
        ], $viewModels);

        return Response::html($this->viewRenderer->render('admin/dashboard', $payload));
    }
}
