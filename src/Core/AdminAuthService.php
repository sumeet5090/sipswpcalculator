<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\AuthenticationException;
use Core\Exceptions\ConfigurationException;
use Services\SessionManager;

/**
 * AdminAuthService
 * Handles authentication and session management for the admin dashboard.
 */
class AdminAuthService
{
    private SessionManager $sessionManager;
    private string $adminPassword;

    public function __construct(SessionManager $sessionManager, string $adminPassword = '')
    {
        $this->sessionManager = $sessionManager;
        $this->adminPassword = $adminPassword;
    }

    /**
     * Check if the user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return $this->sessionManager->has('admin_authenticated');
    }

    /**
     * Attempt to log in with the provided password.
     *
     * @throws AuthenticationException
     * @throws ConfigurationException
     */
    public function login(string $password): void
    {
        if ($this->adminPassword === '') {
            throw new ConfigurationException('ADMIN_INSIGHTS_PASSWORD environment variable is missing or empty.');
        }

        if (hash_equals($this->adminPassword, $password)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $this->sessionManager->set('admin_authenticated', true);
            return;
        }

        throw new AuthenticationException('Invalid password provided.');
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->sessionManager->destroy();
    }
}
