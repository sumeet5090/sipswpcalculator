<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class SessionManagerTest extends TestCase
{
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSetGetHasAndRemove(): void
    {
        $this->assertFalse($this->sessionManager->has('user_id'));
        $this->assertNull($this->sessionManager->get('user_id'));
        $this->assertSame('default_val', $this->sessionManager->get('user_id', 'default_val'));

        $this->sessionManager->set('user_id', 42);
        $this->assertTrue($this->sessionManager->has('user_id'));
        $this->assertSame(42, $this->sessionManager->get('user_id'));

        $this->sessionManager->remove('user_id');
        $this->assertFalse($this->sessionManager->has('user_id'));
        $this->assertNull($this->sessionManager->get('user_id'));
    }

    public function testDestroyClearsSessionArray(): void
    {
        $this->sessionManager->set('authenticated', true);
        $this->assertTrue($this->sessionManager->has('authenticated'));

        $this->sessionManager->destroy();
        $this->assertFalse($this->sessionManager->has('authenticated'));
        $this->assertEmpty($_SESSION);
    }

    public function testGenerateCsrfTokenCreatesHexadecimalToken(): void
    {
        $token = $this->sessionManager->generateCsrfToken();

        $this->assertSame(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, $this->sessionManager->get('csrf_token'));
    }

    public function testEnsureCsrfTokenReusesExistingTokenOrGenerates(): void
    {
        $firstToken = $this->sessionManager->ensureCsrfToken();
        $this->assertNotEmpty($firstToken);

        $secondToken = $this->sessionManager->ensureCsrfToken();
        $this->assertSame($firstToken, $secondToken);
    }

    public function testVerifyCsrfTokenValidatesCorrectToken(): void
    {
        $token = $this->sessionManager->generateCsrfToken();

        $this->assertTrue($this->sessionManager->verifyCsrfToken($token));
        $this->assertFalse($this->sessionManager->verifyCsrfToken('invalid_token_12345'));
        $this->assertFalse($this->sessionManager->verifyCsrfToken(''));
    }

    public function testGetCsrfTokenThrowsRuntimeExceptionWhenUnset(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSRF token has not been initialized in session.');
        $this->sessionManager->getCsrfToken();
    }

    public function testGetCsrfTokenReturnsTokenWhenSet(): void
    {
        $token = $this->sessionManager->generateCsrfToken();
        $this->assertSame($token, $this->sessionManager->getCsrfToken());
    }
}
