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
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }

        return $default;
    }

    /**
     * Get an environment variable as a boolean, safely parsing "true", "false", "1", "0", "yes", "no".
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if ($val === null) {
            return $default;
        }

        $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $bool ?? $default;
    }

    /**
     * Get an environment variable as an integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $val = self::get($key);
        if ($val === null || !is_numeric($val)) {
            return $default;
        }

        return (int) $val;
    }

    /**
     * Get an environment variable as a float.
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        $val = self::get($key);
        if ($val === null || !is_numeric($val)) {
            return $default;
        }

        return (float) $val;
    }

    /**
     * Get an environment variable as an array from a delimited string.
     *
     * @param string $key
     * @param array<int, string> $default
     * @param string $delimiter
     * @return array<int, string>
     */
    public static function getArray(string $key, array $default = [], string $delimiter = ','): array
    {
        $val = self::get($key);
        if ($val === null) {
            return $default;
        }

        if (is_array($val)) {
            return $val;
        }

        $trimmed = trim((string) $val);
        if ($trimmed === '') {
            return $default;
        }

        $parts = explode($delimiter, $trimmed);
        $result = [];
        foreach ($parts as $part) {
            $p = trim($part);
            if ($p !== '') {
                $result[] = $p;
            }
        }

        return $result;
    }
}
