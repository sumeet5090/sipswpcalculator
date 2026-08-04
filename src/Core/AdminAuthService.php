<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\AuthenticationException;

/**
 * AdminAuthService
 * Handles authentication and session management for the admin dashboard.
 */
class AdminAuthService
{
    /**
     * Start the session if not already started.
     */
    public function __construct()
    {
    }

    /**
     * Check if the user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['admin_authenticated']);
    }

    /**
     * Attempt to log in with the provided password.
     *
     * @throws AuthenticationException
     */
    public function login(string $password): void
    {
        $envPassword = Env::get('ADMIN_INSIGHTS_PASSWORD');
        if (!is_string($envPassword) || $envPassword === '') {
            throw new \RuntimeException('ADMIN_INSIGHTS_PASSWORD environment variable is missing or empty.');
        }

        if (hash_equals($envPassword, $password)) {
            $_SESSION['admin_authenticated'] = true;
            return;
        }

        throw new AuthenticationException('Invalid password provided.');
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        session_destroy();
    }
}
