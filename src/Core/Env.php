<?php

declare(strict_types=1);

namespace Core;

/**
 * Env
 * Centralized environment variable resolver that ensures OS-level environment
 * overrides (e.g. from CLI/testing) take precedence over $_ENV.
 */
class Env
{
    /**
     * Get an environment variable value with a fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        return $default;
    }
}
