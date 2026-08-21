<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\ConfigurationException;

/**
 * SiteConfig
 * Immutable value object holding site-wide URL configuration.
 */
class SiteConfig
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $trimmed = trim($baseUrl);
        if ($trimmed === '' || !preg_match('#^https?://#i', $trimmed)) {
            throw new ConfigurationException("Invalid base URL '{$baseUrl}'. Must be non-empty and start with http:// or https://.");
        }
        $this->baseUrl = rtrim($trimmed, '/');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getUrl(string $path = ''): string
    {
        if ($path === '' || $path === '/') {
            return $this->baseUrl . '/';
        }
        $cleanPath = ltrim($path, '/');
        // Collapse any consecutive internal slashes in path
        $cleanPath = (string) preg_replace('#/{2,}#', '/', $cleanPath);
        return $this->baseUrl . '/' . $cleanPath;
    }
}
