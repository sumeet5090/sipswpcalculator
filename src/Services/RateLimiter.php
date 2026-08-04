<?php

declare(strict_types=1);

namespace Services;

use Core\Exceptions\RateLimitExceededException;

/**
 * RateLimiter
 * Provides file-based IP rate limiting logic.
 */
class RateLimiter
{
    /**
     * Check if request count exceeds rate limit for a given IP and action prefix.
     *
     * @throws RateLimitExceededException
     */
    public function checkLimit(string $ip, string $prefix, int $maxRequests, int $windowSeconds = 60): void
    {
        $rateLimitDir = sys_get_temp_dir() . '/' . trim($prefix, '/') . '/';
        if (!is_dir($rateLimitDir)) {
            @mkdir($rateLimitDir, 0700, true);
        }

        $ipHash = hash('sha256', $ip);
        $rateFile = $rateLimitDir . $ipHash . '.json';
        $fp = @fopen($rateFile, 'c+');

        if ($fp && flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            $rateData = !empty($content) ? json_decode($content, true) : [];
            if (!is_array($rateData)) {
                $rateData = [];
            }

            $now = time();
            $rateData = array_filter($rateData, fn($t) => is_int($t) && ($now - $t) < $windowSeconds);

            if (count($rateData) >= $maxRequests) {
                flock($fp, LOCK_UN);
                fclose($fp);
                throw new RateLimitExceededException('Rate limit exceeded. Please wait before trying again.');
            }

            $rateData[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode(array_values($rateData)));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
