<?php

declare(strict_types=1);

namespace Controllers;

use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightPayload;
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

    public function __construct(
        AnonymizedInsightLogger $insightLogger,
        RateLimiter $rateLimiter
    ) {
        $this->insightLogger = $insightLogger;
        $this->rateLimiter = $rateLimiter;
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isPost()) {
            return new Response('Method Not Allowed', 405);
        }

        // Rate limiting check (max 30 requests per minute per IP)
        try {
            $ip = $request->getClientIp();
            $this->rateLimiter->checkLimit($ip, 'sipswp_log_limits', 30, 60);
        } catch (\Core\Exceptions\RateLimitExceededException $e) {
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
