<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\AdminCsrfMiddleware;
use Core\Middleware\CsrfHoneypotMiddleware;
use Core\Middleware\HoneypotMiddleware;
use Core\Middleware\SessionMiddleware;
use Core\Middleware\TrailingSlashRedirectMiddleware;
use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class MiddlewarePipelineTest extends TestCase
{
    public function testTrailingSlashRedirectOnGet(): void
    {
        $middleware = new TrailingSlashRedirectMiddleware();
        $request = new Request([], [], [
            'REQUEST_URI' => '/about/',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/about', $response->getHeader('Location'));
    }

    public function testTrailingSlashRedirectOnPost(): void
    {
        $middleware = new TrailingSlashRedirectMiddleware();
        $request = new Request([], [], [
            'REQUEST_URI' => '/generate-pdf/',
            'REQUEST_METHOD' => 'POST'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/generate-pdf', $response->getHeader('Location'));
    }

    public function testNoRedirectForRootSlash(): void
    {
        $middleware = new TrailingSlashRedirectMiddleware();
        $request = new Request([], [], [
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('root_reached');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('root_reached', $response->getContent());
    }

    public function testSessionMiddlewareStartsSessionForAdmin(): void
    {
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('start');
        $sessionManager->expects($this->once())->method('ensureCsrfToken');

        $middleware = new SessionMiddleware($sessionManager);
        $request = new Request([], [], [
            'REQUEST_URI' => '/admin_insights',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('admin_reached');
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSessionMiddlewareDoesNotStartForPublicWithoutCookie(): void
    {
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->never())->method('start');

        $middleware = new SessionMiddleware($sessionManager);
        $request = new Request([], [], [
            'REQUEST_URI' => '/about',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('about_reached');
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSessionMiddlewareStartsWhenSessionCookieExists(): void
    {
        $sessionManager = new SessionManager();
        $sessionCookieName = session_name();
        $middleware = new SessionMiddleware($sessionManager);

        $request = new Request(
            [],
            [],
            [
                'REQUEST_URI' => '/about',
                'REQUEST_METHOD' => 'GET',
            ],
            [],
            null,
            [$sessionCookieName => 'mock_session_id']
        );

        $response = $middleware->process($request, function () use ($sessionManager) {
            return Response::html($sessionManager->getCsrfToken());
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function testCsrfHoneypotBlocksSpam(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['website_url' => 'http://spam-bot.com'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/generate-pdf'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Automated request detected', $response->getContent());
    }

    public function testCsrfHoneypotBlocksAdminWithoutToken(): void
    {
        $sessionManager = new SessionManager();
        $sessionManager->ensureCsrfToken();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['password' => 'secret'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/admin_insights'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Invalid security token', $response->getContent());
    }

    public function testCsrfHoneypotAllowsAdminWithValidToken(): void
    {
        $sessionManager = new SessionManager();
        $token = $sessionManager->ensureCsrfToken();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['csrf_token' => $token, 'password' => 'secret'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/admin_insights'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('admin_login_processed');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin_login_processed', $response->getContent());
    }

    public function testCsrfHoneypotAllowsNonAdminPostWithoutToken(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['calc_type' => 'SIP', 'amount' => 5000], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/log_insight'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('log_insight_processed');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('log_insight_processed', $response->getContent());
    }

    public function testCsrfHoneypotBlocksArraySpamPayload(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['website_url' => ['malicious_array']], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/generate-pdf'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Automated request detected', $response->getContent());
    }

    public function testCsrfHoneypotRefreshesTokenOnFailure(): void
    {
        $sessionManager = new SessionManager();
        $initialToken = $sessionManager->ensureCsrfToken();
        $middleware = new CsrfHoneypotMiddleware(new HoneypotMiddleware(), new AdminCsrfMiddleware($sessionManager));

        $request = new Request([], ['csrf_token' => 'invalid_expired_token', 'password' => 'secret'], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/admin_insights'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(403, $response->getStatusCode());
        // Verify token was refreshed
        $this->assertNotSame($initialToken, $sessionManager->getCsrfToken());
    }

    public function testTrailingSlashRedirectPreservesQueryParamsOnPost(): void
    {
        $middleware = new TrailingSlashRedirectMiddleware();
        $request = new Request([], [], [
            'REQUEST_URI' => '/calculate/',
            'QUERY_STRING' => 'sip=5000&years=10',
            'REQUEST_METHOD' => 'POST'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('should_not_reach');
        });

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('/calculate?sip=5000&years=10', $response->getHeader('Location'));
    }

    public function testDirectPipedRouterExecution(): void
    {
        $app = new \Core\App();
        $router = $app->getRouter();
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/'
        ]);

        $response = $router->dispatch($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
    }
}
