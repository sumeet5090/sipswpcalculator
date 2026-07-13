<?php

namespace Core;

class ViteHelper
{
    private static $manifest = null;

    /**
     * Get the Vite asset URL (either dev server or compiled production file).
     */
    public static function asset(string $entry): string
    {
        $isProduction = ($_ENV['ENVIRONMENT'] ?? 'development') === 'production';

        if (!$isProduction) {
            // During development, serve from the Vite dev server
            return "http://localhost:5173/" . ltrim($entry, '/');
        }

        // In production, read from manifest
        if (self::$manifest === null) {
            $manifestPath = __DIR__ . '/../../dist/.vite/manifest.json';

            // Vite v5 sometimes places it in dist/.vite/manifest.json, older versions in dist/manifest.json
            if (!file_exists($manifestPath)) {
                $manifestPath = __DIR__ . '/../../dist/manifest.json';
            }

            if (file_exists($manifestPath)) {
                self::$manifest = json_decode(file_get_contents($manifestPath), true);
            } else {
                self::$manifest = [];
            }
        }

        $entryKey = ltrim($entry, '/');

        if (isset(self::$manifest[$entryKey])) {
            return '/dist/' . self::$manifest[$entryKey]['file'];
        }

        // Fallback if not found in manifest
        return '/' . ltrim($entry, '/');
    }

    /**
     * Inject Vite Dev Server client script (only in dev).
     */
    public static function client(): string
    {
        $isProduction = ($_ENV['ENVIRONMENT'] ?? 'development') === 'production';
        if ($isProduction) {
            return '';
        }
        return '<script type="module" src="http://localhost:5173/@vite/client"></script>';
    }

    /**
     * Get the CSS files associated with an entry (only needed in production).
     */
    public static function css(string $entry): string
    {
        $isProduction = ($_ENV['ENVIRONMENT'] ?? 'development') === 'production';
        if (!$isProduction) {
            return ''; // CSS is injected via JS in dev mode
        }

        // Ensure manifest is loaded
        self::asset($entry);
        $entryKey = ltrim($entry, '/');
        $html = '';

        if (isset(self::$manifest[$entryKey]['css'])) {
            foreach (self::$manifest[$entryKey]['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/dist/' . $cssFile . '">';
            }
        }

        return $html;
    }
}
