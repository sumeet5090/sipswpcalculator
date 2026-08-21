<?php

declare(strict_types=1);

namespace Services;

/**
 * SessionManagerInterface
 * Contract for session interaction, lifecycle, and CSRF token verification.
 */
interface SessionManagerInterface
{
    public function start(): void;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function destroy(): void;

    public function generateCsrfToken(): string;

    public function ensureCsrfToken(): string;

    public function getCsrfToken(): string;

    public function verifyCsrfToken(mixed $token): bool;
}
