<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\RateLimiter;
use Services\SessionManager;

/**
 * AdminAuthAction
 * Single Responsibility action dedicated strictly to handling administrator login and logout lifecycle.
 */
class AdminAuthAction
{
    private AdminAuthService $authService;
    private ViewRenderer $viewRenderer;
    private SessionManager $sessionManager;
    private ?RateLimiter $rateLimiter;

    public function __construct(
        AdminAuthService $authService,
        ViewRenderer $viewRenderer,
        SessionManager $sessionManager,
        ?RateLimiter $rateLimiter = null
    ) {
        $this->authService = $authService;
        $this->viewRenderer = $viewRenderer;
        $this->sessionManager = $sessionManager;
        $this->rateLimiter = $rateLimiter;
    }

    public function login(Request $request): Response
    {
        $loginError = '';
        if ($request->isPost()) {
            $ip = $request->getClientIp();

            if ($this->rateLimiter !== null) {
                try {
                    $this->rateLimiter->checkLimit($ip, 'sipswp_admin_auth', 5, 300);
                } catch (RateLimitExceededException) {
                    return Response::html($this->viewRenderer->render('admin/login', [
                        'error' => 'Too many login attempts. Please wait 5 minutes before trying again.',
                        'csrf_token' => $this->sessionManager->ensureCsrfToken(),
                    ]), 429);
                }
            }

            $password = $request->post('password');
            if (is_string($password)) {
                try {
                    $this->authService->login($password);
                    return Response::redirect('/admin_insights');
                } catch (\Core\Exceptions\AuthenticationException) {
                    $loginError = 'Incorrect password. Access denied.';
                } catch (\Core\Exceptions\ConfigurationException $e) {
                    error_log('AdminAuth Configuration Error: ' . $e->getMessage());
                    $loginError = 'Admin authentication is currently unavailable due to server configuration.';
                }
            } else {
                $loginError = 'Incorrect password. Access denied.';
            }
        }

        return Response::html($this->viewRenderer->render('admin/login', [
            'error' => $loginError,
            'csrf_token' => $this->sessionManager->ensureCsrfToken(),
        ]));
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        return Response::redirect('/admin_insights');
    }
}
