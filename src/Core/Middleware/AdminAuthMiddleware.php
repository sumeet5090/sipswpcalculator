<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\AdminAuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\SessionManagerInterface;

/**
 * AdminAuthMiddleware
 * Single-responsibility middleware to guard admin routes against unauthenticated access.
 */
class AdminAuthMiddleware implements MiddlewareInterface
{
    private AdminAuthService $authService;
    private ViewRenderer $viewRenderer;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        AdminAuthService $authService,
        ViewRenderer $viewRenderer,
        SessionManagerInterface $sessionManager
    ) {
        $this->authService = $authService;
        $this->viewRenderer = $viewRenderer;
        $this->sessionManager = $sessionManager;
    }

    public function process(Request $request, callable $next): Response
    {
        if (!$this->authService->isAuthenticated()) {
            return Response::html($this->viewRenderer->render('admin/login', [
                'error' => '',
                'csrf_token' => $this->sessionManager->ensureCsrfToken(),
            ]));
        }

        return $next($request);
    }
}
