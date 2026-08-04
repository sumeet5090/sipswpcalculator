<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected static int $serverPid = 0;

    /**
     * Start a background local PHP server for integration testing.
     *
     * @param int $port
     * @param string $envPrefix Optional environment variable overrides (e.g., 'APP_URL=https://sipswpcalculator.com')
     */
    protected static function startLocalServer(int $port, string $envPrefix = ''): void
    {
        $prefix = $envPrefix !== '' ? rtrim($envPrefix) . ' ' : '';
        $command = sprintf(
            '%sphp -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!',
            $prefix,
            $port,
            escapeshellarg(__DIR__ . '/../../index.php')
        );

        $output = [];
        exec($command, $output);
        self::$serverPid = (int)($output[0] ?? 0);

        // Wait up to 1 second for the server to bind and start responding
        $maxRetries = 10;
        $started = false;

        for ($i = 0; $i < $maxRetries; $i++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($socket) {
                fclose($socket);
                $started = true;
                break;
            }
            usleep(100000); // 100ms
        }

        if (!$started) {
            throw new \RuntimeException("Failed to start local PHP development server on 127.0.0.1:{$port}");
        }
    }

    /**
     * Terminate the background local PHP server.
     */
    protected static function stopLocalServer(): void
    {
        if (self::$serverPid > 0) {
            exec('kill -9 ' . self::$serverPid . ' 2>/dev/null');
            self::$serverPid = 0;
        }
    }
}
