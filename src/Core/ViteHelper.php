<?php

namespace Core;

class ViteHelper
{
    private static $manifest = null;
    private static ?bool $devServerActive = null;

    /**
     * Check if Vite dev server is running on port 5173.
     */
    private static function isDevServerRunning(): bool
    {
        if (self::$devServerActive !== null) {
            return self::$devServerActive;
        }

        $env = $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: 'development';
        if ($env === 'production') {
            self::$devServerActive = false;
            return false;
        }

        // Fast socket check to see if port 5173 is open
        $connection = @fsockopen('127.0.0.1', 5173, $errno, $errstr, 0.05);
        if (is_resource($connection)) {
            fclose($connection);
            self::$devServerActive = true;
            return true;
        }

        self::$devServerActive = false;
        return false;
    }

    /**
     * Get the Vite asset URL (either dev server or compiled production file).
     */
    public static function asset(string $entry): string
    {
        if (self::isDevServerRunning()) {
            return "http://localhost:5173/" . ltrim($entry, '/');
        }

        self::loadManifest();
        $entryKey = ltrim($entry, '/');

        if (isset(self::$manifest[$entryKey])) {
            return '/dist/' . self::$manifest[$entryKey]['file'];
        }

        return '/' . ltrim($entry, '/');
    }

    /**
     * Inject Vite Dev Server client script (only when dev server is active).
     */
    public static function client(): string
    {
        if (!self::isDevServerRunning()) {
            return '';
        }
        return '<script type="module" src="http://localhost:5173/@vite/client"></script>';
    }

    /**
     * Get the CSS files associated with an entry.
     */
    public static function css(string $entry): string
    {
        if (self::isDevServerRunning()) {
            return ''; // CSS is injected via JS in dev mode when Vite dev server is running
        }

        self::loadManifest();
        $entryKey = ltrim($entry, '/');
        $html = '';

        if (isset(self::$manifest[$entryKey]['css'])) {
            foreach (self::$manifest[$entryKey]['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/dist/' . $cssFile . '">';
            }
        }

        return $html;
    }

    /**
     * Load dist/.vite/manifest.json safely.
     */
    private static function loadManifest(): void
    {
        if (self::$manifest !== null) {
            return;
        }

        $manifestPath = __DIR__ . '/../../dist/.vite/manifest.json';
        if (!file_exists($manifestPath)) {
            $manifestPath = __DIR__ . '/../../dist/manifest.json';
        }

        if (file_exists($manifestPath)) {
            self::$manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
        } else {
            self::$manifest = [];
        }
    }
}
