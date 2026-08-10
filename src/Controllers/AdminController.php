<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\AdminDashboardPresenter;
use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightRepository;
use Core\ViewRenderer;
use Services\RateLimiter;

/**
 * AdminController
 * Unified Controller delegating requests to single-responsibility action classes (SRP).
 */
class AdminController
{
    public const MAX_PAYLOAD_SIZE_BYTES = LogInsightApiAction::MAX_PAYLOAD_SIZE_BYTES;
    public const TIME_RANGES = ShowAdminDashboardAction::TIME_RANGES;

    private ShowAdminDashboardAction $dashboardAction;
    private AdminAuthAction $authAction;
    private LogInsightApiAction $logAction;

    public function __construct(
        InsightRepository $insightRepository,
        AnonymizedInsightLogger $insightLogger,
        AdminAuthService $authService,
        AdminDashboardPresenter $presenter,
        RateLimiter $rateLimiter,
        ViewRenderer $viewRenderer
    ) {
        $this->dashboardAction = new ShowAdminDashboardAction($insightRepository, $authService, $presenter, $viewRenderer);
        $this->authAction = new AdminAuthAction($authService, $viewRenderer);
        $this->logAction = new LogInsightApiAction($insightLogger, $rateLimiter);
    }

    public function insights(Request $request): Response
    {
        return ($this->dashboardAction)($request);
    }

    public function login(Request $request): Response
    {
        return $this->authAction->login($request);
    }

    public function logout(Request $request): Response
    {
        return $this->authAction->logout($request);
    }

    public function logInsight(Request $request): Response
    {
        return ($this->logAction)($request);
    }
}
