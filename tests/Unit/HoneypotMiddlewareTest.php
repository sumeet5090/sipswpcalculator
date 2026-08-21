<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\HoneypotMiddleware;
use PHPUnit\Framework\TestCase;

class HoneypotMiddlewareTest extends TestCase
{
    public function testAllowsGetRequestWithoutInspection(): void
    {
        $middleware = new HoneypotMiddleware();
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Passed', $response->getContent());
    }

    public function testAllowsPostRequestWithEmptyHoneypot(): void
    {
        $middleware = new HoneypotMiddleware('website_url');
        $request = new Request([], ['website_url' => ''], ['REQUEST_METHOD' => 'POST']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testBlocksPostRequestWithFilledHoneypot(): void
    {
        $middleware = new HoneypotMiddleware('website_url');
        $request = new Request([], ['website_url' => 'http://spam-bot.com'], ['REQUEST_METHOD' => 'POST']);

        $response = $middleware->process($request, function (Request $req) {
            return new Response('Passed', 200);
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Forbidden: Automated request detected.', $response->getContent());
    }
}
