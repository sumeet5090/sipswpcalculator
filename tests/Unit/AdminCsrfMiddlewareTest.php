<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\AdminCsrfMiddleware;
use Core\ViewRenderer;
use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class AdminCsrfMiddlewareTest extends TestCase
{
    private SessionManager $sessionManager;
    private ViewRenderer $viewRenderer;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
        $this->viewRenderer = new ViewRenderer(new \Core\ViteHelper('testing'), 'testing', 'https://sipswpcalculator.com', __DIR__ . '/../../src/Views');
    }

    public function testAllowsNonAdminPostRequests(): void
    {
        $middleware = new AdminCsrfMiddleware($this->sessionManager, $this->viewRenderer);
        $request = new Request([], ['data' => '123'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/log_insight']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testBlocksAdminPostWithMissingToken(): void
    {
        $middleware = new AdminCsrfMiddleware($this->sessionManager, $this->viewRenderer);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin_insights']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAllowsAdminPostWithValidToken(): void
    {
        $this->sessionManager->start();
        $validToken = $this->sessionManager->generateCsrfToken();

        $middleware = new AdminCsrfMiddleware($this->sessionManager, $this->viewRenderer);
        $request = new Request([], ['csrf_token' => $validToken], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin_insights']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Passed', $response->getContent());
    }
}
