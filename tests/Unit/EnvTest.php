<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    private array $envBackup;
    private array $serverBackup;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testEnvArrayTakesPrecedence(): void
    {
        $_ENV['TEST_KEY_PRECEDENCE'] = 'from_env';
        $_SERVER['TEST_KEY_PRECEDENCE'] = 'from_server';
        putenv('TEST_KEY_PRECEDENCE=from_getenv');

        $this->assertSame('from_env', Env::get('TEST_KEY_PRECEDENCE', 'default'));
    }

    public function testServerArrayTakesPrecedenceOverGetenv(): void
    {
        unset($_ENV['TEST_KEY_SERVER']);
        $_SERVER['TEST_KEY_SERVER'] = 'from_server';
        putenv('TEST_KEY_SERVER=from_getenv');

        $this->assertSame('from_server', Env::get('TEST_KEY_SERVER', 'default'));
    }

    public function testGetenvTakesPrecedenceOverDefault(): void
    {
        unset($_ENV['TEST_KEY_GETENV']);
        unset($_SERVER['TEST_KEY_GETENV']);
        putenv('TEST_KEY_GETENV=from_getenv');

        $this->assertSame('from_getenv', Env::get('TEST_KEY_GETENV', 'default'));
    }

    public function testEmptyStringInEnvFallsThrough(): void
    {
        $_ENV['TEST_KEY_EMPTY'] = '';
        $_SERVER['TEST_KEY_EMPTY'] = 'fallback_from_server';

        $this->assertSame('fallback_from_server', Env::get('TEST_KEY_EMPTY', 'default'));
    }

    public function testDefaultFallbackWhenUndefined(): void
    {
        unset($_ENV['COMPLETELY_UNDEFINED_KEY_9999']);
        unset($_SERVER['COMPLETELY_UNDEFINED_KEY_9999']);
        putenv('COMPLETELY_UNDEFINED_KEY_9999');

        $this->assertSame('fallback_val', Env::get('COMPLETELY_UNDEFINED_KEY_9999', 'fallback_val'));
    }
}
