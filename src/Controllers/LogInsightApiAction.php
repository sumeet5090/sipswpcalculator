<?php

declare(strict_types=1);

namespace Controllers;

use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightPayload;
use Services\ConfigServiceInterface;
use Services\RateLimiter;

/**
 * LogInsightApiAction
 * Single Responsibility action dedicated strictly to rate limiting and logging calculation analytics payloads.
 */
class LogInsightApiAction
{
    public const MAX_PAYLOAD_SIZE_BYTES = 65536;

    private AnonymizedInsightLogger $insightLogger;
    private RateLimiter $rateLimiter;
    private ConfigServiceInterface $configService;

    public function __construct(
        AnonymizedInsightLogger $insightLogger,
        RateLimiter $rateLimiter,
        ConfigServiceInterface $configService
    ) {
        $this->insightLogger = $insightLogger;
        $this->rateLimiter = $rateLimiter;
        $this->configService = $configService;
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isPost()) {
            return new Response('Method Not Allowed', 405);
        }

        // Rate limiting check using centralized configuration
        try {
            $ip = $request->getClientIp();
            $rateLimits = $this->configService->getJsonConfig('content/rate_limits.json');
            $maxRequests = (int) ($rateLimits['log_insight']['max_requests'] ?? 30);
            $windowSeconds = (int) ($rateLimits['log_insight']['window_seconds'] ?? 60);
            $this->rateLimiter->checkLimit($ip, 'sipswp_log_limits', $maxRequests, $windowSeconds);
        } catch (\Core\Exceptions\RateLimitExceededException) {
            return new Response('Rate limit exceeded', 429);
        }

        $rawBody = $request->getRawBody();
        if (strlen($rawBody) > self::MAX_PAYLOAD_SIZE_BYTES) { // 64KB limit
            return new Response('Payload Too Large', 413);
        }

        $data = $request->getParsedBody();

        if (empty($data) || !isset($data['calc_type'], $data['amount'], $data['duration'])) {
            return new Response('Invalid payload', 400);
        }

        $payload = InsightPayload::fromArray($data);
        $this->insightLogger->logCalculation($payload, $request);

        return new Response('', 204);
    }
}
