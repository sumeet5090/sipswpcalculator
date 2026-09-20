<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;
use Services\RateLimiter;
use Services\RateLimitStorageInterface;

class RateLimitMiddlewareTest extends TestCase
{
    private ConfigService $configService;

    protected function setUp(): void
    {
        $this->configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
    }

    public function testAllowsRequestUnderLimit(): void
    {
        $storage = $this->createMock(RateLimitStorageInterface::class);
        $storage->expects($this->once())
            ->method('checkAndIncrement')
            ->with('1.2.3.4', 'test_prefix', 10, 60);

        $rateLimiter = new RateLimiter($storage);
        $middleware = new RateLimitMiddleware(
            $rateLimiter,
            $this->configService,
            'pdf_generation',
            'test_prefix',
            'Limit hit'
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);
        $response = $middleware->process($request, function (Request $req) {
            return new Response('passed through', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('passed through', $response->getBody());
    }

    public function testRejectsRequestExceedingLimitWith429(): void
    {
        $storage = $this->createMock(RateLimitStorageInterface::class);
        $storage->expects($this->once())
            ->method('checkAndIncrement')
            ->willThrowException(new RateLimitExceededException('Exceeded.'));

        $rateLimiter = new RateLimiter($storage);
        $middleware = new RateLimitMiddleware(
            $rateLimiter,
            $this->configService,
            'pdf_generation',
            'test_prefix',
            'Too many requests. Please wait a minute before generating another PDF.'
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4']);
        $response = $middleware->process($request, function (Request $req) {
            return new Response('should not reach', 200);
        });

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('Too many requests. Please wait a minute before generating another PDF.', $response->getBody());
    }
}
