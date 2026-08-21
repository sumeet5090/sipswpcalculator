<?php

declare(strict_types=1);

namespace Tests\Unit;

use Controllers\ProcessAdminLoginAction;
use Controllers\ProcessAdminLogoutAction;
use Controllers\ShowAdminLoginAction;
use Core\AdminAuthService;
use Core\Exceptions\AuthenticationException;
use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\RateLimiter;
use Services\SessionManager;

class AdminActionControllersTest extends TestCase
{
    public function testShowAdminLoginActionRendersLoginFormHtml(): void
    {
        $viewRenderer = $this->createMock(ViewRenderer::class);
        $sessionManager = $this->createMock(SessionManager::class);

        $sessionManager->expects($this->once())
            ->method('ensureCsrfToken')
            ->willReturn('mock_csrf');

        $viewRenderer->expects($this->once())
            ->method('render')
            ->with('admin/login', [
                'error' => '',
                'csrf_token' => 'mock_csrf',
            ])
            ->willReturn('<html>Login Form</html>');

        $action = new ShowAdminLoginAction($viewRenderer, $sessionManager);
        $request = new Request();
        $response = $action($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Login Form</html>', $response->getContent());
    }

    public function testProcessAdminLoginActionRedirectsOnSuccessfulPassword(): void
    {
        $authService = $this->createMock(AdminAuthService::class);
        $viewRenderer = $this->createMock(ViewRenderer::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $configService = $this->createMock(ConfigService::class);

        $configService->method('getJsonConfig')->willReturn([
            'admin_auth' => ['max_requests' => 5, 'window_seconds' => 300]
        ]);

        $authService->expects($this->once())
            ->method('login')
            ->with('secret_password');

        $action = new ProcessAdminLoginAction($authService, $viewRenderer, $sessionManager, $rateLimiter, $configService);
        $request = new Request([], ['password' => 'secret_password'], ['REQUEST_METHOD' => 'POST']);
        $response = $action($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admin_insights', $response->getHeader('Location'));
    }

    public function testProcessAdminLogoutActionDestroysSessionAndRedirects(): void
    {
        $authService = $this->createMock(AdminAuthService::class);

        $authService->expects($this->once())
            ->method('logout');

        $action = new ProcessAdminLogoutAction($authService);
        $response = $action();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/admin_insights', $response->getHeader('Location'));
    }
}
