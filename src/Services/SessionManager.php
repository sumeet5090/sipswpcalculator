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
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure'   => $isHttps,
            ]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return isset($_SESSION) ? ($_SESSION[$key] ?? $default) : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION) && !empty($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }
    }

    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);
        return $token;
    }

    public function ensureCsrfToken(): string
    {
        $this->start();
        $token = $this->get('csrf_token');
        if (!is_string($token) || $token === '') {
            return $this->generateCsrfToken();
        }
        return $token;
    }

    public function getCsrfToken(): string
    {
        $token = $this->get('csrf_token');
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('CSRF token has not been initialized in session.');
        }
        return $token;
    }

    public function verifyCsrfToken(mixed $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }
        try {
            $stored = $this->getCsrfToken();
            return hash_equals($stored, $token);
        } catch (\Throwable) {
            return false;
        }
    }
}
