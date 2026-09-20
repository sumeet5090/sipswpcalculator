<?php

declare(strict_types=1);

namespace Controllers;

use Core\AnonymizedInsightLogger;
use Core\Http\Request;
use Core\Http\Response;
use Core\InsightPayload;

/**
 * LogInsightApiAction
 * Single Responsibility action dedicated strictly to validating and logging calculation analytics payloads.
 * Rate limiting is enforced upstream via RateLimitMiddleware.
 */
class LogInsightApiAction
{
    public const MAX_PAYLOAD_SIZE_BYTES = 65536;

    private AnonymizedInsightLogger $insightLogger;

    public function __construct(AnonymizedInsightLogger $insightLogger)
    {
        $this->insightLogger = $insightLogger;
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->isPost()) {
            return new Response('Method Not Allowed', 405);
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
