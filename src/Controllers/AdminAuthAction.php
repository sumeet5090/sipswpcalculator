<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;

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
        ShowAdminLoginAction $showLoginAction,
        ProcessAdminLoginAction $processLoginAction,
        ProcessAdminLogoutAction $processLogoutAction
    ) {
        $this->showLoginAction = $showLoginAction;
        $this->processLoginAction = $processLoginAction;
        $this->processLogoutAction = $processLogoutAction;
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
