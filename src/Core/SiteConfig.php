<?php

declare(strict_types=1);

namespace Core;

/**
 * SiteConfig
 * Immutable value object holding site-wide URL configuration.
 */
class SiteConfig
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        /** @var string $url */
        $url = $baseUrl ?? Env::get('APP_URL', 'https://sipswpcalculator.com');
        $this->baseUrl = rtrim($url, '/');
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
        return $this->baseUrl . '/' . ltrim($path, '/');
    }
}
