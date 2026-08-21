<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CsrfHoneypotMiddleware;
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
            'REQUEST_URI' => '/sip-calculator/',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => 'sip=5000'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('next_called');
        });

        $this->assertSame(301, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertSame('/sip-calculator?sip=5000', $headers['Location']);
    }

    public function testTrailingSlashRedirectOnPost(): void
    {
        $middleware = new TrailingSlashRedirectMiddleware();
        $request = new Request([], [], [
            'REQUEST_URI' => '/calculate/',
            'REQUEST_METHOD' => 'POST'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('next_called');
        });

        $this->assertSame(308, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertSame('/calculate', $headers['Location']);
    }

    public function testTrailingSlashRootIgnored(): void
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

    public function testSessionMiddlewareLazyInitializationPublic(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new SessionMiddleware($sessionManager);

        $request = new Request([], [], [
            'REQUEST_URI' => '/sip-calculator',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () {
            return Response::html('public_page');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('public_page', $response->getContent());
    }

    public function testSessionMiddlewareStartsOnAdminRoute(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new SessionMiddleware($sessionManager);

        $request = new Request([], [], [
            'REQUEST_URI' => '/admin_insights',
            'REQUEST_METHOD' => 'GET'
        ]);

        $response = $middleware->process($request, function () use ($sessionManager) {
            return Response::html($sessionManager->getCsrfToken());
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function testCsrfHoneypotBlocksSpam(): void
    {
        $sessionManager = new SessionManager();
        $middleware = new CsrfHoneypotMiddleware($sessionManager);

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
        $middleware = new CsrfHoneypotMiddleware($sessionManager);

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
        $middleware = new CsrfHoneypotMiddleware($sessionManager);

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
        $middleware = new CsrfHoneypotMiddleware($sessionManager);

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
}
