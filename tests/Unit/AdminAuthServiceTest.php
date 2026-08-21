<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AdminAuthService;
use Core\Exceptions\AuthenticationException;
use Core\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;
use Services\SessionManager;

class AdminAuthServiceTest extends TestCase
{
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->sessionManager = new SessionManager();
    }

    public function testLoginSucceedsWithCorrectPassword(): void
    {
        $service = new AdminAuthService($this->sessionManager, 'secure_password_123');

        $this->assertFalse($service->isAuthenticated());
        $service->login('secure_password_123');
        $this->assertTrue($service->isAuthenticated());
    }

    public function testLoginFailsWithIncorrectPasswordThrowsAuthenticationException(): void
    {
        $service = new AdminAuthService($this->sessionManager, 'correct_pass');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid password provided.');
        $service->login('wrong_pass');
    }

    public function testEmptyPasswordThrowsConfigurationException(): void
    {
        $service = new AdminAuthService($this->sessionManager, '');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('ADMIN_INSIGHTS_PASSWORD environment variable is missing or empty.');
        $service->login('any_pass');
    }

    public function testLogoutClearsAuthentication(): void
    {
        $service = new AdminAuthService($this->sessionManager, 'test_pass');
        $service->login('test_pass');
        $this->assertTrue($service->isAuthenticated());

        $service->logout();
        $this->assertFalse($service->isAuthenticated());
    }
}
