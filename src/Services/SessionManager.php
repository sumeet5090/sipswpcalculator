<?php

declare(strict_types=1);

namespace Services;

/**
 * SessionManager
 * Encapsulates $_SESSION interaction and lifecycle management.
 */
class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return isset($_SESSION) ? ($_SESSION[$key] ?? $default) : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION) && !empty($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $this->start();
        session_destroy();
    }

    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);
        return $token;
    }

    public function getCsrfToken(): string
    {
        $token = $this->get('csrf_token');
        if (!is_string($token) || $token === '') {
            return $this->generateCsrfToken();
        }
        return $token;
    }

    public function verifyCsrfToken(string $token): bool
    {
        $stored = $this->get('csrf_token');
        return is_string($stored) && hash_equals($stored, $token);
    }
}
