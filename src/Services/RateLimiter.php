<?php

declare(strict_types=1);

namespace Services;

use Core\Exceptions\RateLimitExceededException;

/**
 * RateLimiter
 * High-level rate limiter delegating to an injected RateLimitStorageInterface strategy.
 */
class RateLimiter
{
    private RateLimitStorageInterface $storage;

    public function __construct(RateLimitStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Check if request count exceeds rate limit for a given IP and action prefix.
     *
     * @throws RateLimitExceededException
     */
    public function checkLimit(string $ip, string $prefix, int $maxRequests, int $windowSeconds = 60): void
    {
        $this->storage->checkAndIncrement($ip, $prefix, $maxRequests, $windowSeconds);
    }
}
