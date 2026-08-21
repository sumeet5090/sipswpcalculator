<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\RateLimitExceededException;
use PHPUnit\Framework\TestCase;
use Services\FileRateLimitStorage;

class FileRateLimitStorageTest extends TestCase
{
    private string $tempDir;
    private FileRateLimitStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->storage = new FileRateLimitStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testCheckAndIncrementAllowsRequestsWithinLimit(): void
    {
        $ip = '192.168.1.100';
        $prefix = 'api_test';

        // 3 requests allowed in 60s window
        $this->storage->checkAndIncrement($ip, $prefix, 3, 60);
        $this->storage->checkAndIncrement($ip, $prefix, 3, 60);
        $this->storage->checkAndIncrement($ip, $prefix, 3, 60);

        // Verify sharded file structure
        $ipHash = hash('sha256', $ip);
        $subDir = substr($ipHash, 0, 2);
        $filePath = "{$this->tempDir}/{$prefix}/{$subDir}/{$ipHash}.json";

        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertNotEmpty($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
    }

    public function testCheckAndIncrementThrowsWhenLimitExceeded(): void
    {
        $ip = '192.168.1.101';
        $prefix = 'limit_test';

        $this->storage->checkAndIncrement($ip, $prefix, 2, 60);
        $this->storage->checkAndIncrement($ip, $prefix, 2, 60);

        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionMessage('Rate limit exceeded');
        $this->storage->checkAndIncrement($ip, $prefix, 2, 60);
    }

    public function testRecoversFromCorruptedOrEmptyRateLimitFile(): void
    {
        $ip = '192.168.1.102';
        $prefix = 'corrupt_test';
        $ipHash = hash('sha256', $ip);
        $subDir = substr($ipHash, 0, 2);
        $dir = "{$this->tempDir}/{$prefix}/{$subDir}";
        mkdir($dir, 0777, true);

        // Write corrupt non-JSON content
        file_put_contents("{$dir}/{$ipHash}.json", "INVALID_NOT_JSON");

        // Should recover cleanly and record the new timestamp
        $this->storage->checkAndIncrement($ip, $prefix, 5, 60);

        $data = json_decode((string) file_get_contents("{$dir}/{$ipHash}.json"), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
    }

    public function testZeroOrNegativeMaxRequestsNoOps(): void
    {
        $ip = '192.168.1.103';
        $prefix = 'noop_test';

        $this->storage->checkAndIncrement($ip, $prefix, 0, 60);
        $this->storage->checkAndIncrement($ip, $prefix, -5, 60);

        $ipHash = hash('sha256', $ip);
        $subDir = substr($ipHash, 0, 2);
        $filePath = "{$this->tempDir}/{$prefix}/{$subDir}/{$ipHash}.json";

        $this->assertFileDoesNotExist($filePath);
    }

    public function testZeroOrNegativeWindowSecondsNoOps(): void
    {
        $ip = '192.168.1.104';
        $prefix = 'noop_win_test';

        $this->storage->checkAndIncrement($ip, $prefix, 5, 0);
        $this->storage->checkAndIncrement($ip, $prefix, 5, -10);

        $ipHash = hash('sha256', $ip);
        $subDir = substr($ipHash, 0, 2);
        $filePath = "{$this->tempDir}/{$prefix}/{$subDir}/{$ipHash}.json";

        $this->assertFileDoesNotExist($filePath);
    }

    public function testExceptionMessagesDoNotLeakInternalPaths(): void
    {
        $ip = '192.168.1.105';
        $prefix = 'leak_test';

        $this->storage->checkAndIncrement($ip, $prefix, 1, 60);

        try {
            $this->storage->checkAndIncrement($ip, $prefix, 1, 60);
            $this->fail('Expected RateLimitExceededException was not thrown');
        } catch (RateLimitExceededException $e) {
            $this->assertStringNotContainsString($this->tempDir, $e->getMessage());
            $this->assertStringContainsString('Rate limit exceeded', $e->getMessage());
        }
    }
}
