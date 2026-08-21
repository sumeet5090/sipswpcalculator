<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Http\Response;

/**
 * ProcessAdminLogoutAction
 * Single Responsibility action dedicated strictly to terminating the administrator session.
 */
class ProcessAdminLogoutAction
{
    private AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(): Response
    {
        $this->authService->logout();
        return Response::redirect('/admin_insights');
    }
}
