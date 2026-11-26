<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected static int $serverPid = 0;
    protected static int $serverPort = 0;

    protected static function findAvailablePort(): int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new \RuntimeException("Unable to allocate an ephemeral local port: {$errstr} ({$errno})");
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        if (!is_string($name) || !str_contains($name, ':')) {
            throw new \RuntimeException('Failed to resolve bound ephemeral socket name.');
        }
        $parts = explode(':', $name);
        return (int) end($parts);
    }

    /**
     * @return non-empty-string
     */
    protected static function getBaseUrl(string $path = ''): string
    {
        return 'http://127.0.0.1:' . self::$serverPort . $path;
    }

    /**
     * Start a background local PHP server for integration testing.
     *
     * @param int $port Optional explicit port; 0 allocates a free ephemeral OS port.
     * @param string $envPrefix Optional environment variable overrides (e.g., 'APP_URL=https://sipswpcalculator.com')
     */
    protected static function startLocalServer(int $port = 0, string $envPrefix = ''): int
    {
        $resolvedPort = $port > 0 ? $port : self::findAvailablePort();
        self::$serverPort = $resolvedPort;

        $prefix = $envPrefix !== '' ? rtrim($envPrefix) . ' ' : '';
        $command = sprintf(
            '%sphp -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!',
            $prefix,
            $resolvedPort,
            escapeshellarg(__DIR__ . '/../../index.php')
        );

        $output = [];
        exec($command, $output);
        self::$serverPid = (int)($output[0] ?? 0);

        register_shutdown_function([self::class, 'stopLocalServer']);

        // Wait up to 2 seconds for the server to bind and start responding
        $maxRetries = 20;
        $started = false;

        for ($i = 0; $i < $maxRetries; $i++) {
            $socket = @fsockopen('127.0.0.1', $resolvedPort, $errno, $errstr, 0.1);
            if ($socket) {
                fclose($socket);
                $started = true;
                break;
            }
            usleep(100000); // 100ms
        }

        if (!$started) {
            throw new \RuntimeException("Failed to start local PHP development server on 127.0.0.1:{$resolvedPort}");
        }

        return $resolvedPort;
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
        self::$serverPort = 0;
    }
}
