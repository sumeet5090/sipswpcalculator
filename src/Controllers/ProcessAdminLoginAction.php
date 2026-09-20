<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Exceptions\AuthenticationException;
use Core\Exceptions\ConfigurationException;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\SessionManagerInterface;

/**
 * ProcessAdminLoginAction
 * Single Responsibility action dedicated strictly to verifying administrator authentication attempts.
 * Rate limiting is enforced upstream via RateLimitMiddleware.
 */
class ProcessAdminLoginAction
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

    public function __invoke(Request $request): Response
    {
        $password = $request->post('password');
        $loginError = '';

        if (is_string($password)) {
            try {
                $this->authService->login($password);
                return Response::redirect('/admin_insights');
            } catch (AuthenticationException) {
                $loginError = 'Incorrect password. Access denied.';
            } catch (ConfigurationException $e) {
                error_log('AdminAuth Configuration Error: ' . $e->getMessage());
                $loginError = 'Admin authentication is currently unavailable due to server configuration.';
            }
        } else {
            $loginError = 'Incorrect password. Access denied.';
        }

        return Response::html($this->viewRenderer->render('admin/login', [
            'error' => $loginError,
            'csrf_token' => $this->sessionManager->ensureCsrfToken(),
        ]));
    }
}
