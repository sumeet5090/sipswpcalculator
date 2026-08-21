<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\RateLimiter;
use Services\SessionManager;

/**
 * AdminAuthAction
 * Backward-compatible composition controller delegating to single-responsibility actions.
 */
class AdminAuthAction
{
    private ShowAdminLoginAction $showLoginAction;
    private ProcessAdminLoginAction $processLoginAction;
    private ProcessAdminLogoutAction $processLogoutAction;

    public function __construct(
        AdminAuthService $authService,
        ViewRenderer $viewRenderer,
        SessionManager $sessionManager,
        RateLimiter $rateLimiter,
        ?ConfigService $configService = null
    ) {
        $cfg = $configService ?? new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $this->showLoginAction = new ShowAdminLoginAction($viewRenderer, $sessionManager);
        $this->processLoginAction = new ProcessAdminLoginAction($authService, $viewRenderer, $sessionManager, $rateLimiter, $cfg);
        $this->processLogoutAction = new ProcessAdminLogoutAction($authService);
    }

    public function login(Request $request): Response
    {
        if ($request->isPost()) {
            return ($this->processLoginAction)($request);
        }
        return ($this->showLoginAction)($request);
    }

    public function logout(?Request $request = null): Response
    {
        return ($this->processLogoutAction)();
    }
}
