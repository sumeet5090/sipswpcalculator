<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Exceptions\AuthenticationException;
use Core\Exceptions\ConfigurationException;
use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\ConfigServiceInterface;
use Services\RateLimiter;
use Services\SessionManagerInterface;

/**
 * ProcessAdminLoginAction
 * Single Responsibility action dedicated strictly to rate limiting and verifying administrator authentication attempts.
 */
class ProcessAdminLoginAction
{
    private AdminAuthService $authService;
    private ViewRenderer $viewRenderer;
    private SessionManagerInterface $sessionManager;
    private RateLimiter $rateLimiter;
    private ConfigServiceInterface $configService;

    public function __construct(
        AdminAuthService $authService,
        ViewRenderer $viewRenderer,
        SessionManagerInterface $sessionManager,
        RateLimiter $rateLimiter,
        ConfigServiceInterface $configService
    ) {
        $this->authService = $authService;
        $this->viewRenderer = $viewRenderer;
        $this->sessionManager = $sessionManager;
        $this->rateLimiter = $rateLimiter;
        $this->configService = $configService;
    }

    public function __invoke(Request $request): Response
    {
        $ip = $request->getClientIp();

        $rateLimits = $this->configService->getJsonConfig('content/rate_limits.json');
        $maxRequests = (int) ($rateLimits['admin_auth']['max_requests'] ?? 5);
        $windowSeconds = (int) ($rateLimits['admin_auth']['window_seconds'] ?? 300);

        try {
            $this->rateLimiter->checkLimit($ip, 'sipswp_admin_auth', $maxRequests, $windowSeconds);
        } catch (RateLimitExceededException) {
            return Response::html($this->viewRenderer->render('admin/login', [
                'error' => 'Too many login attempts. Please wait 5 minutes before trying again.',
                'csrf_token' => $this->sessionManager->ensureCsrfToken(),
            ]), 429);
        }

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
