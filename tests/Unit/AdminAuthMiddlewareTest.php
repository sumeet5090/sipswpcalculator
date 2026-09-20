<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AdminAuthService;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\AdminAuthMiddleware;
use Core\ViewRenderer;
use PHPUnit\Framework\TestCase;
use Services\SessionManagerInterface;

class AdminAuthMiddlewareTest extends TestCase
{
    public function testUnauthenticatedRequestRendersLoginViewDirectly(): void
    {
        $authService = $this->createMock(AdminAuthService::class);
        $viewRenderer = $this->createMock(ViewRenderer::class);
        $sessionManager = $this->createMock(SessionManagerInterface::class);

        $authService->method('isAuthenticated')->willReturn(false);
        $sessionManager->method('ensureCsrfToken')->willReturn('csrf_token_abc');

        $viewRenderer->expects($this->once())
            ->method('render')
            ->with('admin/login', [
                'error' => '',
                'csrf_token' => 'csrf_token_abc',
            ])
            ->willReturn('<html>Login Page Intercepted</html>');

        $middleware = new AdminAuthMiddleware($authService, $viewRenderer, $sessionManager);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_insights']);

        $reachedNext = false;
        $response = $middleware->process($request, function () use (&$reachedNext) {
            $reachedNext = true;
            return new Response('should not reach', 200);
        });

        $this->assertFalse($reachedNext);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Login Page Intercepted</html>', $response->getBody());
    }

    public function testAuthenticatedRequestPassesToNextHandler(): void
    {
        $authService = $this->createMock(AdminAuthService::class);
        $viewRenderer = $this->createMock(ViewRenderer::class);
        $sessionManager = $this->createMock(SessionManagerInterface::class);

        $authService->method('isAuthenticated')->willReturn(true);
        $viewRenderer->expects($this->never())->method('render');

        $middleware = new AdminAuthMiddleware($authService, $viewRenderer, $sessionManager);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin_insights']);

        $reachedNext = false;
        $response = $middleware->process($request, function () use (&$reachedNext) {
            $reachedNext = true;
            return new Response('admin dashboard reached', 200);
        });

        $this->assertTrue($reachedNext);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin dashboard reached', $response->getBody());
    }
}
