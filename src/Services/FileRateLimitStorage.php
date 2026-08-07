<?php

declare(strict_types=1);

namespace Services;

use Core\Exceptions\RateLimitExceededException;

class FileRateLimitStorage implements RateLimitStorageInterface
{
    private string $baseStorageDir;

    public function __construct(?string $baseStorageDir = null)
    {
        $this->baseStorageDir = $baseStorageDir ?? __DIR__ . '/../../var/rate_limits';
    }

    public function checkAndIncrement(string $ip, string $prefix, int $maxRequests, int $windowSeconds): void
    {
        $rateLimitDir = rtrim($this->baseStorageDir, '/\\') . '/' . trim($prefix, '/') . '/';
        if (!is_dir($rateLimitDir) && !mkdir($rateLimitDir, 0700, true) && !is_dir($rateLimitDir)) {
            error_log("RateLimiter Error: Failed to create storage directory at {$rateLimitDir}");
            throw new RateLimitExceededException('Rate limiter storage unavailable.');
        }

        $ipHash = hash('sha256', $ip);
        $rateFile = $rateLimitDir . $ipHash . '.json';
        $fp = fopen($rateFile, 'c+');

        if ($fp === false) {
            error_log("RateLimiter Error: Failed to open rate limit file at {$rateFile}");
            throw new RateLimitExceededException('Rate limiter storage unavailable.');
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            error_log("RateLimiter Error: Failed to acquire lock on {$rateFile}");
            throw new RateLimitExceededException('Rate limiter storage busy.');
        }

        try {
            $content = stream_get_contents($fp);
            $rateData = !empty($content) ? json_decode($content, true) : [];
            if (!is_array($rateData)) {
                $rateData = [];
            }

            $now = time();
            $rateData = array_filter($rateData, fn($t) => is_int($t) && ($now - $t) < $windowSeconds);

            if (count($rateData) >= $maxRequests) {
                throw new RateLimitExceededException('Rate limit exceeded. Please wait before trying again.');
            }

            $rateData[] = $now;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode(array_values($rateData)));
            fflush($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
