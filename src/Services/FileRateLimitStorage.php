<?php

declare(strict_types=1);

namespace Services;

use Core\Exceptions\RateLimitExceededException;

class FileRateLimitStorage implements RateLimitStorageInterface
{
    private string $baseStorageDir;

    public function __construct(?string $baseStorageDir = null)
    {
        if ($baseStorageDir === null) {
            $sharedParent = dirname(__DIR__, 4) . '/shared/var/rate_limits';
            if (is_dir(dirname($sharedParent))) {
                $baseStorageDir = $sharedParent;
            } else {
                $baseStorageDir = __DIR__ . '/../../var/rate_limits';
            }
        }
        $this->baseStorageDir = $baseStorageDir;
    }

    public function checkAndIncrement(string $ip, string $prefix, int $maxRequests, int $windowSeconds): void
    {
        if ($maxRequests <= 0) {
            return;
        }

        $ipHash = hash('sha256', $ip);
        $subDir = substr($ipHash, 0, 2);
        $rateLimitDir = rtrim($this->baseStorageDir, '/\\') . '/' . trim($prefix, '/') . '/' . $subDir . '/';

        if (!is_dir($rateLimitDir) && !mkdir($rateLimitDir, 0775, true) && !is_dir($rateLimitDir)) {
            error_log("RateLimiter Error: Failed to create storage directory at {$rateLimitDir}. Check filesystem permissions.");
            throw new RateLimitExceededException('Rate limiter storage unavailable.');
        }

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
            @touch($rateFile);
        }

        $this->pruneStaleFiles($rateLimitDir, $windowSeconds);
    }

    /**
     * Opportunistically prune stale rate limit JSON files to prevent inode exhaustion.
     */
    private function pruneStaleFiles(string $dir, int $windowSeconds): void
    {
        if (random_int(1, 100) !== 1 || !is_dir($dir)) {
            return;
        }

        $entries = @scandir($dir);
        if ($entries === false) {
            return;
        }

        $now = time();
        $staleThreshold = $windowSeconds * 2;
        $count = 0;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || !str_ends_with($entry, '.json')) {
                continue;
            }
            $file = $dir . $entry;
            $mtime = @filemtime($file);
            if ($mtime !== false && ($now - $mtime) > $staleThreshold) {
                @unlink($file);
            }
            if (++$count > 50) {
                break;
            }
        }
    }
}
