<?php

declare(strict_types=1);

namespace Services;

use Core\Exceptions\RateLimitExceededException;

/**
 * Interface RateLimitStorageInterface
 * Abstract contract for rate limiter persistence backends (File, Redis, Memcached, etc.).
 */
interface RateLimitStorageInterface
{
    /**
     * Check if request count exceeds threshold and log current hit.
     *
     * @throws RateLimitExceededException
     */
    public function checkAndIncrement(string $ip, string $prefix, int $maxRequests, int $windowSeconds): void;
}
