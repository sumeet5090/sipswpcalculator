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

    public function testGetBoolParsing(): void
    {
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';
        $_ENV['TEST_BOOL_ONE'] = '1';
        $_ENV['TEST_BOOL_ZERO'] = '0';
        $_ENV['TEST_BOOL_YES'] = 'yes';
        $_ENV['TEST_BOOL_NO'] = 'no';
        $_ENV['TEST_BOOL_INVALID'] = 'invalid_string';

        $this->assertTrue(Env::getBool('TEST_BOOL_TRUE'));
        $this->assertFalse(Env::getBool('TEST_BOOL_FALSE', true));
        $this->assertTrue(Env::getBool('TEST_BOOL_ONE'));
        $this->assertFalse(Env::getBool('TEST_BOOL_ZERO', true));
        $this->assertTrue(Env::getBool('TEST_BOOL_YES'));
        $this->assertFalse(Env::getBool('TEST_BOOL_NO', true));
        $this->assertTrue(Env::getBool('TEST_BOOL_INVALID', true));
        $this->assertFalse(Env::getBool('NON_EXISTENT_BOOL_KEY', false));
    }

    public function testGetIntParsing(): void
    {
        $_ENV['TEST_INT_VALID'] = '42';
        $_ENV['TEST_INT_NEGATIVE'] = '-100';
        $_ENV['TEST_INT_INVALID'] = 'not_a_number';

        $this->assertSame(42, Env::getInt('TEST_INT_VALID'));
        $this->assertSame(-100, Env::getInt('TEST_INT_NEGATIVE'));
        $this->assertSame(10, Env::getInt('TEST_INT_INVALID', 10));
        $this->assertSame(999, Env::getInt('NON_EXISTENT_INT_KEY', 999));
    }
}
