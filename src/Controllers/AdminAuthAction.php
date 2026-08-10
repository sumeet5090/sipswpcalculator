<?php

declare(strict_types=1);

namespace Controllers;

use Core\AdminAuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;

/**
 * AdminAuthAction
 * Single Responsibility action dedicated strictly to handling administrator login and logout lifecycle.
 */
class AdminAuthAction
{
    private AdminAuthService $authService;
    private ViewRenderer $viewRenderer;

    public function __construct(
        AdminAuthService $authService,
        ViewRenderer $viewRenderer
    ) {
        $this->authService = $authService;
        $this->viewRenderer = $viewRenderer;
    }

    public function login(Request $request): Response
    {
        $loginError = '';
        if ($request->isPost()) {
            $password = $request->post('password');
            if (is_string($password)) {
                try {
                    $this->authService->login($password);
                    return Response::redirect('/admin_insights');
                } catch (\Core\Exceptions\AuthenticationException $e) {
                    $loginError = 'Incorrect password. Access denied.';
                } catch (\Core\Exceptions\ConfigurationException $e) {
                    $loginError = 'Configuration Error: ' . $e->getMessage();
                }
            } else {
                $loginError = 'Incorrect password. Access denied.';
            }
        }

        return Response::html($this->viewRenderer->render('admin/login', [
            'error' => $loginError
        ]));
    }

    public function logout(Request $request): Response
    {
        $this->authService->logout();
        return Response::redirect('/admin_insights');
    }
}
