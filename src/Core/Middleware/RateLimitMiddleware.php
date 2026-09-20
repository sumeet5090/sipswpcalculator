<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Exceptions\RateLimitExceededException;
use Core\Http\Request;
use Core\Http\Response;
use Services\ConfigServiceInterface;
use Services\RateLimiter;

/**
 * RateLimitMiddleware
 * Single-responsibility route-level middleware to enforce centralized IP rate limits.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimiter $rateLimiter;
    private ConfigServiceInterface $configService;
    private string $rateLimitKey;
    private string $storagePrefix;
    private string $customErrorMessage;

    public function __construct(
        RateLimiter $rateLimiter,
        ConfigServiceInterface $configService,
        string $rateLimitKey,
        string $storagePrefix = 'sipswp_rate_limits',
        string $customErrorMessage = 'Rate limit exceeded'
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->configService = $configService;
        $this->rateLimitKey = $rateLimitKey;
        $this->storagePrefix = $storagePrefix;
        $this->customErrorMessage = $customErrorMessage;
    }

    public function process(Request $request, callable $next): Response
    {
        $ip = $request->getClientIp();

        $rateLimits = $this->configService->getJsonConfig('content/rate_limits.json');
        $maxRequests = (int) ($rateLimits[$this->rateLimitKey]['max_requests'] ?? 60);
        $windowSeconds = (int) ($rateLimits[$this->rateLimitKey]['window_seconds'] ?? 60);

        try {
            $this->rateLimiter->checkLimit($ip, $this->storagePrefix, $maxRequests, $windowSeconds);
        } catch (RateLimitExceededException) {
            return new Response($this->customErrorMessage, 429);
        }

        return $next($request);
    }
}
