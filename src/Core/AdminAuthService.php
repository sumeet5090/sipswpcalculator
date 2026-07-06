<?php

declare(strict_types=1);

namespace Core;

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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
     */
    public function login(string $password): bool
    {
        $envPassword = getenv('ADMIN_INSIGHTS_PASSWORD');
        $adminPassword = ($envPassword !== false && $envPassword !== '') ? $envPassword : 'sipswp_admin_2026!';

        if (hash_equals($adminPassword, $password)) {
            $_SESSION['admin_authenticated'] = true;
            return true;
        }

        return false;
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        session_destroy();
    }
}
